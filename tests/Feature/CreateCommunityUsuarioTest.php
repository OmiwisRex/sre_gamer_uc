<?php

namespace Tests\Feature;

use App\Mail\UsuarioAccesoMail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateCommunityUsuarioTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_create_form_lists_gender_and_non_privileged_roles(): void
    {
        $genderId = $this->createGender('Mujer');
        $roleId = $this->createRole('Estudiante');
        $visitorRoleId = $this->createRole('Invitado');
        $adminRoleId = $this->createRole('Administrador');
        $masterRoleId = $this->createRole('Master');

        $this->get(route('usuarios.create'))
            ->assertOk()
            ->assertSee('<option value="'.$genderId.'"', false)
            ->assertSee('Mujer')
            ->assertSee('<option value="'.$roleId.'" selected', false)
            ->assertSee('Estudiante')
            ->assertDontSee('<option value="'.$visitorRoleId.'"', false)
            ->assertDontSee('<option value="'.$adminRoleId.'"', false)
            ->assertDontSee('<option value="'.$masterRoleId.'"', false);
    }

    public function test_valid_submission_creates_user_and_emails_hashed_temporary_password(): void
    {
        Mail::fake();
        $genderId = $this->createGender('Mujer');
        $activeStateId = $this->createActiveState();
        $roleId = $this->createRole('Estudiante');
        $mailPassword = null;

        $this->from(route('usuarios.create'))
            ->post(route('usuarios.community.store'), [
                'nombres' => 'Ana',
                'apellidos' => 'Prueba',
                'cedula' => '123456789',
                'year_nace' => '2000',
                'genero_id' => $genderId,
                'rol_id' => $roleId,
                'email' => 'ana@example.test',
            ])
            ->assertRedirect(route('usuarios.create'))
            ->assertSessionHas('success', 'Usuario de la comunidad creado. Se enviaron sus credenciales al correo registrado.');

        $this->assertDatabaseHas('usuarios', [
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $activeStateId,
            'rol_id' => $roleId,
        ]);
        $this->assertDatabaseHas('sesiones', [
            'email' => 'ana@example.test',
        ]);
        $communityCode = DB::table('usuarios')->where('cedula', '123456789')->value('codigo');
        $this->assertMatchesRegularExpression('/^USR-[A-Z0-9]{20}$/', $communityCode);

        Mail::assertSent(UsuarioAccesoMail::class, function (UsuarioAccesoMail $mail) use (&$mailPassword): bool {
            $mailPassword = $mail->password;
            $this->assertSame(6, strlen($mailPassword));
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $mailPassword);

            return $mail->hasTo('ana@example.test');
        });

        $sessionPassword = DB::table('sesiones')->where('email', 'ana@example.test')->value('password');
        $this->assertIsString($mailPassword);
        $this->assertTrue(Hash::check($mailPassword, $sessionPassword));
    }

    public function test_duplicate_code_and_email_are_rejected_without_sending_another_email(): void
    {
        Mail::fake();
        $genderId = $this->createGender('Hombre');
        $this->createActiveState();
        $roleId = $this->createRole('Estudiante');
        $this->createCommunityRecord('111111111', 'EST-100', 'ana@example.test', $genderId, $roleId);

        $this->post(route('usuarios.community.store'), [
            'nombres' => 'Otra',
            'apellidos' => 'Persona',
            'cedula' => '222222222',
            'year_nace' => '2001',
            'genero_id' => $genderId,
            'codigo' => 'EST-100',
            'rol_id' => $roleId,
            'email' => 'ana@example.test',
        ])
            ->assertSessionHasErrors([
                'codigo' => 'Ese código ya está registrado.',
                'email' => 'Ese correo electrónico ya está registrado.',
            ]);

        $this->assertDatabaseCount('usuarios', 1);
        Mail::assertNothingSent();
    }

    public function test_duplicate_cedula_is_rejected_for_community_users(): void
    {
        $genderId = $this->createGender('Hombre');
        $this->createActiveState();
        $roleId = $this->createRole('Estudiante');
        $this->createCommunityRecord('111111111', 'EST-100', 'ana@example.test', $genderId, $roleId);

        $this->post(route('usuarios.community.store'), [
            'nombres' => 'Otra',
            'apellidos' => 'Persona',
            'cedula' => '111111111',
            'year_nace' => '2001',
            'genero_id' => $genderId,
            'rol_id' => $roleId,
            'email' => 'otra@example.test',
        ])
            ->assertSessionHasErrors([
                'cedula' => 'Ya existe un usuario con esa cédula.',
            ]);

        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_privileged_role_is_rejected_even_when_submitted_directly(): void
    {
        Mail::fake();
        $genderId = $this->createGender('Mujer');
        $this->createActiveState();
        $adminRoleId = $this->createRole('Administrador');

        $this->post(route('usuarios.community.store'), [
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'year_nace' => '2000',
            'genero_id' => $genderId,
            'rol_id' => $adminRoleId,
            'email' => 'user@example.test',
        ])
            ->assertSessionHasErrors('rol_id');

        $this->assertDatabaseCount('usuarios', 0);
        Mail::assertNothingSent();
    }

    public function test_community_fields_must_be_valid(): void
    {
        $this->post(route('usuarios.community.store'), [
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'year_nace' => '1899',
            'genero_id' => 999,
            'rol_id' => 999,
            'email' => 'correo-invalido',
        ])
            ->assertSessionHasErrors([
                'year_nace' => 'El año de nacimiento debe estar entre 1900 y '.now()->year.'.',
                'genero_id',
                'rol_id' => 'El rol seleccionado no está disponible.',
                'email' => 'Ingresa un correo electrónico válido.',
            ]);

        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('sesiones', 0);
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
        return DB::table('rols')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Rol de prueba',
        ]);
    }

    private function createCommunityRecord(
        string $cedula,
        string $codigo,
        string $email,
        int $genderId,
        int $roleId,
    ): int {
        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Existente',
            'cedula' => $cedula,
            'codigo' => $codigo,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => DB::table('estados')->where('nombre', 'Activo')->value('id'),
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sesiones')->insert([
            'usuario_id' => $usuarioId,
            'email' => $email,
            'password' => Hash::make('TEMPORARY'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $usuarioId;
    }
}
