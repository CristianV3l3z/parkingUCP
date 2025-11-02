<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\usuarioController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\vigilanteController;
use App\Http\Controllers\datosController;
use App\Http\Controllers\tiqueteController;
use App\Http\Controllers\pagoController;
use Illuminate\Support\Facades\Artisan;

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
Route::post('/register', [registroController::class, 'register'])->name('usuario.register');

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

Route::get('/pago/{id}', [pagoController::class, 'crearPreferencia']);

Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', [usuarioController::class, 'edit'])->name('usuario.edit');
    Route::put('/profile', [usuarioController::class, 'update'])->name('usuario.update');

    // Alias opcional: agregar name 'profile.update' apuntando a la misma ruta
    Route::put('/profile', [usuarioController::class, 'update'])->name('profile.update');
});

Route::get('/clear', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return 'Caches cleared!';
});


