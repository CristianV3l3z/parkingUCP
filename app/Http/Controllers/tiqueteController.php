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
    public function index()
    {
        $tiquetes = tiquete::with(['vehiculo','vigilante','tarifa'])
            ->where('activo', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($t){
                // Mostrar nombre del vigilante con prioridad:
                // 1. Campo 'vigilante_nombre' (almacenado en la BD)
                // 2. Campo 'nombre_completo' del modelo
                // 3. Concatenar nombre + apellido si existen
                if (!empty($t->vigilante_nombre)) {
                    $t->nombre_mostrado = $t->vigilante_nombre;
                } elseif ($t->vigilante) {
                    if (!empty($t->vigilante->nombre_completo)) {
                        $t->nombre_mostrado = $t->vigilante->nombre_completo;
                    } else {
                        $parts = [];
                        if (!empty($t->vigilante->nombre)) $parts[] = $t->vigilante->nombre;
                        if (!empty($t->vigilante->apellido)) $parts[] = $t->vigilante->apellido;
                        $t->nombre_mostrado = count($parts) ? implode(' ', $parts) : null;
                    }
                } else {
                    $t->nombre_mostrado = null;
                }
                return $t;
            });

        return response()->json($tiquetes, 200);
    }


    public function indexView()
    {
        $vigilante = session('vigilante');
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

        $data['codigo_uuid'] = (string) Str::uuid();

        // Buscar y guardar el nombre del vigilante
        try {
            $v = \App\Models\Vigilante::find($data['id_vigilante']);
            if ($v) {
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
            Log::warning('No se pudo leer vigilante para nombre: '.$e->getMessage());
        }

        DB::beginTransaction();
        try {
            $tiquete = tiquete::create($data);
            DB::commit();

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


    public function show($id)
    {
        $tiquete = tiquete::with(['vehiculo', 'vigilante', 'tarifa'])->findOrFail($id);
        return response()->json($tiquete, 200);
    }


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

        if (array_key_exists('estado', $data)) {
            $newState = (int) $data['estado'];
            if ($newState === 0) {
                $tiquete->hora_salida = $tiquete->hora_salida ?? now();
                $tiquete->estado = 0;
            } elseif ($newState === 1) {
                $tiquete->hora_salida = null;
                $tiquete->estado = 1;
            }
            unset($data['estado']);
        }

        if (!empty($data)) {
            $tiquete->fill($data);
        }

        $tiquete->save();
        $tiquete->load(['vehiculo','vigilante','tarifa']);

        return response()->json([
            'message' => 'Tiquete actualizado correctamente',
            'tiquete' => $tiquete
        ], 200);
    }


    public function destroy($id)
    {
        $t = tiquete::findOrFail($id);

        DB::beginTransaction();
        try {
            $t->activo = false;
            $t->save();

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

    public function activate($id)
    {
        $t = tiquete::findOrFail($id);

        DB::beginTransaction();
        try {
            $t->activo = true;
            $t->save();

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

        if ($t->hora_salida) {
            return response()->json(['message' => 'Tiquete ya cerrado'], 200);
        }

        $t->hora_salida = now();
        $t->estado = 0;
        $t->save();

        return response()->json(['message' => 'Tiquete cerrado', 'data' => $t], 200);
    }

    public function reactivate($id, Request $request)
    {
        $t = \App\Models\tiquete::findOrFail($id);

        if (!$t->hora_salida) {
            return response()->json(['message' => 'Tiquete ya activo'], 200);
        }

        $t->hora_salida = null;
        $t->estado = 1;
        $t->save();

        return response()->json(['message' => 'Tiquete reactivado', 'data' => $t], 200);
    }
}
