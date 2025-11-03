<?php

namespace App\Http\Controllers;

use App\Models\tarifa;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class tarifaController extends Controller
{
    public function index()
    {
        return response()->json(Tarifa::all(), 200);
    }

    public function indexView()
    {
        $vigilante = session('vigilante'); // obtenemos el vigilante logueado
        return view('tarifas', compact('vigilante'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_vehiculo' => 'required|string|max:50',
            'valor' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
            'activo' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $tarifa = Tarifa::create($data);
            DB::commit();

            return response()->json([
                'message' => 'Tarifa creada correctamente',
                'id_tarifa' => $tarifa->id_tarifa,
                'tarifa' => $tarifa
            ], 201)->header('Location', route('tarifas.show', $tarifa->id_tarifa));
        } catch (\Throwable $e) {
    DB::rollBack();
    return response()->json([
        'message' => 'Error al crear la tarifa',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ], 500);
}
    }

    public function show($id)
    {
        $tarifa = Tarifa::findOrFail($id);
        return response()->json($tarifa, 200);
    }

    public function update(Request $request, $id)
    {
        $tarifa = Tarifa::findOrFail($id);

        $data = $request->validate([
            'tipo_vehiculo' => 'sometimes|string|max:50',
            'valor' => 'sometimes|numeric|min:0',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        $tarifa->update($data);

        return response()->json([
            'message' => 'Tarifa actualizada correctamente',
            'tarifa' => $tarifa
        ], 200);
    }

    public function destroy($id)
    {
        $tarifa = Tarifa::findOrFail($id);
        $tarifa->delete();

        return response()->json([
            'message' => 'Tarifa eliminada correctamente'
        ], 200);
    }
}
