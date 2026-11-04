<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('index');

Route::view('/login', 'sesion.index')->name('sesion.login');
Route::view('/recuperar', 'sesion.recuperar')->name('sesion.recuperar');

Route::view('/adminsala', 'administracion.index')->name('administracion.index');
Route::view('/usuarios/create', 'usuarios.create')->name('usuarios.create');
Route::view('/usuarios', 'usuarios.index')->name('usuarios.index');
Route::view('/eventos/create', 'eventos.create')->name('eventos.create');
Route::view('/eventos', 'eventos.index')->name('eventos.index');
Route::view('/dispositivos/create', 'dispositivos.create')->name('dispositivos.create');
Route::view('/dispositivos', 'dispositivos.index')->name('dispositivos.index');
Route::view('/observaciones', 'observaciones.index')->name('observaciones.index');
Route::view('/estadisticas', 'estadisticas.index')->name('estadisticas.index');
Route::view('/auditorias', 'auditorias.index')->name('auditorias.index');
Route::view('/configuracion', 'configuracion.index')->name('configuracion.index');
