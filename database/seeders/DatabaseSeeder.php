<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AccionesSeeder::class,
            EstadosSeeder::class,
            GenerosSeeder::class,
            RolsSeeder::class,
            TipoDisposSeeder::class
        ]);
    }
}
