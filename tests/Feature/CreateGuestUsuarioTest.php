<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateGuestUsuarioTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_creation_stores_default_profile_and_unique_placeholder_credentials(): void
    {
        $genderId = $this->createGender('Indefinido');
        $activeStateId = $this->createActiveState();
        $guestRoleId = $this->createRole('Invitado');

        $this->from(route('usuarios.index'))
            ->post(route('usuarios.guest.store'), [
                'nombres' => 'Ana',
                'apellidos' => 'Prueba',
                'cedula' => '123456789',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('success', 'Invitado creado con éxito.');

        $this->assertDatabaseHas('usuarios', [
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'year_nace' => 0,
            'genero_id' => $genderId,
            'estado_id' => $activeStateId,
            'rol_id' => $guestRoleId,
        ]);
        $usuarioId = DB::table('usuarios')->where('cedula', '123456789')->value('id');
        $guestCode = DB::table('usuarios')->where('id', $usuarioId)->value('codigo');
        $this->assertMatchesRegularExpression('/^INV-[A-Z0-9]{20}$/', $guestCode);
        $guestSession = DB::table('sesiones')->where('usuario_id', $usuarioId)->first();
        $this->assertSame('invitado+'.$usuarioId.'@example.invalid', $guestSession->email);
        $guestPassword = $guestSession->password;
        $this->assertIsString($guestPassword);
        $this->assertNotSame('', $guestPassword);
        $this->assertNotSame('', password_get_info($guestPassword)['algoName']);
    }

    public function test_multiple_guests_receive_unique_codes_emails_and_password_hashes(): void
    {
        $this->createGender('Indefinido');
        $this->createActiveState();
        $this->createRole('Invitado');

        foreach (['123456789', '987654321'] as $cedula) {
            $this->post(route('usuarios.guest.store'), [
                'nombres' => 'Invitado',
                'apellidos' => 'Prueba',
                'cedula' => $cedula,
            ])->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('usuarios', 2);
        $this->assertDatabaseCount('sesiones', 2);
        $guestCodes = DB::table('usuarios')->orderBy('id')->pluck('codigo');
        $this->assertNotSame($guestCodes[0], $guestCodes[1]);
        $passwords = DB::table('sesiones')->orderBy('id')->pluck('password');
        $this->assertNotSame($passwords[0], $passwords[1]);
    }

    public function test_existing_cedula_is_rejected_with_a_visible_error(): void
    {
        $genderId = $this->createGender('Indefinido');
        $activeStateId = $this->createActiveState();
        $guestRoleId = $this->createRole('Invitado');
        $this->createGuestRecord('123456789', $genderId, $activeStateId, $guestRoleId);

        $this->post(route('usuarios.guest.store'), [
            'nombres' => 'Otra',
            'apellidos' => 'Persona',
            'cedula' => '123456789',
        ])
            ->assertSessionHasErrors([
                'cedula' => 'Ya existe un usuario con esa cédula.',
            ]);

        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('sesiones', 1);
    }

    public function test_missing_guest_fields_are_rejected(): void
    {
        $this->post(route('usuarios.guest.store'), [])
            ->assertSessionHasErrors([
                'nombres' => 'Los nombres son obligatorios.',
                'apellidos' => 'Los apellidos son obligatorios.',
                'cedula' => 'La cédula es obligatoria.',
            ]);

        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_users_index_exposes_the_guest_modal_post_form(): void
    {
        $this->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Crear invitado')
            ->assertSee('action="'.route('usuarios.guest.store').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }

    private function createGender(string $name): int
    {
        return DB::table('generos')->insertGetId(['nombre' => $name]);
    }

    private function createActiveState(): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado activo',
        ]);
    }

    private function createRole(string $name): int
    {
        DB::table('rols')->insertOrIgnore([
            'nombre' => $name,
            'descripcion' => 'Rol de prueba',
        ]);

        return (int) DB::table('rols')->where('nombre', $name)->value('id');
    }

    private function createGuestRecord(string $cedula, int $genderId, int $stateId, int $roleId): int
    {
        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Invitado',
            'apellidos' => 'Existente',
            'cedula' => $cedula,
            'codigo' => 'INV-EXISTING',
            'year_nace' => 0,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sesiones')->insert([
            'usuario_id' => $usuarioId,
            'email' => '',
            'password' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $usuarioId;
    }
}
