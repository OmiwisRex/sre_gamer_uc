<?php

namespace Tests\Feature;

use App\Mail\UsuarioAccesoMail;
use App\Models\Sesion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UsuarioEditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_edit_form_loads_existing_user_data_and_related_options(): void
    {
        $stateId = $this->createState('Activo');
        $genderId = $this->createGender('Mujer');
        $roleId = $this->createRole('Estudiante');
        $usuarioId = $this->createUsuario($stateId, $genderId, $roleId);
        $this->createSession($usuarioId, 'ana@example.test');

        $this->get(route('usuarios.edit', $usuarioId))
            ->assertOk()
            ->assertSee('action="'.route('usuarios.update', $usuarioId).'"', false)
            ->assertSee('value="Ana"', false)
            ->assertSee('value="123456789"', false)
            ->assertSee('<option value="'.$stateId.'" selected>Activo</option>', false)
            ->assertSee('<option value="'.$genderId.'" selected>Mujer</option>', false)
            ->assertSee('<option value="'.$roleId.'" selected>Estudiante</option>', false)
            ->assertSee('value="ana@example.test"', false);
    }

    public function test_valid_update_changes_user_profile_and_keeps_existing_credentials(): void
    {
        $stateId = $this->createState('Activo');
        $newStateId = $this->createState('Bloqueado');
        $genderId = $this->createGender('Mujer');
        $roleId = $this->createRole('Estudiante');
        $usuarioId = $this->createUsuario($stateId, $genderId, $roleId);
        $this->createSession($usuarioId, 'ana@example.test', Hash::make('existing-password'));

        $this->put(route('usuarios.update', $usuarioId), [
            'nombres' => 'Ana María',
            'apellidos' => 'Actualizada',
            'cedula' => '123456789',
            'estado_id' => $newStateId,
            'year_nace' => 1999,
            'genero_id' => $genderId,
            'codigo' => 'EST-001',
            'rol_id' => $roleId,
            'email' => 'ana@example.test',
        ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'El usuario se actualizó correctamente.');

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'nombres' => 'Ana María',
            'apellidos' => 'Actualizada',
            'estado_id' => $newStateId,
            'year_nace' => 1999,
            'codigo' => 'EST-001',
        ]);
        $this->assertDatabaseHas('sesiones', [
            'usuario_id' => $usuarioId,
            'email' => 'ana@example.test',
        ]);
        $this->assertTrue(Hash::check(
            'existing-password',
            DB::table('sesiones')->where('usuario_id', $usuarioId)->value('password'),
        ));
    }

    public function test_guest_can_be_converted_to_community_user_and_receives_credentials(): void
    {
        Mail::fake();
        $stateId = $this->createState('Activo');
        $genderId = $this->createGender('Indefinido');
        $guestRoleId = $this->createRole('Invitado');
        $studentRoleId = $this->createRole('Estudiante');
        $usuarioId = $this->createUsuario($stateId, $genderId, $guestRoleId, 'INV-CODE-001', 0);
        $this->createSession($usuarioId, 'invitado+'.$usuarioId.'@example.invalid', Hash::make('placeholder'));

        $this->put(route('usuarios.update', $usuarioId), [
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'estado_id' => $stateId,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'rol_id' => $studentRoleId,
            'email' => 'ana@example.test',
        ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'El usuario se actualizó correctamente.');

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'rol_id' => $studentRoleId,
            'year_nace' => 2000,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'usuario_id' => $usuarioId,
            'email' => 'ana@example.test',
        ]);

        $temporaryPassword = null;
        Mail::assertSent(UsuarioAccesoMail::class, function (UsuarioAccesoMail $mail) use (&$temporaryPassword): bool {
            $temporaryPassword = $mail->password;

            return $mail->hasTo('ana@example.test');
        });
        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check(
            $temporaryPassword,
            DB::table('sesiones')->where('usuario_id', $usuarioId)->value('password'),
        ));
    }

    public function test_user_cannot_assign_an_administrative_role(): void
    {
        $stateId = $this->createState('Activo');
        $genderId = $this->createGender('Mujer');
        $roleId = $this->createRole('Estudiante');
        $adminRoleId = $this->createRole('Administrador');
        $usuarioId = $this->createUsuario($stateId, $genderId, $roleId);
        $session = $this->createSession($usuarioId, 'ana@example.test');

        $this->actingAs($session)
            ->put(route('usuarios.update', $usuarioId), [
                'nombres' => 'Ana',
                'apellidos' => 'Prueba',
                'cedula' => '123456789',
                'estado_id' => $stateId,
                'year_nace' => 2000,
                'genero_id' => $genderId,
                'rol_id' => $adminRoleId,
                'email' => 'ana@example.test',
            ])
            ->assertSessionHasErrors([
                'rol_id' => 'El rol seleccionado no está disponible.',
            ]);

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'rol_id' => $roleId,
        ]);
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createGender(string $name): int
    {
        return DB::table('generos')->insertGetId(['nombre' => $name]);
    }

    private function createRole(string $name): int
    {
        return DB::table('rols')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Rol de prueba',
        ]);
    }

    public function test_master_can_assign_an_administrative_role(): void
    {
        $stateId = $this->createState('Activo');
        $genderId = $this->createGender('Mujer');
        $studentRoleId = $this->createRole('Estudiante');
        $masterRoleId = $this->createRole('Master');
        $adminRoleId = $this->createRole('Administrador');
        $usuarioId = $this->createUsuario($stateId, $genderId, $studentRoleId);
        $this->createSession($usuarioId, 'ana@example.test');
        $masterId = $this->createUsuario($stateId, $genderId, $masterRoleId, 'MASTER-001', 1980, '987654321');
        $masterSession = $this->createSession($masterId, 'master@example.test');

        $this->actingAs($masterSession)
            ->put(route('usuarios.update', $usuarioId), [
                'nombres' => 'Ana',
                'apellidos' => 'Prueba',
                'cedula' => '123456789',
                'estado_id' => $stateId,
                'year_nace' => 2000,
                'genero_id' => $genderId,
                'rol_id' => $adminRoleId,
                'email' => 'ana@example.test',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'El usuario se actualizó correctamente.');

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'rol_id' => $adminRoleId,
        ]);
    }

    private function createUsuario(
        int $stateId,
        int $genderId,
        int $roleId,
        string $code = 'EST-001',
        int $yearOfBirth = 2000,
        string $cedula = '123456789',
    ): int {
        return DB::table('usuarios')->insertGetId([
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'cedula' => $cedula,
            'codigo' => $code,
            'year_nace' => $yearOfBirth,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSession(
        int $usuarioId,
        string $email,
        string $password = 'placeholder-password',
    ): Sesion {
        DB::table('sesiones')->insert([
            'usuario_id' => $usuarioId,
            'email' => $email,
            'password' => $password,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Sesion::query()->where('usuario_id', $usuarioId)->firstOrFail();
    }
}
