<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CreateAsociacionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_creates_an_association_with_observation_and_audit(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111001', $activeStateId);
        $deviceId = $this->createDevice($activeStateId, 'PC 1');
        $this->createAssociationAction();

        $this->postAssociation($playerId, $deviceId, observation: 'Equipo revisado')
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111001']))
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $association = DB::table('asociaciones')->where('usuario_id', $playerId)->first();
        $this->assertNotNull($association);
        $this->assertSame($activeStateId, $association->estado_id);
        $this->assertSame('2026-03-15 13:45:00', $association->inicio);
        $this->assertSame($association->inicio, $association->final);
        $this->assertDatabaseHas('observaciones', [
            'usuario_id' => $adminId,
            'mensaje' => 'Equipo revisado',
            'asociacion_id' => $association->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $adminId,
            'tabla' => 'asociaciones',
            'indice' => $association->id,
        ]);
    }

    public function test_rejects_association_for_a_blocked_player(): void
    {
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $blockedStateId = $this->createState('Bloqueado');
        $playerId = $this->createUser('555111002', $blockedStateId);
        $deviceId = $this->createDevice($activeStateId, 'PC 1');

        $this->postAssociation($playerId, $deviceId)
            ->assertSessionHasErrors([
                'usuario_id' => 'Solo se pueden asociar jugadores en estado Activo.',
            ]);

        $this->assertDatabaseCount('asociaciones', 0);
    }

    public function test_rejects_a_second_association_while_the_player_has_one_active_or_blocked(): void
    {
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111003', $activeStateId);
        $deviceId = $this->createDevice($activeStateId, 'PC 1');
        $this->createAssociation($playerId, $deviceId, $blockedStateId, '2026-03-15 12:00:00', '2026-03-15 12:30:00');

        $this->postAssociation($playerId, $deviceId)
            ->assertSessionHasErrors([
                'usuario_id' => 'El jugador ya tiene una asociación Activa o Bloqueada que debe resolver antes de crear otra.',
            ]);

        $this->assertDatabaseCount('asociaciones', 1);
    }

    public function test_rejects_an_association_without_a_device_or_event(): void
    {
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111004', $activeStateId);

        $this->postAssociation($playerId, null)
            ->assertSessionHasErrors([
                'dispositivo_id' => 'Selecciona un dispositivo, o un evento que permita asociaciónes y sin dispositivo.',
            ]);

        $this->assertDatabaseCount('asociaciones', 0);
    }

    public function test_general_association_rejects_a_device_reserved_by_an_event_in_progress(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111005', $activeStateId);
        $deviceId = $this->createDevice($activeStateId, 'PC 1');
        $eventId = $this->createEvent($adminId, $activeStateId, 'Torneo', '2026-03-15 13:00:00', 2);
        $this->linkDeviceToEvent($eventId, $deviceId);

        $this->postAssociation($playerId, $deviceId)
            ->assertSessionHasErrors([
                'dispositivo_id' => 'El dispositivo está reservado por un evento en curso y no puede usarse en una asociación general.',
            ]);

        $this->assertDatabaseCount('asociaciones', 0);
    }

    public function test_creates_an_event_association_only_on_an_assigned_device(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111006', $activeStateId);
        $typeId = $this->createType();
        $assignedDeviceId = $this->createDevice($activeStateId, 'PC asignado', $typeId);
        $otherDeviceId = $this->createDevice($activeStateId, 'PC externo', $typeId);
        $eventId = $this->createEvent($adminId, $activeStateId, 'Torneo', '2026-03-15 13:00:00', 2);
        $this->linkDeviceToEvent($eventId, $assignedDeviceId);
        $this->createAssociationAction();

        $this->postAssociation($playerId, $otherDeviceId, $eventId)
            ->assertSessionHasErrors([
                'dispositivo_id' => 'El dispositivo debe estar asignado al evento seleccionado.',
            ]);
        $this->assertDatabaseCount('asociaciones', 0);

        $this->postAssociation($playerId, $assignedDeviceId, $eventId)
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $this->assertDatabaseHas('asociaciones', [
            'usuario_id' => $playerId,
            'evento_id' => $eventId,
            'dispositivo_id' => $assignedDeviceId,
        ]);
    }

    public function test_auto_admin_event_association_does_not_require_a_device(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111007', $activeStateId);
        $eventId = $this->createEvent($adminId, $activeStateId, 'Evento auto administrado', '2026-03-15 13:00:00', 2, true);
        $this->createAssociationAction();

        $this->postAssociation($playerId, null, $eventId)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111007']))
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $this->assertDatabaseHas('asociaciones', [
            'usuario_id' => $playerId,
            'evento_id' => $eventId,
            'dispositivo_id' => null,
        ]);
    }

    public function test_device_sharing_requires_confirmation_and_then_allows_the_association(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111008', $activeStateId);
        $otherPlayerId = $this->createUser('555111009', $activeStateId);
        $deviceId = $this->createDevice($activeStateId, 'Consola compartida');
        $this->createAssociation($otherPlayerId, $deviceId, $activeStateId, '2026-03-15 13:00:00', '2026-03-15 13:30:00');
        $this->createAssociationAction();
        $form = route('administracion.index', ['jugador_id' => '555111008']);

        $this->from($form)->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
        ])->assertSessionHasErrors([
            'confirmar_compartido' => 'Este dispositivo ya tiene otros jugadores asociados: Omar Jordan (0.5 h). Confirma si el uso compartido es intencional.',
        ]);

        $this->assertDatabaseCount('asociaciones', 1);
        $this->get($form)->assertOk()->assertSee('Omar Jordan')->assertSee('0.5 h');

        $this->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'confirmar_compartido' => '1',
        ])->assertRedirect($form)
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $this->assertDatabaseCount('asociaciones', 2);
    }

    public function test_matching_reservation_is_a_warning_that_the_administrator_can_confirm(): void
    {
        $this->travelTo('2026-03-15 14:30:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111010', $activeStateId);
        $reservationOwnerId = $this->createUser('555111011', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($activeStateId, 'PC 1', $typeId);
        $this->createReservation($reservationOwnerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);
        $this->createAssociationAction();
        $form = route('administracion.index', ['jugador_id' => '555111010']);

        $this->from($form)->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
        ])->assertSessionHasErrors([
            'confirmar_reserva' => 'Hay una reserva vigente de Omar Jordan. Su titular puede llegar durante su franja y reclamar un dispositivo; si llega, el administrador deberá cerrar o trasladar esta asociación. Confirma para continuar.',
        ]);

        $this->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'confirmar_reserva' => '1',
        ])->assertRedirect($form)
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $this->assertDatabaseHas('asociaciones', ['usuario_id' => $playerId, 'dispositivo_id' => $deviceId]);
    }

    public function test_general_reservation_does_not_warn_for_an_event_association_of_the_same_type(): void
    {
        $this->travelTo('2026-03-15 14:30:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111022', $activeStateId);
        $reservationOwnerId = $this->createUser('555111023', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($activeStateId, 'PC de evento', $typeId);
        $eventId = $this->createEvent($adminId, $activeStateId, 'Torneo', '2026-03-15 13:00:00', 2);
        $this->linkDeviceToEvent($eventId, $deviceId);
        $this->createReservation($reservationOwnerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);
        $this->createAssociationAction();

        $this->postAssociation($playerId, $deviceId, $eventId)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111022']))
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $this->assertDatabaseHas('asociaciones', [
            'usuario_id' => $playerId,
            'evento_id' => $eventId,
            'dispositivo_id' => $deviceId,
        ]);
    }

    public function test_event_reservation_conversion_cannot_change_its_event_scope(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111024', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($activeStateId, 'PC 1', $typeId);
        $reservedEventId = $this->createEvent($adminId, $activeStateId, 'Evento reservado', '2026-03-15 13:00:00', 2);
        $otherEventId = $this->createEvent($adminId, $activeStateId, 'Otro evento', '2026-03-15 13:00:00', 2);
        $this->linkDeviceToEvent($reservedEventId, $deviceId);
        $reservationId = $this->createReservation(
            $playerId,
            $typeId,
            $blockedStateId,
            $reservedEventId,
            '2026-03-15 14:00:00',
            60,
        );

        $this->postAssociation($playerId, $deviceId, $otherEventId, $reservationId)
            ->assertSessionHasErrors([
                'evento_id' => 'El evento de la asociación debe coincidir con el de la reserva.',
            ]);

        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $activeStateId]);
        $this->assertDatabaseCount('asociaciones', 0);
    }

    public function test_conversion_consumes_a_blocked_reservation_and_links_it_to_the_association(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $this->createState('Eliminado');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111012', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($activeStateId, 'PC 1', $typeId);
        $reservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);
        $this->createAssociationAction();

        $this->post(route('reservas.associate', $reservationId))
            ->assertRedirect(route('administracion.index', [
                'jugador_id' => '555111012',
                'reserva_id' => $reservationId,
            ]));

        $blockedStateId = DB::table('estados')->where('nombre', 'Bloqueado')->value('id');
        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $blockedStateId]);

        $this->postAssociation($playerId, $deviceId, reservationId: $reservationId)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111012']))
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $deletedStateId = DB::table('estados')->where('nombre', 'Eliminado')->value('id');
        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $deletedStateId]);
        $this->assertDatabaseHas('asociaciones', [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'reserva_id' => $reservationId,
            'evento_id' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $adminId,
            'tabla' => 'asociaciones',
        ]);
    }

    public function test_failed_conversion_restores_the_reservation_to_active(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111013', $activeStateId);
        $typeId = $this->createType();
        $reservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);
        $this->post(route('reservas.associate', $reservationId));
        $deviceId = $this->createDevice($activeStateId, 'Tipo incorrecto', $this->createType('Consola'));

        $this->postAssociation($playerId, $deviceId, reservationId: $reservationId)
            ->assertSessionHasErrors(['dispositivo_id' => 'El dispositivo debe ser del mismo tipo que el reservado.']);

        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $activeStateId]);
        $this->assertDatabaseCount('asociaciones', 0);
    }

    public function test_independent_association_deletes_same_day_reservation_but_preserves_a_future_day_reservation(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111014', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($activeStateId, 'PC 1', $typeId);
        $todayReservation = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 18:00:00', 60);
        $futureReservation = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-16 18:00:00', 60);
        $this->createAssociationAction();

        $this->postAssociation($playerId, $deviceId)
            ->assertSessionHas('success', 'Asociación creada correctamente.');

        $deletedStateId = DB::table('estados')->where('nombre', 'Eliminado')->value('id');
        $this->assertDatabaseHas('reservas', ['id' => $todayReservation, 'estado_id' => $deletedStateId]);
        $this->assertDatabaseHas('reservas', ['id' => $futureReservation, 'estado_id' => $activeStateId]);
    }

    public function test_continues_and_closes_a_blocked_association_using_operational_time(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111015', $activeStateId);
        $associationId = $this->createAssociation($playerId, null, $blockedStateId, '2026-03-15 13:00:00', '2026-03-15 13:30:00');

        $this->travelTo('2026-03-15 14:30:00');
        $this->post(route('asociaciones.continue', $associationId))
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111015']));
        $this->assertDatabaseHas('asociaciones', [
            'id' => $associationId,
            'estado_id' => $activeStateId,
            'inicio' => '2026-03-15 13:00:00',
            'final' => '2026-03-15 14:30:00',
        ]);

        $this->travelTo('2026-03-15 14:45:00');
        $this->post(route('asociaciones.close', $associationId))
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111015']));
        $deletedStateId = DB::table('estados')->where('nombre', 'Eliminado')->value('id');
        $this->assertDatabaseHas('asociaciones', [
            'id' => $associationId,
            'estado_id' => $deletedStateId,
            'final' => '2026-03-15 14:45:00',
        ]);
    }

    public function test_closing_a_blocked_association_keeps_its_last_active_end_time(): void
    {
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111016', $activeStateId);
        $associationId = $this->createAssociation(
            $playerId,
            null,
            $blockedStateId,
            '2026-03-15 13:00:00',
            '2026-03-15 13:20:00',
        );
        $this->travelTo('2026-03-15 13:45:00');

        $this->post(route('asociaciones.close', $associationId));

        $deletedStateId = DB::table('estados')->where('nombre', 'Eliminado')->value('id');
        $this->assertDatabaseHas('asociaciones', [
            'id' => $associationId,
            'estado_id' => $deletedStateId,
            'final' => '2026-03-15 13:20:00',
        ]);
    }

    public function test_reservation_can_be_cancelled_and_a_blocked_conversion_can_be_reversed(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111017', $activeStateId);
        $typeId = $this->createType();
        $reservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);

        $this->post(route('reservas.associate', $reservationId));
        $this->post(route('reservas.disassociate', $reservationId))
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111017']))
            ->assertSessionHas('success', 'Conversión cancelada; la reserva volvió al estado Activo.');
        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $activeStateId]);

        $this->post(route('reservas.cancel', $reservationId))
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111017']))
            ->assertSessionHas('success', 'Reserva cancelada correctamente.');
        $deletedStateId = DB::table('estados')->where('nombre', 'Eliminado')->value('id');
        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $deletedStateId]);
    }

    public function test_conversion_cannot_begin_outside_its_fifteen_minute_window(): void
    {
        $this->travelTo('2026-03-15 13:44:59');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111018', $activeStateId);
        $typeId = $this->createType();
        $reservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);

        $this->post(route('reservas.associate', $reservationId))
            ->assertSessionHasErrors([
                'reserva_id' => 'La reserva solo puede asociarse entre 15 minutos antes y 15 minutos después de su inicio.',
            ]);

        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $activeStateId]);
    }

    public function test_inactive_player_cannot_begin_reservation_conversion(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->createState('Bloqueado');
        $inactiveStateId = $this->createState('Inactivo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111025', $inactiveStateId);
        $typeId = $this->createType();
        $reservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 60);

        $this->post(route('reservas.associate', $reservationId))
            ->assertSessionHasErrors([
                'usuario_id' => 'Solo se pueden asociar jugadores en estado Activo.',
            ]);

        $this->assertDatabaseHas('reservas', ['id' => $reservationId, 'estado_id' => $activeStateId]);
    }

    public function test_player_module_shows_association_observations_and_current_device_sharing(): void
    {
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111019', $activeStateId);
        $otherPlayerId = $this->createUser('555111020', $activeStateId);
        $deviceId = $this->createDevice($activeStateId, 'Consola 1');
        $associationId = $this->createAssociation($playerId, $deviceId, $activeStateId, '2026-03-15 13:00:00', '2026-03-15 13:30:00');
        $this->createAssociation($otherPlayerId, $deviceId, $activeStateId, '2026-03-15 13:10:00', '2026-03-15 13:40:00');
        DB::table('observaciones')->insert([
            'usuario_id' => $adminId,
            'mensaje' => 'Primera observación',
            'asociacion_id' => $associationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('observaciones')->insert([
            'usuario_id' => $adminId,
            'mensaje' => 'Segunda observación',
            'asociacion_id' => $associationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('administracion.index', ['jugador_id' => '555111019']))
            ->assertOk()
            ->assertSee('Primera observación - Segunda observación')
            ->assertSee('Omar Jordan')
            ->assertSee('También asociados a este dispositivo:')
            ->assertDontSee('Pausar');
    }

    public function test_sync_command_leaves_associations_unchanged_and_expires_or_restores_reservations(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $deletedStateId = $this->createState('Eliminado');
        $playerId = $this->createUser('555111021', $activeStateId);
        $activeAssociationId = $this->createAssociation($playerId, null, $activeStateId, '2026-03-15 13:00:00', '2026-03-15 13:01:00');
        $blockedAssociationId = $this->createAssociation($playerId, null, $blockedStateId, '2026-03-15 12:00:00', '2026-03-15 12:30:00');
        $typeId = $this->createType();
        $expiredReservationId = $this->createReservation($playerId, $typeId, $activeStateId, null, '2026-03-15 13:00:00', 60);
        $abandonedReservationId = $this->createReservation($playerId, $typeId, $blockedStateId, null, '2026-03-15 15:00:00', 60);
        DB::table('reservas')->where('id', $abandonedReservationId)->update(['updated_at' => '2026-03-15 13:20:00']);

        $this->artisan('administracion:sync-player-activity')->assertSuccessful();

        $this->assertDatabaseHas('asociaciones', [
            'id' => $activeAssociationId,
            'final' => '2026-03-15 13:01:00',
        ]);
        $this->assertDatabaseHas('asociaciones', [
            'id' => $blockedAssociationId,
            'final' => '2026-03-15 12:30:00',
        ]);
        $this->assertDatabaseHas('reservas', ['id' => $expiredReservationId, 'estado_id' => $deletedStateId]);
        $this->assertDatabaseHas('reservas', ['id' => $abandonedReservationId, 'estado_id' => $activeStateId]);
    }

    public function test_hours_refresh_updates_active_association_end_and_renders_only_the_hours_component(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111026', $activeStateId);
        $otherPlayerId = $this->createUser('555111027', $activeStateId);
        $activeAssociationId = $this->createAssociation(
            $playerId,
            null,
            $activeStateId,
            '2026-03-15 13:00:00',
            '2026-03-15 13:01:00',
        );
        $blockedAssociationId = $this->createAssociation(
            $playerId,
            null,
            $blockedStateId,
            '2026-03-15 12:00:00',
            '2026-03-15 12:30:00',
        );
        $otherAssociationId = $this->createAssociation(
            $otherPlayerId,
            null,
            $activeStateId,
            '2026-03-15 13:00:00',
            '2026-03-15 13:10:00',
        );

        $this->get(route('administracion.index', ['jugador_id' => '555111026']))
            ->assertOk()
            ->assertSee('data-refresh-interval="5000"', false)
            ->assertSee(route('administracion.jugadores.horas.refresh', $playerId), false);

        $this->post(route('administracion.jugadores.horas.refresh', $playerId))
            ->assertOk()
            ->assertSee('id="comp-horas"', false)
            ->assertSee('0.8h')
            ->assertSee('Total: 1.3 horas')
            ->assertDontSee('administracion-title');

        $this->assertDatabaseHas('asociaciones', [
            'id' => $activeAssociationId,
            'final' => '2026-03-15 13:45:00',
        ]);
        $this->assertDatabaseHas('asociaciones', [
            'id' => $blockedAssociationId,
            'final' => '2026-03-15 12:30:00',
        ]);
        $this->assertDatabaseHas('asociaciones', [
            'id' => $otherAssociationId,
            'final' => '2026-03-15 13:10:00',
        ]);
    }

    public function test_association_operations_are_restricted_to_administration_roles(): void
    {
        $this->signInAsRole('Estudiante');

        $this->post(route('asociaciones.store'), [
            'usuario_id' => 1,
            'dispositivo_id' => 1,
        ])->assertForbidden();
    }

    private function authenticateAdministrator(int $activeStateId): int
    {
        $adminId = $this->createUser('100000001', $activeStateId, 'Administrador');
        auth()->user()->forceFill(['usuario_id' => $adminId]);

        return $adminId;
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createUser(string $identification, int $stateId, string $roleName = 'Estudiante'): int
    {
        $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id')
            ?? DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->where('nombre', $roleName)->value('id')
            ?? DB::table('rols')->insertGetId(['nombre' => $roleName, 'descripcion' => 'Rol de prueba']);
        $userId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Omar',
            'apellidos' => 'Jordan',
            'cedula' => $identification,
            'codigo' => 'ST-'.$identification,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sesiones')->insert([
            'usuario_id' => $userId,
            'email' => $identification.'@example.test',
            'password' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $userId;
    }

    private function createType(string $name = 'Computador'): int
    {
        return DB::table('tipo_dispos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Tipo de prueba',
        ]);
    }

    private function createDevice(int $stateId, string $name, ?int $typeId = null): int
    {
        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId ?? $this->createType(),
            'estado_id' => $stateId,
            'descripcion' => 'Dispositivo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEvent(
        int $organizerId,
        int $stateId,
        string $name,
        string $start,
        int $durationHours,
        bool $autoAdmin = false,
    ): int {
        return DB::table('eventos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Evento de prueba',
            'usuario_id' => $organizerId,
            'inicio' => $start,
            'duracion_horas' => $durationHours,
            'aforo' => 10,
            'estado_id' => $stateId,
            'auto_admin' => $autoAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkDeviceToEvent(int $eventId, int $deviceId): void
    {
        DB::table('evento_dispos')->insert([
            'evento_id' => $eventId,
            'dispositivo_id' => $deviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createReservation(
        int $playerId,
        int $typeId,
        int $stateId,
        ?int $eventId,
        string $start,
        int $duration,
    ): int {
        return DB::table('reservas')->insertGetId([
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => $eventId,
            'estado_id' => $stateId,
            'inicio' => $start,
            'duracion_min' => $duration,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAssociation(
        int $playerId,
        ?int $deviceId,
        int $stateId,
        string $start,
        string $final,
        ?int $eventId = null,
        ?int $reservationId = null,
    ): int {
        return DB::table('asociaciones')->insertGetId([
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'evento_id' => $eventId,
            'reserva_id' => $reservationId,
            'estado_id' => $stateId,
            'inicio' => $start,
            'final' => $final,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAssociationAction(): void
    {
        DB::table('acciones')->insert([
            'nombre' => 'Creó Asociación',
            'descripcion' => 'Creó una asociación',
        ]);
    }

    private function postAssociation(
        int $playerId,
        ?int $deviceId,
        ?int $eventId = null,
        ?int $reservationId = null,
        ?string $observation = null,
    ): TestResponse {
        return $this->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'evento_id' => $eventId,
            'reserva_id' => $reservationId,
            'observacion' => $observation,
        ]);
    }
}
