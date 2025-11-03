<?php

use App\Http\Controllers\usuarioController;
use App\Http\Controllers\vigilanteController;
use App\Http\Controllers\vehiculoController;
use App\Http\Controllers\tarifaController;
use App\Http\Controllers\tiqueteController;
use App\Http\Controllers\registroController;
use App\Http\Controllers\datosController;
use App\Http\Controllers\CheckoutProController; // <-- Movido aquí

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('usuario')->group(function () {

    // Obtener todos los usuarios
    Route::get('/', [usuarioController::class, 'index'])->name('usuario.index');

    // Obtener un usuario por ID
    Route::get('/{id}', [usuarioController::class, 'show'])->name('usuario.show');

    // Crear nuevo usuario
    Route::post('/', [usuarioController::class, 'store'])->name('usuario.store');

    // Actualizar usuario existente
    Route::put('/{id}', [usuarioController::class, 'update'])->name('usuario.update');
    Route::patch('/{id}', [usuarioController::class, 'update'])->name('usuario.update.partial');

    // Eliminar usuario
    Route::delete('/{id}', [usuarioController::class, 'destroy'])->name('usuario.destroy');

});

// Prefijo "vigilante"
Route::prefix('vigilante')->group(function () {
    // Listar todos
    Route::get('/', [vigilanteController::class, 'index'])->name('vigilante.index');

    // Crear nuevo
    Route::post('/', [vigilanteController::class, 'store'])->name('vigilante.store');

    // Mostrar uno específico
    Route::get('/{id}', [vigilanteController::class, 'show'])->name('vigilante.show');

    // Actualizar (PUT o PATCH)
    Route::put('/{id}', [vigilanteController::class, 'update'])->name('vigilante.update');
    Route::patch('/{id}', [vigilanteController::class, 'update']);

    // Eliminar
    Route::delete('/{id}', [vigilanteController::class, 'destroy'])->name('vigilante.destroy');
});

Route::prefix('vehiculo')->group(function () {
    //Listar todos los vehiculos
    Route::get('/', [vehiculoController::class, 'index'])->name('vehiculo.index');

    //Crear nuevo vehiculo
    Route::post('/', [vehiculoController::class, 'store'])->name('vehiculo.store');
    
    //Mostrar un vehiculo especifico
    Route::get('/{id}', [vehiculoController::class, 'show'])->name('vehiculo.show');
    
    //Actualizar un vehiculo
    Route::put('/{id}', [vehiculoController::class, 'update'])->name('vehiculo.update');
    
    //Eliminar un vehiculo
    Route::put('/{id}', [vehiculoController::class, 'update']);
    
    //Eliminar un vehiculo
    Route::delete('/{id}', [vehiculoController::class, 'destroy'])->name('vehiculo.destroy');
});


Route::prefix('tarifa')->group(function () {
    //Listar todas las tarifas
    Route::get('/', [tarifaController::class, 'index'])->name('tarifas.index');
    
    //Crear nueva tarifa
    Route::post('/', [tarifaController::class, 'store'])->name('tarifas.store');
    
    //Mostrar una tarifa especifica
    Route::get('/{id}', [tarifaController::class, 'show'])->name('tarifas.show');
    
    //Actualizar una tarifa
    Route::put('/{id}', [tarifaController::class, 'update'])->name('tarifas.update');
    
    //Eliminar una tarifa
    Route::patch('/{id}', [tarifaController::class, 'update']);

    //Eliminar una tarifa
    Route::delete('/{id}', [tarifaController::class, 'destroy'])->name('tarifas.destroy');
});


// Tiquetes
Route::prefix('tiquete')->group(function () {
    Route::get('/', [tiqueteController::class, 'index'])->name('tiquetes.index');
    Route::post('/', [tiqueteController::class, 'store'])->name('tiquetes.store');
    Route::get('/{id}', [tiqueteController::class, 'show'])->name('tiquetes.show');
    Route::put('/{id}', [tiqueteController::class, 'update'])->name('tiquetes.update');
    Route::patch('/{id}', [tiqueteController::class, 'update']);
    // DELETE se usa para desactivar (no eliminar físicamente)
    Route::delete('/{id}', [tiqueteController::class, 'destroy'])->name('tiquetes.destroy');
    // Reactivar
    Route::patch('/{id}/activate', [tiqueteController::class, 'activate'])->name('tiquetes.activate');
});


Route::prefix('registro')->group(function () {
    // Listar todos los registros
    Route::get('/', [registroController::class, 'index']);
    
    // Crear un nuevo registro
    Route::post('/', [registroController::class, 'store']);
    
    // Mostrar un registro específico
    Route::get('/{id}', [registroController::class, 'show']);
    
    // Actualizar un registro
    Route::put('/{id}', [registroController::class, 'update']);
    
    // Permitir también PATCH para actualizaciones parciales
    Route::patch('/{id}', [registroController::class, 'update']);

    // Eliminar un registro
    Route::delete('/{id}', [registroController::class, 'destroy']);
});


Route::prefix('datos')->group(function () {
    // Resumen general
    Route::get('/summary', [datosController::class, 'summary']);
    
    // Ingresos por día
    Route::get('/ingresos', [datosController::class, 'ingresosByDay']);
    
    // Adeudo por tipo de vehículo
    Route::get('/adeudo_by_type', [datosController::class, 'adeudoByType']);
    
    // Historial de registros
    Route::get('/history', [datosController::class, 'history']);
});


// Rutas de Mercado Pago
Route::post('/checkout/create', [CheckoutProController::class, 'crearPreferencia']);
Route::post('/checkout/{id}/create', [CheckoutProController::class, 'crearPreferencia']);
Route::post('/checkout/webhook', [CheckoutProController::class, 'webhook']);
Route::get('/checkout/status/{id_tiquete}', [CheckoutProController::class, 'status']);
