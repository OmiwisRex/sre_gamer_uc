<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MasterAdminSeeder extends Seeder
{
    public function run(): void
    {
        $masterAdmin = config('services.master_admin');

        foreach (['nombres', 'apellidos', 'cedula', 'email', 'password'] as $requiredKey) {
            if (! is_string($masterAdmin[$requiredKey] ?? null) || trim($masterAdmin[$requiredKey]) === '') {
                throw new RuntimeException('Configura MASTER_ADMIN_'.strtoupper($requiredKey).' antes de ejecutar MasterAdminSeeder.');
            }
        }

        if (
            mb_strlen($masterAdmin['nombres']) > 32
            || mb_strlen($masterAdmin['apellidos']) > 32
            || mb_strlen($masterAdmin['cedula']) > 24
            || mb_strlen($masterAdmin['email']) > 128
            || mb_strlen($masterAdmin['password']) < 6
            || filter_var($masterAdmin['email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new RuntimeException('Verifica los datos MASTER_ADMIN; la contraseña requiere al menos 6 caracteres.');
        }

        DB::transaction(function () use ($masterAdmin): void {
            DB::table('generos')->insertOrIgnore(['nombre' => 'Indefinido']);
            DB::table('estados')->insertOrIgnore([
                'nombre' => 'Activo',
                'descripcion' => 'indica que el registro está funcional',
            ]);
            DB::table('rols')->insertOrIgnore([
                'nombre' => 'Master',
                'descripcion' => 'administrador con máximo nivel de permisos',
            ]);

            $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id');
            $activeStateId = DB::table('estados')->where('nombre', 'Activo')->value('id');
            $masterRoleId = DB::table('rols')->where('nombre', 'Master')->value('id');

            if ($genderId === null || $activeStateId === null || $masterRoleId === null) {
                throw new RuntimeException('Ejecuta primero los seeders de géneros, estados y roles.');
            }

            if (DB::table('usuarios')->where('cedula', $masterAdmin['cedula'])->exists()) {
                throw new RuntimeException('La cédula configurada para el Master ya está registrada.');
            }

            if (DB::table('sesiones')->where('email', $masterAdmin['email'])->exists()) {
                throw new RuntimeException('El correo configurado para el Master ya está registrado.');
            }

            $usuarioId = DB::table('usuarios')->insertGetId([
                'nombres' => $masterAdmin['nombres'],
                'apellidos' => $masterAdmin['apellidos'],
                'cedula' => $masterAdmin['cedula'],
                'codigo' => 'MST-'.Str::upper(Str::random(20)),
                'year_nace' => 0,
                'genero_id' => $genderId,
                'estado_id' => $activeStateId,
                'rol_id' => $masterRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sesiones')->insert([
                'usuario_id' => $usuarioId,
                'email' => $masterAdmin['email'],
                'password' => Hash::make($masterAdmin['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
