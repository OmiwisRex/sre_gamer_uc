<?php

use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\ConfiguracionController;
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
Route::get('/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
Route::post('/usuarios/invitados', [UsuarioController::class, 'storeGuest'])->name('usuarios.guest.store');
Route::post('/usuarios/comunidad', [UsuarioController::class, 'storeCommunity'])->name('usuarios.community.store');
Route::get('/usuarios/busqueda', [UsuarioController::class, 'search'])->name('usuarios.search');
Route::get('/usuarios/{usuario}/detalle', [UsuarioController::class, 'detail'])->name('usuarios.detail');
Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
Route::get('/eventos/busqueda', [EventoController::class, 'search'])->name('eventos.search');
Route::get('/eventos/{evento}/detalle', [EventoController::class, 'detail'])->name('eventos.detail');
Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
Route::get('/dispositivos/create', [DispositivoController::class, 'create'])->name('dispositivos.create');
Route::get('/dispositivos/{dispositivo}/edit', [DispositivoController::class, 'edit'])->name('dispositivos.edit');
Route::put('/dispositivos/{dispositivo}', [DispositivoController::class, 'update'])->name('dispositivos.update');
Route::post('/dispositivos', [DispositivoController::class, 'store'])->name('dispositivos.store');
Route::get('/dispositivos/busqueda', [DispositivoController::class, 'search'])->name('dispositivos.search');
Route::get('/dispositivos/{dispositivo}/detalle', [DispositivoController::class, 'detail'])->name('dispositivos.detail');
Route::get('/dispositivos', [DispositivoController::class, 'index'])->name('dispositivos.index');
Route::view('/observaciones', 'observaciones.index')->name('observaciones.index');
Route::view('/estadisticas', 'estadisticas.index')->name('estadisticas.index');
Route::view('/auditorias', 'auditorias.index')->name('auditorias.index');
Route::view('/configuracion', 'configuracion.index')->middleware('auth')->name('configuracion.index');
Route::put('/configuracion/contrasena', [ConfiguracionController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('configuracion.password.update');
