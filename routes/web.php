<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuarioController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\vigilanteController;
use App\Http\Controllers\datosController;
use App\Http\Controllers\tiqueteController;
use App\Http\Controllers\pagoController;
use App\Http\Controllers\CheckoutProController; // <-- ¡Nuevo: Importar el controlador de pago!

/*
|--------------------------------------------------------------------------
| Web Routes (limpio)
|--------------------------------------------------------------------------
*/

// Home pública
Route::view('/', 'home')->name('home');

// Anchors (redirigen a hash en la home)
Route::get('/servicios', fn() => redirect('/#servicios'));
Route::get('/acerca', fn() => redirect('/#acerca'));
Route::get('/contacto', fn() => redirect('/#contacto'));

// Auth (usuarios normales)
Route::get('/login', [usuarioController::class, 'showLoginForm'])->name('login');
Route::post('/login', [usuarioController::class, 'login'])->name('login.post');
Route::post('/logout', [usuarioController::class, 'logout'])->name('logout');

Route::get('/register', [registroController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [registroController::class, 'register'])->name('register');

// Dashboard protegido por auth
Route::get('/dashboard', fn() => view('dashboard'))->middleware('auth')->name('dashboard');

// Rutas protegidas sólo para vigilantes (middleware 'vigilante')
Route::middleware(['vigilante'])->group(function () {
    Route::get('/ingresos', [vigilanteController::class, 'ingresos'])->name('ingresos');
    Route::get('/tiquetes', fn() => view('tiquetes'))->name('tiquetes');
    Route::get('/datos', [datosController::class, 'index'])->name('datos');
    
    // logout vigilante
    Route::post('/vigilante/logout', [vigilanteController::class, 'logout'])->name('vigilante.logout');
});

// rutas protegidas por vigilante para vistas de tarifas
Route::middleware(['vigilante'])->group(function () {
    // ...
    Route::get('/tarifas', function(){ return view('tarifas'); })->name('tarifas');
    
});

// Ruta de pago de la pasarela (la vista puede redirigir aquí o el navegador)
Route::get('/pago/{id}', [CheckoutProController::class, 'crearPreferencia'])->name('pago.iniciar'); // <-- ¡Corregido el controlador!

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [usuarioController::class, 'edit'])->name('usuario.edit');
    Route::put('/profile', [usuarioController::class, 'update'])->name('usuario.update');

    // Alias opcional: agregar name 'profile.update' apuntando a la misma ruta
    Route::put('/profile', [usuarioController::class, 'update'])->name('profile.update');
});


// Rutas temporales para la creación de vigilantes (SIN protección)
Route::get('/vigilante/crear', [vigilanteController::class, 'showCreateForm'])->name('vigilante.create.form');
Route::post('/vigilante/crear', [vigilanteController::class, 'create'])->name('vigilante.store.web');

// Mostrar formulario edición perfil
Route::get('/perfil', [App\Http\Controllers\vigilanteController::class, 'edit'])->name('perfil.edit');

// Actualizar perfil
// Cambia 'update' por 'updateProfile'
Route::put('/perfil', [App\Http\Controllers\vigilanteController::class, 'updateProfile'])->name('perfil.update');
