<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('rols')->insert([
            [
                'nombre' => 'Estudiante',
                'descripcion' => 'persona que estudia activamente, tiene código de estudiante'
            ],
            [
                'nombre' => 'Egresado',
                'descripcion' => 'quien estudió en la institución y logró culminar con éxito'
            ],
            [
                'nombre' => 'Expulsado',
                'descripcion' => 'quien fué estudiante pero salió sin graduarse'
            ],
            [
                'nombre' => 'Profesor',
                'descripcion' => 'trabajador que da clases en la institución'
            ],
            [
                'nombre' => 'Trabajador',
                'descripcion' => 'persona que hace algún profesional en la comunidad (No profesor)'
            ],
            [
                'nombre' => 'Visitante',
                'descripcion' => 'persona que no pertenece a la comunidad Unicatólica'
            ],
            [
                'nombre' => 'Administrador',
                'descripcion' => 'administrador básico que maneja la sala gamer'
            ],
            [
                'nombre' => 'Master',
                'descripcion' => 'administrador con máximo nivel de permisos'
            ]
        ]);
    }
}
