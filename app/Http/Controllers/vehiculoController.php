<?php

namespace App\Http\Controllers;

use App\Models\vehiculo;
use App\Models\tarifa;
use App\Models\tiquete;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class vehiculoController extends Controller
{
    

public function index()
{
    $vehiculos = vehiculo::with('tarifa')
                  ->where('activo', true)
                  ->get()
                  ->map(function($v) {
                      // formato de fecha, adeudo, etc. (tu lógica actual)
                      // ... tu código para created_local, adeudo, valor_tarifa ...
                      return $v;
                  });

    return response()->json($vehiculos, 200);
}



public function store(Request $request)
{
    $data = $request->validate([
        'id_usuario'    => 'nullable|exists:usuario,id_usuario',
        'placa'         => 'required|string|max:30',
        'tipo_vehiculo' => 'required|string|max:50',
        'marca'         => 'nullable|string|max:100',
        'id_tarifa'     => 'nullable|exists:tarifa,id_tarifa',
        'descripcion'   => 'nullable|string',
    ]);

    // Normaliza
    $data['placa'] = strtoupper(trim($data['placa']));

    // Determinar vigilante (id_usuario) si no viene
    if (empty($data['id_usuario'])) {
        $idVig = null;
        if (Auth::check()) {
            $idVig = Auth::user()->id_vigilante ?? Auth::id();
        }
        if (!$idVig && session()->has('vigilante')) {
            $v = session('vigilante');
            $idVig = $v['id_vigilante'] ?? $v['id'] ?? $v['id_usuario'] ?? null;
        }
        if ($idVig) $data['id_usuario'] = $idVig;
    }

    DB::beginTransaction();
    try {
        // buscar vehiculo por placa (case-insensitive)
        $veh = vehiculo::whereRaw('upper(placa) = ?', [strtoupper($data['placa'])])->first();

        if ($veh) {
            if ($veh->activo) {
                // ya existe activo -> rechazamos
                DB::rollBack();
                return response()->json(['message' => 'Ya existe un vehículo activo con esta placa.'], 409);
            }

            // reactiva el registro inactivo: actualiza campos y marca activo
            $veh->tipo_vehiculo = $data['tipo_vehiculo'];
            $veh->marca = $data['marca'] ?? $veh->marca;
            $veh->id_tarifa = $data['id_tarifa'] ?? $veh->id_tarifa;
            $veh->descripcion = $data['descripcion'] ?? $veh->descripcion;
            $veh->id_usuario = $data['id_usuario'] ?? $veh->id_usuario;
            $veh->activo = true;
            // no cambiamos created_at (es histórico); sí actualizamos updated_at
            $veh->save();

            // Crear un tiquete nuevo manualmente que registre la nueva entrada
            $tiquete = tiquete::create([
                'codigo_uuid'   => (string) Str::uuid(),
                'id_vehiculo'   => $veh->id_vehiculo,
                'id_vigilante'  => $data['id_usuario'] ?? null,
                'id_tarifa'     => $veh->id_tarifa ?? $data['id_tarifa'] ?? null,
                'hora_entrada'  => now(),
                'hora_salida'   => null,
                'estado'        => 1,
                'observaciones' => $data['descripcion'] ?? 'Reactivado - nueva entrada'
            ]);

            DB::commit();

            $veh->load(['tarifa','vigilante']);
            return response()->json([
                'message' => 'Vehículo reactivado y tiquete creado',
                'vehiculo' => $veh,
                'tiquete' => $tiquete
            ], 200);
        }

        // No existe: creamos vehículo (y creamos tiquete manualmente)
        $veh = vehiculo::create($data);

        DB::commit();
        return response()->json([
            'message' => 'Vehículo creado correctamente',
            'id'      => $veh->id_vehiculo,
            'data'    => $veh
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Error store vehiculo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
        return response()->json(['message' => 'Error al crear/activar vehículo','error'=>$e->getMessage()], 500);
    }
}


    public function show($id)
    {
        $vehiculo = vehiculo::with(['usuario', 'tarifa'])->findOrFail($id);
        return response()->json($vehiculo, 200);
    }

    public function update(Request $request, $id)
    {
        $vehiculo = vehiculo::findOrFail($id);

        if ($request->has('placa')) {
            $request->merge(['placa' => strtoupper($request->input('placa'))]);
        }

        $data = $request->validate([
            'id_usuario'    => 'sometimes|exists:usuario,id_usuario',
            'placa'         => 'sometimes|string|max:30|unique:vehiculo,placa,' . $id . ',id_vehiculo',
            'tipo_vehiculo' => 'sometimes|string|max:50',
            'marca'         => 'nullable|string|max:100',
            'id_tarifa'     => 'sometimes|exists:tarifa,id_tarifa',
            'descripcion'   => 'nullable|string',
        ]);

        if (empty($data['id_tarifa']) && !empty($data['tipo_vehiculo'])) {
            $t = tarifa::where('tipo_vehiculo', $data['tipo_vehiculo'])->where('activo', true)->first();
            if ($t) $data['id_tarifa'] = $t->id_tarifa;
        }

        $vehiculo->update($data);

        return response()->json([
            'message' => 'Vehículo actualizado correctamente',
            'id'      => $vehiculo->id_vehiculo,
            'data'    => $vehiculo
        ], 200);
    }

    // Eliminar por completo el vehículo (no solo desactivar)
    public function destroy($id)
    {
        $vehiculo = vehiculo::findOrFail($id);
        $vehiculo->delete();    
        return response()->json([
            'message' => 'Vehículo eliminado correctamente'
        ], 200);
    }

}
