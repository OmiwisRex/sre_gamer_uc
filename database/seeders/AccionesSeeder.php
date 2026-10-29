<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('acciones')->insert([
            [
                'nombre' => 'Creó Asociación',
                'descripcion' => 'ha creado un nuevo registro de asociación'
            ],
            [
                'nombre' => 'Creó Reserva',
                'descripcion' => 'ha creado un nuevo registro de reserva'
            ]
        ]);
    }
}
