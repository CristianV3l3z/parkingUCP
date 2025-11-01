<?php

namespace App\Http\Controllers;

use App\Models\registro; // mantiene tu import actual; cámbialo si tu modelo usa otra capitalización
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario; // modelo Usuario (asegúrate que existe)
use App\Http\Controllers\VigilanteController; // si lo usas en otras partes

class registroController extends Controller
{
    // -----------------------------
    // Métodos originales (no tocados)
    // -----------------------------

    // Listar registros
    public function index()
    {
        $registros = registro::with(['tiquete', 'vigilante'])->get();
        return response()->json($registros, 200);
    }

    // Crear un registro
    public function store(Request $request)
    {
        $request->validate([
            'id_tiquete' => 'required|exists:tiquete,id_tiquete',
            'id_vigilante' => 'required|exists:vigilante,id_vigilante',
            'accion' => 'required|string|max:80',
            'detalle' => 'nullable|string'
        ]);

        $registro = registro::create($request->all());
        return response()->json($registro, 201);
    }

    // Mostrar un registro por ID
    public function show($id)
    {
        $registro = registro::with(['tiquete', 'vigilante'])->findOrFail($id);
        return response()->json($registro, 200);
    }

    // Actualizar un registro
    public function update(Request $request, $id)
    {
        $registro = registro::findOrFail($id);

        $request->validate([
            'accion' => 'sometimes|string|max:80',
            'detalle' => 'nullable|string'
        ]);

        $registro->update($request->all());
        return response()->json($registro, 200);
    }

    // Eliminar un registro
    public function destroy($id)
    {
        $registro = registro::findOrFail($id);
        $registro->delete();
        return response()->json(['message' => 'Registro eliminado correctamente'], 200);
    }

    // -----------------------------
    // Métodos nuevos: Registro de usuarios (Register)
    // -----------------------------

    /**
     * Mostrar formulario de registro (GET /register)
     * Retorna resources/views/register.blade.php
     */
    public function showRegisterForm()
    {
        // Si ya está autenticado como Usuario, lo mandamos al dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('register');
    }

    /**
     * Procesar registro (POST /register)
     * Crea un registro en la tabla 'usuario' usando el modelo Usuario.
     * Acepta inputs 'email' o 'correo' y 'password'|'password_confirmation'.
     */
    public function register(Request $request)
    {
        // Normalizar: aceptar 'email' o 'correo' del form
        $email = $request->input('email', $request->input('correo'));

        // Preparar payload para validación
        $payload = [
            'nombre' => $request->input('nombre'),
            'correo' => $email,
            'telefono' => $request->input('telefono'),
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];

        // Reglas: validar 'correo' porque así está tu BD
        $validator = Validator::make($payload, [
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|max:150|unique:usuario,correo',
            'telefono' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Crear el usuario en la tabla 'usuario'
        $usuario = Usuario::create([
            'nombre' => $payload['nombre'],
            'correo' => $payload['correo'],
            'telefono' => $payload['telefono'] ?? null,
            'contrasena_hash' => Hash::make($payload['password']),
        ]);

        // Iniciar sesión del usuario (Usuario debe extender Authenticatable idealmente)
        Auth::login($usuario);
        $request->session()->regenerate();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'redirect' => route('dashboard')
            ], 201);
        }

        return redirect()->route('dashboard')->with('status', 'Registro exitoso');
    }
}
