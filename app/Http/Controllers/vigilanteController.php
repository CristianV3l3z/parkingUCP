<?php

namespace App\Http\Controllers;

use App\Models\Vigilante;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class vigilanteController extends Controller
{
    // Listar todos los vigilantes
    public function index()
    {
        return response()->json(Vigilante::all(), 200);
    }

    // Crear nuevo vigilante
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|max:150|unique:vigilante,correo',
            'contrasena' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $vigilante = Vigilante::create([
                'nombre' => $data['nombre'],
                'correo' => $data['correo'],
                // almacenamos en la columna 'contrasena_hash' tal como tu modelo usa
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
            Log::error('Error creando vigilante: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error al crear el vigilante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Mostrar vigilante
    public function show($id)
    {
        $vigilante = Vigilante::findOrFail($id);
        return response()->json($vigilante, 200);
    }

    // Actualizar vigilante
    public function update(Request $request, $id)
    {
        $vigilante = Vigilante::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'correo' => [
                'sometimes','email','max:150',
                Rule::unique('vigilante','correo')->ignore($id, 'id_vigilante')
            ],
            'contrasena' => 'nullable|string|min:6|confirmed',
        ]);

        if (isset($data['nombre'])) $vigilante->nombre = $data['nombre'];
        if (isset($data['correo'])) $vigilante->correo = $data['correo'];
        if (!empty($data['contrasena'])) {
            $vigilante->contrasena_hash = Hash::make($data['contrasena']);
        }

        $vigilante->save();

        return response()->json([
            'message' => 'Vigilante actualizado correctamente',
            'id' => $vigilante->id_vigilante,
            'data' => $vigilante
        ], 200);
    }

    // Eliminar vigilante
    public function destroy($id)
    {
        $vigilante = Vigilante::findOrFail($id);
        $vigilante->delete();

        return response()->json(['message' => 'Vigilante eliminado correctamente'], 200);
    }

    /**
     * Login del vigilante (acción HTTP). 
     * Acepta Request y retorna JSON (no usar la firma de tres parámetros).
     */
    public function login(Request $request)
    {
        $creds = $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required|string'
        ]);

        $vigilante = Vigilante::where('correo', $creds['correo'])->first();

        if (! $vigilante || ! Hash::check($creds['contrasena'], $vigilante->contrasena_hash)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Guardar en session (si trabajas con sesión en esta app)
        $request->session()->put('vigilante_id', $vigilante->id_vigilante);
        $request->session()->put('vigilante', [
            'id' => $vigilante->id_vigilante,
            'nombre' => $vigilante->nombre,
            'correo' => $vigilante->correo,
        ]);
        $request->session()->put('user_type', 'vigilante');

        return response()->json(['message'=>'Login exitoso','vigilante'=>$vigilante], 200);
    }

    // Vista ingresos (protegida por sesión)
    public function ingresos(Request $request)
    {
        if (! $request->session()->has('vigilante_id')) {
            return redirect()->route('login');
        }

        $vigilante = $request->session()->get('vigilante');
        return view('ingresos', compact('vigilante'));
    }

    // Logout vigilante
    public function logout(Request $request)
    {
        $request->session()->forget(['vigilante', 'vigilante_id', 'user_type']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }


    //funciones temporales para crear vigilantes desde la web
    // --- Métodos para el Formulario Web Temporal ---

    /**
     * Muestra el formulario de creación de vigilante.
     */
    public function showCreateForm()
    {
        // Se asume que la vista estará en 'vigilante/create.blade.php'
        return view('create');
    }

    /**
     * Procesa la solicitud POST del formulario web.
     */
    public function create(Request $request)
    {
        // Reutilizamos la validación del método store
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|max:150|unique:vigilante,correo',
            // Aseguramos que el nombre de campo sea 'contrasena' para la validación 'confirmed'
            'contrasena' => 'required|string|min:6|confirmed', 
        ]);

        DB::beginTransaction();
        try {
            Vigilante::create([
                'nombre' => $data['nombre'],
                'correo' => $data['correo'],
                // Hashing tal como lo haces en el método store
                'contrasena_hash' => Hash::make($data['contrasena']),
            ]);

            DB::commit();

            // Redirigir de vuelta con mensaje de éxito (Web response)
            return redirect()->back()->with('success', 'Vigilante creado exitosamente. Ya puedes eliminar esta vista.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creando vigilante (Web): '.$e->getMessage());
            // Redirigir de vuelta con errores (Web response)
            return redirect()->back()->withInput()->withErrors(['error' => 'Error interno: No se pudo crear el vigilante.']);
        }
    }


}
