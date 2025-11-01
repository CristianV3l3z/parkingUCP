<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class datosController extends Controller
{
    /**
     * Mostrar la vista de datos
     */
    public function index(Request $request)
    {
        // debug opcional para ver si entra el vigilante
        Log::debug('datos.index called', [
            'auth' => Auth::check(),
            'session_vigilante' => $request->session()->has('vigilante')
        ]);

        return view('datos');
    }

    /**
     * KPIs resumen (totales) en un rango from/to (opcionales)
     * Query params: ?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function summary(Request $request)
    {
        // Parsear from/to (si vienen) y forzar inicio/fin de día
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : Carbon::now()->startOfMonth(); // por defecto: inicio mes actual

        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : Carbon::now()->endOfMonth(); // por defecto: fin mes actual

        // Totales de vehículos creados en el rango (por created_at)
        $totales = DB::selectOne("
            SELECT
              COUNT(*) FILTER (WHERE LOWER(tipo_vehiculo) = 'carro' AND v.created_at BETWEEN ? AND ?) AS carros,
              COUNT(*) FILTER (WHERE LOWER(tipo_vehiculo) = 'moto'  AND v.created_at BETWEEN ? AND ?) AS motos,
              COUNT(*) AS total
            FROM vehiculo v
            WHERE v.created_at BETWEEN ? AND ?
        ", [
            $from, $to,
            $from, $to,
            $from, $to
        ]);

        $carros = (int)($totales->carros ?? 0);
        $motos  = (int)($totales->motos ?? 0);
        $total  = (int)($totales->total ?? 0);

        /**
         * ADEUDO total en el rango
         * --- CAMBIO IMPORTANTE:
         * Solo contamos adeudo de tiquetes que ya tienen hora_salida (es decir, vehículos que ya salieron).
         * De esta forma los tiquetes abiertos (sin salida) no aumentan la deuda hasta que se registre su salida.
         *
         * Calculamos por tiquete:
         *   overlap_hours = CEIL( EXTRACT(EPOCH FROM (LEAST(t.hora_salida, :to) - GREATEST(t.hora_entrada, :from))) / 3600 )
         * y multiplicamos por valor de tarifa.
         *
         * NOTA: CEIL hace que cualquier fracción de hora cuente como hora completa (mantengo tu comportamiento).
         */
        $adeudoRow = DB::selectOne("
            SELECT COALESCE(
                SUM(
                    CEIL(
                        EXTRACT(EPOCH FROM (
                            LEAST(t.hora_salida, ?::timestamptz)
                            - GREATEST(t.hora_entrada, ?::timestamptz)
                        )) / 3600.0
                    ) * COALESCE(tr.valor::numeric, 0)
                ),
                0
            )::numeric(14,2) AS total_adeudo
            FROM tiquete t
            LEFT JOIN tarifa tr ON tr.id_tarifa = t.id_tarifa
            -- IMPORTANTE: solo contar tiquetes que ya tienen salida (no contar abiertos)
            WHERE t.hora_salida IS NOT NULL
              AND LEAST(t.hora_salida, ?::timestamptz) > GREATEST(t.hora_entrada, ?::timestamptz)
        ", [
            // params: (for LEAST(t.hora_salida, :to), for GREATEST(..., :from), then reused)
            $to,   // primero en LEAST
            $from, // para GREATEST
            $to,   // para WHERE comparison
            $from
        ]);

        $totalAdeudo = (float)($adeudoRow->total_adeudo ?? 0);

        return response()->json([
            'carros' => $carros,
            'motos'  => $motos,
            'total'  => $total,
            'total_adeudo' => number_format($totalAdeudo, 2, '.', ''), // string "1234.56"
            'from' => $from->toDateString(),
            'to'   => $to->toDateString()
        ]);
    }

    /**
     * Conteos por día entre from/to
     * Devuelve un objeto { "YYYY-MM-DD": count, ... } para cada día dentro del rango
     */
    public function ingresosByDay(Request $request)
    {
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::today()->endOfDay();
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(6)->startOfDay();

        // Agrupar por día
        $rows = DB::select("
            SELECT date_trunc('day', created_at) AS day, COUNT(*) AS entradas
            FROM vehiculo
            WHERE created_at BETWEEN ? AND ?
            GROUP BY day
            ORDER BY day
        ", [$from, $to]);

        // Inicializar resultado con todos los días del rango (evita huecos)
        $out = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $out[$key] = 0;
        }

        // Rellenar con los valores obtenidos
        foreach ($rows as $r) {
            // $r->day viene como timestamp; formateamos a Y-m-d
            $k = (new Carbon($r->day))->format('Y-m-d');
            $out[$k] = (int)$r->entradas;
        }

        return response()->json($out);
    }

    /**
     * Adeudo por tipo de vehículo (carro / moto) en un rango.
     * Request: ?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Observación: igual que en summary, solo contamos tiquetes con hora_salida (no contamos abiertos).
     */
    public function adeudoByType(Request $request)
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
        $to   = $request->query('to')   ? Carbon::parse($request->query('to'))->endOfDay()     : Carbon::now()->endOfDay();

        $rows = DB::select("
            SELECT
                LOWER(COALESCE(v.tipo_vehiculo, 'otro')) AS tipo,
                COALESCE(SUM(
                    CEIL(
                        EXTRACT(EPOCH FROM (
                            LEAST(t.hora_salida, ?::timestamptz)
                            - GREATEST(t.hora_entrada, ?::timestamptz)
                        )) / 3600.0
                    ) * COALESCE(tr.valor::numeric, 0)
                ), 0)::numeric(14,2) AS adeudo
            FROM tiquete t
            LEFT JOIN vehiculo v ON v.id_vehiculo = t.id_vehiculo
            LEFT JOIN tarifa tr ON tr.id_tarifa = t.id_tarifa
            -- Solo tiquetes con salida (no contar abiertos)
            WHERE t.hora_salida IS NOT NULL
              AND LEAST(t.hora_salida, ?::timestamptz) > GREATEST(t.hora_entrada, ?::timestamptz)
            GROUP BY LOWER(COALESCE(v.tipo_vehiculo, 'otro'))
        ", [$to, $from, $to, $from]);

        // Resultado por defecto si no hay filas
        $out = ['carro' => '0.00', 'moto' => '0.00', 'otro' => '0.00'];
        foreach ($rows as $r) {
            $key = trim((string)$r->tipo);
            // aseguramos que la clave exista; si no, la agregamos
            $out[$key] = number_format((float)$r->adeudo, 2, '.', '');
        }

        return response()->json($out);
    }

    /**
     * Historial de vehiculos filtrado por rango (from/to)
     * Devuelve por cada vehículo su último tiquete y el adeudo calculado correspondiente al rango.
     *
     * Query params: ?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Lógica:
     * - Filtramos vehículos por v.created_at BETWEEN :from AND :to (así la tabla solo muestra vehículos registrados en el rango).
     * - Para cada vehículo obtenemos el último tiquete (subconsulta LATERAL).
     * - El adeudo del tiquete se calcula usando:
     *      overlap = LEAST(COALESCE(hora_salida, NOW()), :to) - GREATEST(hora_entrada, :from)
     *   Si overlap > 0 -> adeudo = CEIL(EXTRACT(EPOCH FROM overlap)/3600.0) * tarifa.valor
     *   Si overlap <= 0 -> adeudo = 0  (no cuenta fuera del rango)
     *
     * Nota: Usamos COALESCE(hora_salida, NOW()) para incluir adeudo parcial si el ticket está abierto.
     */
    public function history(Request $request)
    {
        // Parsear from/to (startOfDay/endOfDay) — por defecto últimos 7 días (consistente con otros endpoints)
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::now()->endOfDay();
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(6)->startOfDay();

        // Query: por cada vehículo, un lateral para obtener el ultimo tiquete + adeudo calculado (respetando from/to)
        $rows = DB::select("
            SELECT
                v.id_vehiculo,
                v.placa,
                v.tipo_vehiculo,
                v.created_at AS fecha_ingreso,
                COALESCE(vi.nombre, '') AS vigilante_nombre,

                -- Campos del ultimo tiquete (pueden ser NULL)
                lt.id_tiquete,
                lt.hora_entrada,
                lt.hora_salida,
                lt.id_tarifa,
                lt.tarifa_descripcion,
                lt.tarifa_valor,
                -- adeudo calculado para ese ultimo tiquete (respetando el rango from/to)
                COALESCE(lt.adeudo_en_rango, 0)::numeric(14,2) AS adeudo_calculado

            FROM vehiculo v
            LEFT JOIN vigilante vi ON vi.id_vigilante = v.id_usuario

            -- Subconsulta lateral: último tiquete del vehículo con cálculo de adeudo limitado al rango
            LEFT JOIN LATERAL (
                SELECT
                    tt.id_tiquete,
                    tt.hora_entrada,
                    tt.hora_salida,
                    tt.id_tarifa,
                    tr.descripcion AS tarifa_descripcion,
                    COALESCE(tr.valor::numeric, 0)::numeric(12,2) AS tarifa_valor,

                    -- Calculamos el adeudo que cae dentro del rango [ :from , :to ]
                    CASE
                    WHEN LEAST(COALESCE(tt.hora_salida, NOW()), ?::timestamptz)
                        > GREATEST(tt.hora_entrada, ?::timestamptz)
                    THEN
                        ( CEIL(
                            EXTRACT(EPOCH FROM (
                            LEAST(COALESCE(tt.hora_salida, NOW()), ?::timestamptz)
                            - GREATEST(tt.hora_entrada, ?::timestamptz)
                            )) / 3600.0
                        ) * COALESCE(tr.valor::numeric, 0)
                        )::numeric(14,2)
                    ELSE
                        0::numeric(14,2)
                    END AS adeudo_en_rango
                FROM tiquete tt
                LEFT JOIN tarifa tr ON tr.id_tarifa = tt.id_tarifa
                WHERE tt.id_vehiculo = v.id_vehiculo
                ORDER BY tt.hora_entrada DESC NULLS LAST
                LIMIT 1
            ) lt ON true

            -- Filtramos vehículos por la fecha de creación (registro) para que el historial sea por rango
            WHERE v.created_at BETWEEN ?::timestamptz AND ?::timestamptz

            ORDER BY v.created_at DESC
        ", [
            // Parámetros para la subconsulta lateral (to, from, to, from)
            $to,   // 1 -> LEAST(..., :to)
            $from, // 2 -> GREATEST(..., :from)
            $to,   // 3 -> LEAST(..., :to) reutilizado en cálculo
            $from, // 4 -> GREATEST(..., :from) reutilizado en cálculo

            // Parámetros para el WHERE principal (v.created_at BETWEEN from AND to)
            $from, // 5
            $to    // 6
        ]);

        // Formatear salida para JSON
        $out = array_map(function($r){
            return [
                'id_vehiculo' => $r->id_vehiculo,
                'placa' => $r->placa,
                'tipo_vehiculo' => $r->tipo_vehiculo,
                'fecha_ingreso' => (string)$r->fecha_ingreso,
                'vigilante' => $r->vigilante_nombre,
                'id_tiquete' => $r->id_tiquete ? (int)$r->id_tiquete : null,
                'hora_entrada' => $r->hora_entrada ? (string)$r->hora_entrada : null,
                'hora_salida'  => $r->hora_salida  ? (string)$r->hora_salida  : null,
                'tarifa' => [
                    'id' => $r->id_tarifa ? (int)$r->id_tarifa : null,
                    'descripcion' => $r->tarifa_descripcion ?? '',
                    'valor' => number_format((float)($r->tarifa_valor ?? 0), 2, '.', '')
                ],
                'adeudo' => number_format((float)($r->adeudo_calculado ?? 0), 2, '.', '')
            ];
        }, $rows);

        return response()->json($out);
    }

    // Puedes mantener o añadir métodos adicionales si los necesitas.
}
