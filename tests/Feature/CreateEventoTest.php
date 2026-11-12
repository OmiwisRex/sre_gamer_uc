<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateEventoTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
        $this->travelTo('2026-03-15 12:00:00');
    }

    public function test_create_form_lists_devices_and_defaults_start_to_next_minute(): void
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
            ->assertSee('name="inicio" value="2026-03-15T13:46"', false)
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

    public function test_collisions_are_checked_by_device_and_half_open_interval(): void
    {
        $active = $this->createActiveState();
        $organizer = $this->createUser('123456789', $active);
        $firstDevice = $this->createDevice($active);
        $secondDevice = $this->createDevice($active, 'PC Gamer 02');
        $event = DB::table('eventos')->insertGetId([
            'nombre' => 'Existente', 'descripcion' => '', 'usuario_id' => $organizer,
            'inicio' => '2026-03-15 14:00:00', 'duracion_horas' => 2, 'aforo' => 10,
            'estado_id' => $active, 'auto_admin' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('evento_dispos')->insert(['evento_id' => $event, 'dispositivo_id' => $firstDevice]);
        $payload = [
            'nombre' => 'Nuevo', 'organizador_cedula' => '123456789', 'inicio' => '2026-03-15T15:00',
            'duracion_horas' => 2, 'aforo' => 10, 'auto_admin' => '1',
            'dispositivos' => [$firstDevice, $secondDevice],
        ];

        $this->post(route('eventos.store'), $payload)->assertSessionHasErrors([
            'dispositivos' => 'Los siguientes dispositivos tienen un conflicto de horario: PC Gamer 01.',
        ]);
        $this->assertDatabaseCount('eventos', 1);
        $this->post(route('eventos.store'), [...$payload, 'dispositivos' => [$secondDevice]])
            ->assertSessionHasNoErrors();
        $this->post(route('eventos.store'), [...$payload, 'nombre' => 'Consecutivo', 'inicio' => '2026-03-15T16:00', 'dispositivos' => [$firstDevice]])
            ->assertSessionHasNoErrors();
        $this->post(route('eventos.store'), [...$payload, 'nombre' => 'Anterior', 'inicio' => '2026-03-15T13:00', 'duracion_horas' => 1, 'dispositivos' => [$firstDevice]])
            ->assertSessionHasNoErrors();
    }

    public function test_event_requires_future_start_active_organizer_and_active_unique_devices(): void
    {
        $active = $this->createActiveState();
        $blocked = DB::table('estados')->insertGetId(['nombre' => 'Bloqueado', 'descripcion' => '']);
        $organizer = $this->createUser('123456789', $blocked);
        $device = $this->createDevice($blocked);
        $payload = [
            'nombre' => 'Nuevo', 'organizador_cedula' => '123456789', 'inicio' => '2026-03-15T12:00',
            'duracion_horas' => 1, 'aforo' => 1, 'auto_admin' => '1', 'dispositivos' => [$device],
        ];
        $this->post(route('eventos.store'), $payload)->assertSessionHasErrors(['usuario_id', 'inicio']);
        DB::table('usuarios')->where('id', $organizer)->update(['estado_id' => $active]);
        $payload['inicio'] = '2026-03-15T13:00';
        $this->post(route('eventos.store'), $payload)->assertSessionHasErrors(['dispositivos']);
        $this->post(route('eventos.store'), [...$payload, 'dispositivos' => []])->assertSessionHasErrors(['dispositivos']);
        $this->post(route('eventos.store'), [...$payload, 'dispositivos' => [$device, $device]])->assertSessionHasErrors(['dispositivos.0']);
        $this->assertDatabaseCount('eventos', 0);
    }

    public function test_blocked_and_deleted_events_do_not_collide(): void
    {
        $active = $this->createActiveState();
        $blocked = DB::table('estados')->insertGetId(['nombre' => 'Bloqueado', 'descripcion' => '']);
        $deleted = DB::table('estados')->insertGetId(['nombre' => 'Eliminado', 'descripcion' => '']);
        $organizer = $this->createUser('123456789', $active);
        $device = $this->createDevice($active);
        foreach ([$blocked, $deleted] as $state) {
            $event = DB::table('eventos')->insertGetId([
                'nombre' => 'Evento '.$state, 'descripcion' => '', 'usuario_id' => $organizer,
                'inicio' => '2026-03-15 14:00:00', 'duracion_horas' => 2, 'aforo' => 10,
                'estado_id' => $state, 'auto_admin' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('evento_dispos')->insert(['evento_id' => $event, 'dispositivo_id' => $device]);
        }

        $this->post(route('eventos.store'), [
            'nombre' => 'Nuevo', 'organizador_cedula' => '123456789', 'inicio' => '2026-03-15T14:30',
            'duracion_horas' => 1, 'aforo' => 10, 'auto_admin' => '1', 'dispositivos' => [$device],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('eventos', 3);
    }

    public function test_unauthorized_user_cannot_create_an_event(): void
    {
        $this->signInAsRole('Jugador');
        $this->post(route('eventos.store'), [])->assertForbidden();
    }

    private function createActiveState(): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado activo',
        ]);
    }

    private function createDevice(int $activeStateId, string $name = 'PC Gamer 01'): int
    {
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Tipo '.$name,
            'descripcion' => 'Equipo de escritorio',
        ]);

        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
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
