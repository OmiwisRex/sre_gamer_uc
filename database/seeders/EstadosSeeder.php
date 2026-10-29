<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estados')->insert([
            [
                'nombre' => 'Activo',
                'descripcion' => 'indica que el registro está funcional'
            ],
            [
                'nombre' => 'Bloqueado',
                'descripcion' => 'el registro no debería ser tenido en cuenta en muchos casos'
            ],
            [
                'nombre' => 'Eliminado',
                'descripcion' => 'en lugar de Delete en SQL, se pone este estado'
            ]
        ]);
    }
}
