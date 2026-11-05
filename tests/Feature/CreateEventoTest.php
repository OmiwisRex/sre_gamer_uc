<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateEventoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_form_lists_devices_and_defaults_start_to_current_time(): void
    {
        $activeStateId = $this->createActiveState();
        $deviceId = $this->createDevice($activeStateId);
        $this->travelTo('2026-03-15 13:45:00');

        $this->get(route('eventos.create'))
            ->assertOk()
            ->assertSee('name="nombre" value="" maxlength="32" pattern=".*\S.*"', false)
            ->assertSee('placeholder="Nombre" required', false)
            ->assertSee('name="duracion_horas" value="" min="1" max="24" step="1"', false)
            ->assertSee('placeholder="Horas" required', false)
            ->assertSee('name="aforo" value="" min="1" step="1"', false)
            ->assertSee('placeholder="Cantidad de invitados" required', false)
            ->assertSee('name="inicio" value="2026-03-15T13:45"', false)
            ->assertSee('name="dispositivos[]" value="'.$deviceId.'"', false)
            ->assertSee('PC Gamer 01');
    }

    public function test_valid_submission_creates_event_with_resolved_organizer_and_linked_devices(): void
    {
        $activeStateId = $this->createActiveState();
        $organizerId = $this->createUser('123456789', $activeStateId);
        $deviceId = $this->createDevice($activeStateId);

        $this->post(route('eventos.store'), [
            'nombre' => 'Torneo de prueba',
            'organizador_cedula' => '123456789',
            'inicio' => '2026-03-15T13:45',
            'duracion_horas' => '2',
            'aforo' => '30',
            'auto_admin' => '1',
            'dispositivos' => [(string) $deviceId],
        ])
            ->assertRedirect(route('eventos.create'))
            ->assertSessionHas('success', 'El evento se creó correctamente.');

        $this->assertDatabaseHas('eventos', [
            'nombre' => 'Torneo de prueba',
            'descripcion' => null,
            'usuario_id' => $organizerId,
            'inicio' => '2026-03-15 13:45:00',
            'duracion_horas' => 2,
            'aforo' => 30,
            'estado_id' => $activeStateId,
            'auto_admin' => true,
        ]);

        $eventId = DB::table('eventos')->where('nombre', 'Torneo de prueba')->value('id');

        $this->assertDatabaseHas('evento_dispos', [
            'evento_id' => $eventId,
            'dispositivo_id' => $deviceId,
        ]);
    }

    public function test_required_event_fields_are_rejected_when_empty(): void
    {
        $this->post(route('eventos.store'), [
            'nombre' => '',
            'organizador_cedula' => '',
            'inicio' => '',
            'duracion_horas' => '',
            'aforo' => '',
            'auto_admin' => '1',
        ])
            ->assertSessionHasErrors([
                'nombre' => 'El nombre del evento es obligatorio.',
                'organizador_cedula' => 'La cédula del organizador es obligatoria.',
                'inicio' => 'La fecha y hora de inicio son obligatorias.',
                'duracion_horas' => 'La duración es obligatoria.',
                'aforo' => 'El aforo aproximado es obligatorio.',
            ]);
    }

    #[DataProvider('invalidDurations')]
    public function test_non_positive_fractional_or_overlong_duration_is_rejected(string $duration, string $message): void
    {
        $this->createUser('123456789');

        $this->post(route('eventos.store'), [
            'nombre' => 'Torneo de prueba',
            'organizador_cedula' => '123456789',
            'inicio' => '2026-03-15T13:45',
            'duracion_horas' => $duration,
            'aforo' => '30',
            'auto_admin' => '1',
        ])
            ->assertSessionHasErrors([
                'duracion_horas' => $message,
            ]);

        $this->assertDatabaseCount('eventos', 0);
    }

    public static function invalidDurations(): array
    {
        return [
            'zero hours' => ['0', 'La duración mínima es de una hora.'],
            'over twenty-four hours' => ['25', 'La duración máxima es de 24 horas.'],
            'fractional hours' => ['1.5', 'La duración debe ser un número entero de horas.'],
        ];
    }

    public function test_unknown_organizer_cedula_is_rejected(): void
    {
        $this->post(route('eventos.store'), [
            'nombre' => 'Torneo de prueba',
            'organizador_cedula' => '999999999',
            'inicio' => '2026-03-15T13:45',
            'duracion_horas' => '2',
            'aforo' => '30',
            'auto_admin' => '1',
        ])
            ->assertSessionHasErrors([
                'organizador_cedula' => 'No se encontró un usuario con esa cédula.',
            ]);

        $this->assertDatabaseCount('eventos', 0);
    }

    private function createActiveState(): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado activo',
        ]);
    }

    private function createDevice(int $activeStateId): int
    {
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Equipo de escritorio',
        ]);

        return DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC Gamer 01',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(string $cedula, ?int $activeStateId = null): int
    {
        $genderId = DB::table('generos')->insertGetId(['nombre' => 'No especificado']);
        $roleId = DB::table('rols')->insertGetId([
            'nombre' => 'Jugador',
            'descripcion' => 'Rol de prueba',
        ]);
        $activeStateId ??= $this->createActiveState();

        return DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => $cedula,
            'codigo' => 'TEST-'.$cedula,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $activeStateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
