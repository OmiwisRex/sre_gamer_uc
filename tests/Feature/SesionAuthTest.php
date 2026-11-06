<?php

namespace Tests\Feature;

use App\Mail\UsuarioAccesoMail;
use App\Models\Sesion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SesionAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_authenticates_registered_user_from_sesiones(): void
    {
        $sesion = $this->createSession('player@example.test', 'current-password');

        $this->post(route('sesion.authenticate'), [
            'email' => 'player@example.test',
            'password' => 'current-password',
        ])->assertRedirect(route('index'));

        $this->assertTrue(Auth::check());
        $this->assertSame($sesion->id, Auth::id());
    }

    public function test_invalid_login_does_not_authenticate_the_user(): void
    {
        $this->createSession('player@example.test', 'current-password');

        $this->post(route('sesion.authenticate'), [
            'email' => 'player@example.test',
            'password' => 'incorrect-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'email' => 'Las credenciales ingresadas no son válidas.',
            ]);

        $this->assertFalse(Auth::check());
    }

    public function test_password_recovery_emails_and_stores_a_new_temporary_password(): void
    {
        Mail::fake();
        $sesion = $this->createSession('player@example.test', 'current-password');
        $temporaryPassword = null;

        $this->post(route('sesion.recover'), ['email' => 'player@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Si el correo está registrado, recibirás una nueva contraseña.');

        Mail::assertSent(UsuarioAccesoMail::class, function (UsuarioAccesoMail $mail) use (&$temporaryPassword): bool {
            $temporaryPassword = $mail->password;

            return $mail->hasTo('player@example.test')
                && preg_match('/^[A-Z0-9]{6}$/', $temporaryPassword) === 1;
        });

        $this->assertIsString($temporaryPassword);
        $this->assertNotSame('current-password', $temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $sesion->fresh()->password));
    }

    public function test_password_recovery_does_not_disclose_unknown_email_addresses(): void
    {
        Mail::fake();

        $this->post(route('sesion.recover'), ['email' => 'unknown@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Si el correo está registrado, recibirás una nueva contraseña.');

        Mail::assertNothingSent();
    }

    public function test_password_recovery_does_not_send_credentials_to_guest_accounts(): void
    {
        Mail::fake();
        $guestEmail = 'invitado+1@example.invalid';
        $this->createSession($guestEmail, 'placeholder-password', 'Invitado');

        $this->post(route('sesion.recover'), ['email' => $guestEmail])
            ->assertRedirect()
            ->assertSessionHas('success', 'Si el correo está registrado, recibirás una nueva contraseña.');

        Mail::assertNothingSent();
    }

    public function test_guests_and_non_administrators_cannot_access_administration_routes(): void
    {
        $this->get(route('usuarios.index'))
            ->assertRedirect(route('sesion.login'));

        $sesion = $this->createSession('player@example.test', 'password', 'Estudiante');

        $this->actingAs($sesion)
            ->get(route('usuarios.index'))
            ->assertForbidden();

        $this->get(route('reservaciones.index'))->assertStatus(501);
        $this->get(route('index'))
            ->assertOk()
            ->assertSee(route('reservaciones.index'))
            ->assertDontSee(route('usuarios.index'));
    }

    public function test_administrators_see_administration_menu_and_cannot_access_reservations(): void
    {
        $sesion = $this->createSession('admin@example.test', 'password', 'Administrador');

        $this->actingAs($sesion)
            ->get(route('index'))
            ->assertOk()
            ->assertSee(route('usuarios.index'))
            ->assertSee(route('auditorias.index'))
            ->assertDontSee(route('reservaciones.index'));

        $this->get(route('usuarios.index'))->assertOk();
        $this->get(route('reservaciones.index'))->assertForbidden();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $sesion = $this->createSession('player@example.test', 'password');

        $this->actingAs($sesion)
            ->post(route('sesion.logout'))
            ->assertRedirect(route('sesion.login'))
            ->assertSessionHas('success', 'Has cerrado sesión correctamente.');

        $this->assertFalse(Auth::check());
        $this->get(route('configuracion.index'))->assertRedirect(route('sesion.login'));
    }

    private function createSession(string $email, string $password, string $roleName = 'Estudiante'): Sesion
    {
        $stateId = DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado de prueba',
        ]);
        $genderId = DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->insertGetId([
            'nombre' => $roleName,
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
        $sesionId = DB::table('sesiones')->insertGetId([
            'usuario_id' => $usuarioId,
            'email' => $email,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Sesion::query()->findOrFail($sesionId);
    }
}
