<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoDisposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_dispos')->insert([
            [
                'nombre' => 'Computador',
                'descripcion' => 'módulo con una silla y un PC gamer'
            ],
            [
                'nombre' => 'Consola Play',
                'descripcion' => 'sillón con televisor y Play Station'
            ],
            [
                'nombre' => 'Consola Xbox',
                'descripcion' => 'sillón con televisor y Xbox'
            ],
            [
                'nombre' => 'Televisor',
                'descripcion' => 'espacio grande con televisor multipropósito'
            ]
        ]);
    }
}
