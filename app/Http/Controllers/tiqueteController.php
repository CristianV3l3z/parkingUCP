<?php

namespace App\Http\Controllers;

use App\Models\tiquete;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class tiqueteController extends Controller
{
    /**
     * Listar todos los tiquetes
     */
/// Listar: sólo tiquetes "visibles" (activo = true). Orden descendente para que el nuevo aparezca primero.
    public function index()
    {
        $tiquetes = tiquete::with(['vehiculo','vigilante','tarifa'])
                    ->where('activo', true)            // solo visibles
                    ->orderBy('created_at', 'desc')    // el más reciente primero
                    ->get()
                    ->map(function($t){
                        // exponer nombre de vigilante (fallbacks si no viene en la relación)
                        $t->vigilante_nombre = $t->vigilante ? ($t->vigilante->nombre ?? null) : null;
                        return $t;
                    });

        return response()->json($tiquetes, 200);
    }


    public function indexView()
    {
        $vigilante = session('vigilante'); // obtenemos el vigilante logueado
        return view('tiquetes', compact('vigilante'));
    }


    /**
     * Crear un nuevo tiquete
     */
public function store(Request $request)
{
    $data = $request->validate([
        'id_vehiculo'   => 'required|exists:vehiculo,id_vehiculo',
        'id_vigilante'  => 'required|exists:vigilante,id_vigilante',
        'id_tarifa'     => 'required|exists:tarifa,id_tarifa',
        'hora_entrada'  => 'required|date',
        'hora_salida'   => 'nullable|date|after_or_equal:hora_entrada',
        'estado'        => 'required|integer',
        'observaciones' => 'nullable|string',
    ]);

    // Agregar codigo uuid
    $data['codigo_uuid'] = (string) Str::uuid();

    // Intentar obtener nombre del vigilante y guardarlo en el registro
    try {
        $v = \App\Models\Vigilante::find($data['id_vigilante']);
        if ($v) {
            // priorizar nombre_completo si existe
            $nombre = $v->nombre_completo ?? null;
            if (!$nombre) {
                $parts = [];
                if (!empty($v->nombre)) $parts[] = $v->nombre;
                if (!empty($v->apellido)) $parts[] = $v->apellido;
                $nombre = count($parts) ? implode(' ', $parts) : ($v->nombre ?? null);
            }
            $data['vigilante_nombre'] = $nombre;
        }
    } catch (\Throwable $e) {
        // no romper si hay problema al leer vigilante; seguir sin nombre
        Log::warning('No se pudo leer vigilante para nombre: '.$e->getMessage());
    }

    DB::beginTransaction();
    try {
        $tiquete = tiquete::create($data);

        DB::commit();

        // devolver el recurso creado con relaciones
        $tiquete->load(['vehiculo', 'vigilante', 'tarifa']);

        return response()->json([
            'message' => 'Tiquete creado correctamente',
            'id_tiquete' => $tiquete->id_tiquete,
            'tiquete' => $tiquete
        ], 201)->header('Location', route('tiquetes.show', $tiquete->id_tiquete));
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Error creando tiquete: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'message' => 'Error al crear el tiquete',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Mostrar un tiquete específico
     */
    public function show($id)
    {
        $tiquete = tiquete::with(['vehiculo', 'vigilante', 'tarifa'])->findOrFail($id);
        return response()->json($tiquete, 200);
    }

    /**
     * Actualizar un tiquete
     *
     * - Si llega 'estado' lo interpretamos: 0 => cerrar (hora_salida = now), 1 => reabrir (hora_salida = null)
     * - Devolvemos el tiquete con relaciones cargadas para que el front muestre directamente el registro actualizado.
     */
    public function update(Request $request, $id)
    {
        $tiquete = tiquete::findOrFail($id);

        $data = $request->validate([
            'id_vehiculo'   => 'sometimes|exists:vehiculo,id_vehiculo',
            'id_vigilante'  => 'sometimes|exists:vigilante,id_vigilante',
            'id_tarifa'     => 'sometimes|exists:tarifa,id_tarifa',
            'hora_entrada'  => 'sometimes|date',
            'hora_salida'   => 'nullable|date|after_or_equal:hora_entrada',
            'estado'        => 'sometimes|integer',
            'observaciones' => 'nullable|string',
        ]);

        // Si nos mandan 'estado' actuamos inmediatamente (cerrar / reabrir)
        if (array_key_exists('estado', $data)) {
            $newState = (int) $data['estado'];
            if ($newState === 0) {
                // cerrar: guardar hora_salida si no existe
                $tiquete->hora_salida = $tiquete->hora_salida ?? now();
                $tiquete->estado = 0;
            } elseif ($newState === 1) {
                // reabrir: quitar hora_salida y poner estado activo
                $tiquete->hora_salida = null;
                $tiquete->estado = 1;
            }
            // quitar del array porque ya lo procesamos
            unset($data['estado']);
        }

        // aplicar el resto de cambios si vienen
        if (!empty($data)) {
            $tiquete->fill($data);
        }

        $tiquete->save();

        // recargar relaciones para devolver información completa al front
        $tiquete->load(['vehiculo','vigilante','tarifa']);

        return response()->json([
            'message' => 'Tiquete actualizado correctamente',
            'tiquete' => $tiquete
        ], 200);
    }

    /**
     * Eliminar un tiquete
     */
    // eliminar -> ACTUALMENTE DESACTIVAR (soft hide)
    public function destroy($id)
    {
        $t = tiquete::findOrFail($id);

        DB::beginTransaction();
        try {
            // marcar tiquete como inactivo
            $t->activo = false;
            $t->save();

            // opcional: desactivar el vehiculo relacionado para que desaparezca de ingresos
            if ($t->vehiculo) {
                $veh = $t->vehiculo;
                $veh->activo = false;
                $veh->save();
            }

            DB::commit();
            return response()->json(['message'=>'Tiquete desactivado correctamente'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message'=>'Error al desactivar tiquete','error'=>$e->getMessage()], 500);
        }
    }

    // reactivar
    public function activate($id)
    {
        $t = tiquete::findOrFail($id);

        DB::beginTransaction();
        try {
            $t->activo = true;
            $t->save();

            // reactivar vehículo relacionado también
            if ($t->vehiculo) {
                $veh = $t->vehiculo;
                $veh->activo = true;
                $veh->save();
            }

            DB::commit();
            return response()->json(['message'=>'Tiquete reactivado correctamente'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message'=>'Error reactivando','error'=>$e->getMessage()], 500);
        }
    }


    public function close($id, Request $request)
{
    $t = \App\Models\tiquete::findOrFail($id);

    // si ya está cerrado
    if ($t->hora_salida) {
        return response()->json(['message' => 'Tiquete ya cerrado'], 200);
    }

    $t->hora_salida = now(); // usa timezone de app
    $t->estado = 0; // asumiendo 0 = cerrado
    $t->save();

    return response()->json(['message' => 'Tiquete cerrado', 'data' => $t], 200);
}

public function reactivate($id, Request $request)
{
    $t = \App\Models\tiquete::findOrFail($id);

    // solo reactivar si estaba cerrado
    if (!$t->hora_salida) {
        return response()->json(['message' => 'Tiquete ya activo'], 200);
    }

    $t->hora_salida = null;
    $t->estado = 1; // asumiendo 1 = activo
    $t->save();

    return response()->json(['message' => 'Tiquete reactivado', 'data' => $t], 200);
}


}
