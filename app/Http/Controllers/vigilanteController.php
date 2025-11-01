<?php

namespace App\Http\Controllers;

use App\Models\Vigilante;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class vigilanteController extends Controller
{
    /**
     * Mostrar todos los vigilantes.
     */
    public function index()
    {
        return response()->json(Vigilante::all(), 200);
    }

    /**
     * Guardar un nuevo vigilante.
     */
    public function store(Request $request)
{
    // Validación
    $data = $request->validate([
        'nombre' => 'required|string|max:100',
        'correo' => 'required|string|email|max:150|unique:vigilante,correo',
        'contrasena' => 'required|string|min:6|confirmed',
    ]);

    DB::beginTransaction();
    try {
        // Crear vigilante
        $vigilante = Vigilante::create([
            'nombre' => $data['nombre'],
            'correo' => $data['correo'],
            'contrasena_hash' => Hash::make($data['contrasena']),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Vigilante creado correctamente',
            'id' => $vigilante->id_vigilante,
            'data' => $vigilante
        ], 201);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error al crear el vigilante',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Mostrar un vigilante específico.
     */
    public function show($id)
    {
        $vigilante = Vigilante::findOrFail($id);
        return response()->json($vigilante, 200);
    }

    /**
     * Actualizar un vigilante.
     */
    public function update(Request $request, $id){
    $vigilante = Vigilante::findOrFail($id);

    $request->validate([
        'nombre' => 'sometimes|string|max:100',
        'correo' => 'sometimes|string|email|max:150|unique:vigilante,correo,' . $id . ',id_vigilante',
        'contrasena' => 'nullable|string|min:6|confirmed',
    ]);

    $vigilante->nombre = $request->nombre ?? $vigilante->nombre;
    $vigilante->correo = $request->correo ?? $vigilante->correo;

    if ($request->filled('contrasena')) {
        $vigilante->contrasena_hash = Hash::make($request->contrasena);
    }

    $vigilante->save();

    return response()->json([
        'message' => 'Vigilante actualizado correctamente',
        'id' => $vigilante->id_vigilante,
        'data' => $vigilante
    ], 200);
}

    /**
     * Eliminar un vigilante.
     */
    public function destroy($id)
    {
        $vigilante = Vigilante::findOrFail($id);
        $vigilante->delete();

        return response()->json(['message' => 'Vigilante eliminado correctamente'], 200);
    }



    /**
     * Intenta loguear un vigilante por email/password.
     * Si ok guarda info en session y retorna el modelo. Si falla, retorna null.
     */
    public function loginViaCredentials(string $email, string $password, Request $request)
    {
        $vigilante = Vigilante::where('correo', $email)->first();

        if (! $vigilante) {
            return null;
        }

        // Verificar contraseña (campo 'contrasena_hash')
        if (! Hash::check($password, $vigilante->contrasena_hash)) {
            return null;
        }

        // Guardar datos útiles en session (no usamos Auth guard para vigilantes)
        $request->session()->put('vigilante_id', $vigilante->id_vigilante);
        $request->session()->put('vigilante', [
            'id' => $vigilante->id_vigilante,
            'nombre' => $vigilante->nombre,
            'correo' => $vigilante->correo,
        ]);
        $request->session()->put('user_type', 'vigilante');

        return $vigilante;
    }

    // Opcional: método que devuelve la vista ingresos protegido
    public function ingresos()
{
    if (! session('vigilante_id')) {
        return redirect()->route('login');
    }

    // Pasamos el vigilante logueado a la vista
    $vigilante = session('vigilante');
    return view('ingresos', compact('vigilante'));
}

/**
 * Cerrar sesión (vigilante) — limpiar session y redirigir al login.
 */
public function logout(Request $request)
{
    // Eliminar claves específicas de vigilante
    $request->session()->forget(['vigilante', 'vigilante_id', 'user_type']);

    // Si usas Auth para usuarios también, puedes desloguear:
    // \Illuminate\Support\Facades\Auth::logout();

    // Opcional: invalidar la sesión por seguridad
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
}

}
