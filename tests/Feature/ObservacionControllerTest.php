<?php

namespace Tests\Feature;

use App\Models\Sesion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ObservacionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_index_lists_available_events_active_devices_and_recent_associations(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
        $activeStateId = $this->createState('Activo');
        $inactiveStateId = $this->createState('Inactivo');
        $deletedStateId = $this->createState('Eliminado');
        $organizerId = $this->createUser('ADMIN-1', 'Ana', 'Admin', roleName: 'Administrador');
        $playerId = $this->createUser('PLAYER-1', 'Omar', 'Jordan');
        $deviceTypeId = $this->createDeviceType('Computador');
        $activeDeviceId = $this->createDevice('PC 1', $deviceTypeId, $activeStateId);
        $this->createDevice('PC 2', $deviceTypeId, $inactiveStateId);
        $this->createEvent('Evento anterior', $activeStateId, $organizerId, '2026-03-09 10:00:00');
        $newerEventId = $this->createEvent('Evento reciente', $activeStateId, $organizerId, '2026-03-10 11:00:00');
        $this->createEvent('Evento eliminado', $deletedStateId, $organizerId, '2026-03-10 11:30:00');
        $this->createAssociation($playerId, $activeDeviceId, $newerEventId, $inactiveStateId, '2026-03-10 08:30:00');
        $this->createAssociation($playerId, $activeDeviceId, null, $activeStateId, '2026-03-09 10:00:00');
        $this->createAssociation($playerId, $activeDeviceId, null, $activeStateId, '2026-03-10 13:00:00');

        $this->get(route('observaciones.index'))
            ->assertOk()
            ->assertSee('name="mensaje"', false)
            ->assertSee('maxlength="256"', false)
            ->assertSeeInOrder(['name="mensaje"', 'maxlength="256"', 'required'])
            ->assertSeeInOrder(['Evento reciente', 'Evento anterior'])
            ->assertDontSee('Evento eliminado')
            ->assertSee('PC 1')
            ->assertDontSee('PC 2')
            ->assertSee('08:30am-PC 1-Omar Jordan')
            ->assertDontSee('09:00am-PC 1-Omar Jordan')
            ->assertDontSee('01:00pm-PC 1-Omar Jordan');
    }

    public function test_search_filters_by_admin_player_code_event_device_date_and_relation(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('ADMIN-123', 'Ana', 'Admin', roleName: 'Administrador');
        $playerId = $this->createUser('CC-123456', 'Omar', 'Jordan', 'ST-9876');
        $otherPlayerId = $this->createUser('CC-987654', 'Luz', 'Prueba', 'ST-5432');
        $deviceTypeId = $this->createDeviceType('Computador');
        $deviceId = $this->createDevice('PC 1', $deviceTypeId, $activeStateId);
        $organizerId = $this->createUser('ORGANIZER-1', 'Luis', 'Evento');
        $eventId = $this->createEvent('Torneo', $activeStateId, $organizerId, '2026-03-10 10:00:00');
        $associationId = $this->createAssociation($playerId, $deviceId, $eventId, $activeStateId, '2026-03-10 10:00:00');
        $matchingObservationId = $this->createObservation($adminId, 'Revisar el equipo', $associationId, '2026-03-10 11:00:00');
        $otherAssociationId = $this->createAssociation($otherPlayerId, $deviceId, null, $activeStateId, '2026-03-10 10:30:00');
        $this->createObservation($adminId, 'Otra observación', $otherAssociationId, '2026-03-10 11:30:00');
        $this->createObservation($adminId, 'Sin asociación', null, '2026-03-10 11:45:00');

        $this->getJson(route('observaciones.search', [
            'admin_cedula' => 'ADMIN-123',
            'usuario_cedula' => '9876',
            'evento_id' => $eventId,
            'dispositivo_id' => $deviceId,
            'fecha' => '2026-03-10',
            'con_asociacion' => '1',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $matchingObservationId)
            ->assertJsonPath('data.0.mensaje', 'Revisar el equipo')
            ->assertJsonPath('has_more', false);

        $this->getJson(route('observaciones.search', [
            'usuario_cedula' => '123456',
            'evento_id' => $eventId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $matchingObservationId);

        $this->getJson(route('observaciones.search', [
            'fecha' => '2026-03-10',
            'con_asociacion' => '0',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.mensaje', 'Sin asociación')
            ->assertJsonCount(1, 'data');
    }

    public function test_search_returns_newest_observations_with_cursor_pagination(): void
    {
        config()->set('app.observaciones_cursor_size', 1);
        $adminId = $this->createUser('ADMIN-456', 'Ana', 'Admin', roleName: 'Administrador');
        $olderId = $this->createObservation($adminId, 'Primera', null, '2026-03-10 10:00:00');
        $newerId = $this->createObservation($adminId, 'Segunda', null, '2026-03-10 11:00:00');

        $firstPage = $this->getJson(route('observaciones.search'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerId)
            ->assertJsonPath('has_more', true);

        $this->getJson(route('observaciones.search', ['cursor' => $firstPage->json('next_cursor')]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $olderId)
            ->assertJsonPath('has_more', false);
    }

    public function test_detail_returns_observation_and_associated_people_event_and_device(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
        $stateId = $this->createState('Activo');
        $adminId = $this->createUser('ADMIN-789', 'Ana', 'Admin', roleName: 'Administrador');
        $playerId = $this->createUser('PLAYER-789', 'Omar', 'Jordan');
        $organizerId = $this->createUser('ORGANIZER-789', 'Luis', 'Evento');
        $deviceTypeId = $this->createDeviceType('Computador');
        $deviceId = $this->createDevice('PC 1', $deviceTypeId, $stateId);
        $eventId = $this->createEvent('Torneo', $stateId, $organizerId, '2026-03-10 10:00:00');
        $associationId = $this->createAssociation($playerId, $deviceId, $eventId, $stateId, '2026-03-10 10:00:00');
        $observationId = $this->createObservation($adminId, 'Equipo revisado', $associationId, '2026-03-10 11:00:00');

        $this->getJson(route('observaciones.detail', $observationId))
            ->assertOk()
            ->assertJsonPath('data.fecha', '10/03/2026 11:00')
            ->assertJsonPath('data.mensaje', 'Equipo revisado')
            ->assertJsonPath('data.administrador', 'Ana Admin')
            ->assertJsonPath('data.jugador', 'Omar Jordan')
            ->assertJsonPath('data.dispositivo', 'PC 1')
            ->assertJsonPath('data.evento', 'Torneo');
    }

    public function test_valid_submission_creates_observation_for_authenticated_administrator(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
        $stateId = $this->createState('Activo');
        $adminId = $this->createUser('ADMIN-234', 'Ana', 'Admin', roleName: 'Administrador');
        $playerId = $this->createUser('PLAYER-234', 'Omar', 'Jordan');
        $associationId = $this->createAssociation($playerId, null, null, $stateId, '2026-03-10 11:30:00');
        $this->signInAsAdministrator($adminId);

        $this->post(route('observaciones.store'), [
            'mensaje' => 'Revisar el equipo',
            'asociacion_id' => $associationId,
        ])
            ->assertRedirect(route('observaciones.index'))
            ->assertSessionHas('success', 'La observación se creó correctamente.');

        $this->assertDatabaseHas('observaciones', [
            'usuario_id' => $adminId,
            'asociacion_id' => $associationId,
            'mensaje' => 'Revisar el equipo',
        ]);
    }

    public function test_valid_submission_without_association_creates_general_observation(): void
    {
        $adminId = $this->createUser('ADMIN-567', 'Ana', 'Admin', roleName: 'Administrador');
        $this->signInAsAdministrator($adminId);
        $message = str_repeat('a', 256);

        $this->post(route('observaciones.store'), ['mensaje' => $message])
            ->assertRedirect(route('observaciones.index'))
            ->assertSessionHas('success', 'La observación se creó correctamente.');

        $this->assertDatabaseHas('observaciones', [
            'usuario_id' => $adminId,
            'asociacion_id' => null,
            'mensaje' => $message,
        ]);
    }

    public function test_empty_message_is_rejected_with_a_validation_message(): void
    {
        $this->post(route('observaciones.store'), ['mensaje' => ' '])
            ->assertSessionHasErrors([
                'mensaje' => 'El mensaje de la observación es obligatorio.',
            ]);

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_message_longer_than_column_limit_is_rejected(): void
    {
        $this->post(route('observaciones.store'), ['mensaje' => str_repeat('a', 257)])
            ->assertSessionHasErrors([
                'mensaje' => 'El mensaje no puede superar los 256 caracteres.',
            ]);

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_association_older_than_24_hours_cannot_be_submitted(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 12:00:00'));
        $stateId = $this->createState('Activo');
        $playerId = $this->createUser('PLAYER-345', 'Omar', 'Jordan');
        $associationId = $this->createAssociation($playerId, null, null, $stateId, '2026-03-09 11:59:59');

        $this->post(route('observaciones.store'), [
            'mensaje' => 'Revisar el equipo',
            'asociacion_id' => $associationId,
        ])
            ->assertSessionHasErrors([
                'asociacion_id' => 'La asociación seleccionada no está entre las asociaciones recientes.',
            ]);

        $this->assertDatabaseCount('observaciones', 0);
    }

    public function test_observations_are_restricted_to_administrators(): void
    {
        $this->signInAsRole('Estudiante');

        $this->get(route('observaciones.index'))->assertForbidden();
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createDeviceType(string $name): int
    {
        return DB::table('tipo_dispos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Tipo de prueba',
        ]);
    }

    private function createUser(
        string $cedula,
        string $nombres,
        string $apellidos,
        ?string $codigo = null,
        string $roleName = 'Estudiante',
    ): int {
        $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id');
        $genderId ??= DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->where('nombre', $roleName)->value('id');
        $roleId ??= DB::table('rols')->insertGetId([
            'nombre' => $roleName,
            'descripcion' => 'Rol de prueba',
        ]);
        $stateId = DB::table('estados')->value('id') ?? $this->createState('Activo');

        return DB::table('usuarios')->insertGetId([
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cedula' => $cedula,
            'codigo' => $codigo ?? 'TEST-'.$cedula,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDevice(string $name, int $typeId, int $stateId): int
    {
        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $stateId,
            'descripcion' => 'Equipo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEvent(string $name, int $stateId, int $organizerId, string $start): int
    {
        return DB::table('eventos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Descripción de prueba',
            'usuario_id' => $organizerId,
            'inicio' => $start,
            'duracion_horas' => 2,
            'aforo' => 10,
            'estado_id' => $stateId,
            'auto_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAssociation(
        int $playerId,
        ?int $deviceId,
        ?int $eventId,
        int $stateId,
        string $start,
    ): int {
        return DB::table('asociaciones')->insertGetId([
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'evento_id' => $eventId,
            'reserva_id' => null,
            'estado_id' => $stateId,
            'inicio' => $start,
            'final' => $start,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createObservation(int $adminId, string $message, ?int $associationId, string $createdAt): int
    {
        return DB::table('observaciones')->insertGetId([
            'usuario_id' => $adminId,
            'mensaje' => $message,
            'asociacion_id' => $associationId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function signInAsAdministrator(int $adminId): void
    {
        $session = new Sesion;
        $session->forceFill([
            'id' => 999,
            'usuario_id' => $adminId,
            'email' => 'admin@example.test',
        ]);
        $session->setRelation('usuario', Usuario::query()->with('rol')->findOrFail($adminId));

        $this->actingAs($session);
    }
}
