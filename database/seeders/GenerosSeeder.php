<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GenerosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('generos')->insert([
            [
                'nombre' => 'Indefinido'
            ],
            [
                'nombre' => 'Mujer'
            ],
            [
                'nombre' => 'Hombre'
            ],
            [
                'nombre' => 'Otro'
            ]
        ]);
    }
}
