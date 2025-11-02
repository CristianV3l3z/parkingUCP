<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class usuarioController extends Controller
{
    
      // Obtener todos los usuarios
    public function index()
    {
        return response()->json(Usuario::all());
    }

    // Obtener un solo usuario por ID
    public function show($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nombre' => 'required|string|max:255',
        'correo' => 'required|email|unique:usuario,correo',
        'contrasena' => 'required|string|min:6',
        'telefono' => 'nullable|string|max:20',
    ]);

    if ($validator->fails()) {
        return response()->json(['errores' => $validator->errors()], 422);
    }

    $usuario = Usuario::create([
        'nombre' => $request->nombre,
        'correo' => $request->correo,
        'contrasena_hash' => Hash::make($request->contrasena),
        'telefono' => $request->telefono,
    ]);

    return response()->json([
        'mensaje' => 'Registro creado correctamente',
        'id_usuario' => $usuario->id_usuario,
        'usuario' => $usuario,
    ], 201);
}


    // Actualizar un usuario existente
public function updateUser(Request $request, $id)
{
    $usuario = Usuario::find($id);

    if (!$usuario) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }

    $validator = Validator::make($request->all(), [
        'nombre' => 'sometimes|required|string|max:255',
        'correo' => "sometimes|required|email|unique:usuario,correo,{$id},id_usuario",
        'contrasena' => 'sometimes|nullable|string|min:6',
        'telefono' => 'nullable|string|max:20',
    ]);

    if ($validator->fails()) {
        return response()->json(['errores' => $validator->errors()], 422);
    }

    $usuario->nombre = $request->get('nombre', $usuario->nombre);
    $usuario->correo = $request->get('correo', $usuario->correo);
    $usuario->telefono = $request->get('telefono', $usuario->telefono);

    if ($request->filled('contrasena')) {
        $usuario->contrasena_hash = Hash::make($request->contrasena);
    }

    $usuario->save();

    return response()->json([
        'mensaje' => 'Registro actualizado correctamente',
        'id_usuario' => $usuario->id_usuario,
        'usuario' => $usuario,
    ]);
}

    // Eliminar un usuario
    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['mensaje' => 'Usuario eliminado correctamente']);
    }


    //Register de usuario
    /**
     * Mostrar formulario de registro (GET /register)
     */
    public function showRegisterForm()
    {
        return view('register'); // resources/views/register.blade.php (ya la tienes)
    }

    /**
     * Procesar registro (POST /register)
     * Guarda en la tabla `usuario` usando el modelo Usuario.
     */
    public function register(Request $request)
    {
        // Validación en servidor
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'correo' => [
                'required',
                'correo',
                'max:150',
                // Ajusta el nombre de la tabla/columna si tu columna no se llama 'correo'
                Rule::unique('usuario', 'correo'),
            ],
            'telefono' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Crear usuario en la tabla 'usuario'
        $usuario = Usuario::create([
            'nombre' => $validated['nombre'],
            // En tu modelo/tabla el campo de correo se llama 'correo'
            'correo' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            // En tu tabla usas 'contrasena_hash'
            'contrasena_hash' => Hash::make($validated['password']),
        ]);

    }

    // Mostrar formulario de login (GET)
    public function showLoginForm()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        return view('login'); // resources/views/login.blade.php
    }

    // Procesar login (POST)
    public function login(Request $request)
{
    // --- Obtener email y contraseña desde cualquiera de los names que use la vista ---
    $email = $request->input('email', $request->input('correo'));
    $password = $request->input('password', $request->input('contrasena'));

    // --- Si quieres depurar rápidamente qué llega al servidor (descomenta dd) ---
    // dd($request->all()); // <-- descomenta temporalmente para ver los campos

    // --- Validación simple (reportar errores iguales a los que te salieron en pantalla) ---
    $errors = [];
    if (! $email) {
        $errors['email'] = 'The email field is required.';
    }
    if (! $password) {
        $errors['password'] = 'The password field is required.';
    }
    if (! empty($errors)) {
        return back()->withErrors($errors)->withInput();
    }

    // --- Intentar como Usuario (tabla 'usuario', columna 'correo') ---
    $usuario = Usuario::where('correo', $email)->first();

    if ($usuario) {
        // compara texto plano enviado con el hash en 'contrasena_hash'
        if (Hash::check($password, $usuario->contrasena_hash)) {
            // iniciar sesión (Usuario debe extender Authenticatable)
            Auth::login($usuario);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // el email existe pero la contraseña es incorrecta
        return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
    }

    // --- Si no es usuario, delegar a VigilanteController (si lo tienes) ---
    /** @var vigilanteController $vigilanteCtrl */
    $vigilanteCtrl = app()->make(vigilanteController::class);
    $vigilante = $vigilanteCtrl->loginViaCredentials($email, $password, $request);

    if ($vigilante) {
        return redirect()->route('ingresos');
    }

    // --- No encontrado en ninguna tabla ---
    return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
}

    // Logout (aplica para usuarios y también para limpiar sesión de vigilante)
    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        // Limpiar datos de vigilante si existieran
        $request->session()->forget(['vigilante_id', 'vigilante', 'user_type']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
    /**
 * Mostrar formulario de edición de perfil (GET /profile/edit)
 */
public function edit()
{
    // Primero intenta obtener el usuario desde el guard (si tu modelo implementa Authenticatable)
    $user = auth()->user();

    // Si por alguna razón auth()->user() no retorna el modelo, intenta buscar por id_usuario
    if (! $user || ! is_object($user)) {
        $authId = auth()->id();
        $user = Usuario::where('id_usuario', $authId)->first();
    }

    if (! $user) {
        return redirect()->route('login')->withErrors(['email' => 'Por favor inicia sesión nuevamente.']);
    }

    return view('profile_edit', compact('user'));
}

/**
 * Actualizar perfil (PUT /profile)
 */
public function update(Request $request)
{
    // Obtener usuario de forma robusta
    $user = auth()->user();
    if (! $user || ! is_object($user)) {
        $authId = auth()->id();
        $user = Usuario::where('id_usuario', $authId)->first();
    }

    if (! $user) {
        return redirect()->route('login')->withErrors(['email' => 'Usuario no autenticado.']);
    }

    // Validación
    $validator = Validator::make($request->all(), [
        'nombre' => 'required|string|max:255',
        'correo' => [
            'required',
            'email',
            'max:150',
            // Ignora el propio correo del usuario actual (usa id_usuario como PK)
            Rule::unique('usuario', 'correo')->ignore($user->id_usuario, 'id_usuario')
        ],
        'telefono' => 'nullable|string|max:30',
        'avatar' => 'nullable|image|max:2048',
        'password' => 'nullable|string|min:6|confirmed'
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    // Manejo de avatar (si la columna existe en la tabla 'usuario')
    if ($request->hasFile('avatar') && Schema::hasColumn('usuario', 'avatar')) {
        $path = $request->file('avatar')->store('avatars', 'public');

        // borrar avatar previo si existe
        if (!empty($user->avatar) && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        $user->avatar = basename($path);
    }

    // Mapear campos según tu tabla 'usuario'
    $user->nombre = $request->input('nombre', $user->nombre);
    $user->correo = $request->input('correo', $user->correo);
    $user->telefono = $request->input('telefono', $user->telefono);

    // Si envían password lo guardamos en contrasena_hash
    if ($request->filled('password')) {
        $user->contrasena_hash = Hash::make($request->input('password'));
    }

    $user->save();

    return redirect()->route('usuario.edit')->with('success', 'Perfil actualizado correctamente.');
}

}
