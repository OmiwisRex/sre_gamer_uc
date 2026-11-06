<?php

namespace Tests\Feature;

use App\Models\Sesion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfiguracionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_web_guard_authenticates_accounts_from_sessions_table(): void
    {
        $session = $this->createSession('player@example.test', 'current-password');

        $this->assertTrue(Auth::attempt([
            'email' => 'player@example.test',
            'password' => 'current-password',
        ]));

        $this->assertSame($session->id, Auth::id());
    }

    public function test_authenticated_user_can_change_password_with_valid_current_password(): void
    {
        $session = $this->createSession('player@example.test', 'current-password');

        $this->actingAs($session)
            ->put(route('configuracion.password.update'), [
                'current_password' => 'current-password',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ])
            ->assertRedirect(route('configuracion.index'))
            ->assertSessionHas('success', 'La contraseña se actualizó correctamente.');

        $this->assertTrue(Hash::check('newpass123', $session->fresh()->password));
    }

    public function test_password_change_is_rejected_when_current_password_is_invalid(): void
    {
        $session = $this->createSession('player@example.test', 'current-password');

        $this->actingAs($session)
            ->put(route('configuracion.password.update'), [
                'current_password' => 'incorrect-password',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ])
            ->assertSessionHasErrors([
                'current_password' => 'La contraseña actual no es válida.',
            ]);

        $this->assertTrue(Hash::check('current-password', $session->fresh()->password));
    }

    public function test_password_change_requires_a_six_character_matching_new_password(): void
    {
        $session = $this->createSession('player@example.test', 'current-password');

        $this->actingAs($session)
            ->put(route('configuracion.password.update'), [
                'current_password' => 'current-password',
                'new_password' => 'short',
                'new_password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors([
                'new_password' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            ]);

        $this->assertTrue(Hash::check('current-password', $session->fresh()->password));
    }

    public function test_password_change_rejects_a_mismatched_confirmation(): void
    {
        $session = $this->createSession('player@example.test', 'current-password');

        $this->actingAs($session)
            ->put(route('configuracion.password.update'), [
                'current_password' => 'current-password',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'different123',
            ])
            ->assertSessionHasErrors([
                'new_password' => 'La confirmación de la nueva contraseña no coincide.',
            ]);

        $this->assertTrue(Hash::check('current-password', $session->fresh()->password));
    }

    public function test_password_settings_redirects_guests_to_login(): void
    {
        $this->get(route('configuracion.index'))
            ->assertRedirect(route('sesion.login'));
    }

    private function createSession(string $email, string $password): Sesion
    {
        $stateId = DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado de prueba',
        ]);
        $genderId = DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->insertGetId([
            'nombre' => 'Estudiante',
            'descripcion' => 'Rol de prueba',
        ]);
        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'codigo' => 'EST-001',
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sessionId = DB::table('sesiones')->insertGetId([
            'usuario_id' => $usuarioId,
            'email' => $email,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Sesion::query()->findOrFail($sessionId);
    }
}
