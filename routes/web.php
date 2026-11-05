<?php

use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\DispositivoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('index');

Route::view('/login', 'sesion.index')->name('sesion.login');
Route::view('/recuperar', 'sesion.recuperar')->name('sesion.recuperar');

Route::get('/adminsala', [AdministracionController::class, 'index'])->name('administracion.index');
Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuarios.create');
Route::post('/usuarios/invitados', [UsuarioController::class, 'storeGuest'])->name('usuarios.guest.store');
Route::post('/usuarios/comunidad', [UsuarioController::class, 'storeCommunity'])->name('usuarios.community.store');
Route::view('/usuarios', 'usuarios.index')->name('usuarios.index');
Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
Route::view('/eventos', 'eventos.index')->name('eventos.index');
Route::get('/dispositivos/create', [DispositivoController::class, 'create'])->name('dispositivos.create');
Route::post('/dispositivos', [DispositivoController::class, 'store'])->name('dispositivos.store');
Route::view('/dispositivos', 'dispositivos.index')->name('dispositivos.index');
Route::view('/observaciones', 'observaciones.index')->name('observaciones.index');
Route::view('/estadisticas', 'estadisticas.index')->name('estadisticas.index');
Route::view('/auditorias', 'auditorias.index')->name('auditorias.index');
Route::view('/configuracion', 'configuracion.index')->name('configuracion.index');
