<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CreateReservationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_creates_a_general_reservation_and_audit_for_an_active_player(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000001', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111222', $activeStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 60)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111222']))
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => null,
            'estado_id' => $activeStateId,
            'inicio' => '2026-03-15 14:00:00',
            'duracion_min' => 60,
        ]);
        $reservationId = DB::table('reservas')->where('usuario_id', $playerId)->value('id');
        $actionId = DB::table('acciones')->where('nombre', 'Creó Reserva')->value('id');
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $adminId,
            'accion_id' => $actionId,
            'tabla' => 'reservas',
            'indice' => $reservationId,
        ]);
    }

    public function test_rejects_reservations_for_a_blocked_player(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $blockedStateId = $this->createState('Bloqueado');
        $playerId = $this->createUser('555111223', $blockedStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 30)
            ->assertSessionHasErrors([
                'usuario_id' => 'Solo los jugadores activos pueden crear reservas.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_rejects_a_second_reservation_when_the_existing_one_is_blocked(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $blockedStateId = $this->createState('Bloqueado');
        $playerId = $this->createUser('555111224', $activeStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createReservation($playerId, $typeId, $blockedStateId, null, '2026-03-16 14:00:00', 60);

        $this->postReservation($playerId, $typeId, '2026-03-17 14:00:00', 30)
            ->assertSessionHasErrors([
                'usuario_id' => 'El jugador ya tiene una reserva activa o bloqueada que debe resolver antes de crear otra.',
            ]);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_rejects_a_duration_that_is_not_offered_by_the_reservation_form(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111246', $activeStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 45)
            ->assertSessionHasErrors(['duracion_min']);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_rejects_a_same_day_reservation_while_the_player_has_a_current_association(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111225', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createAssociation($playerId, $deviceId, $activeStateId, '2026-03-15 13:35:00', '2026-03-15 13:45:00');

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 30)
            ->assertSessionHasErrors([
                'fecha_reserva' => 'El jugador tiene una asociación vigente y no puede reservar para el día de hoy.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_allows_a_future_day_reservation_while_the_player_has_a_current_association(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000002', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111226', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createAssociation($playerId, $deviceId, $activeStateId, '2026-03-15 13:35:00', '2026-03-15 13:45:00');
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-16 14:00:00', 30)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111226']))
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $playerId,
            'inicio' => '2026-03-16 14:00:00',
        ]);
    }

    public function test_rejects_a_reservation_when_an_existing_reservation_overlaps_any_part_of_the_slot(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111227', $activeStateId);
        $otherPlayerId = $this->createUser('555111228', $activeStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createReservation($otherPlayerId, $typeId, $activeStateId, null, '2026-03-15 14:30:00', 30);

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 60)
            ->assertSessionHasErrors([
                'tipo_dispo_id' => 'Las reservas existentes consumen la capacidad disponible durante parte de la franja solicitada.',
            ]);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_allows_a_reservation_to_start_when_an_existing_reservation_ends(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111247', $activeStateId);
        $otherPlayerId = $this->createUser('555111248', $activeStateId);
        $typeId = $this->createType();
        $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createReservation($otherPlayerId, $typeId, $activeStateId, null, '2026-03-15 14:00:00', 30);
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-15 14:30:00', 30)
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_rejects_a_general_reservation_when_an_event_uses_the_only_device_during_part_of_the_slot(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000003', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111229', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $eventId = $this->createEvent($adminId, $activeStateId, 'Torneo', '2026-03-15 14:30:00', 1);
        $this->linkDeviceToEvent($eventId, $deviceId);

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 60)
            ->assertSessionHasErrors([
                'tipo_dispo_id' => 'Los eventos activos ocupan los dispositivos de este tipo durante la franja solicitada.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_rejects_reservations_for_events_that_are_administered_by_their_organizers(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000004', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111230', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $eventId = $this->createEvent($adminId, $activeStateId, 'Evento auto admin', '2026-03-15 14:00:00', 1, true);
        $this->linkDeviceToEvent($eventId, $deviceId);

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 30, $eventId)
            ->assertSessionHasErrors([
                'evento_id' => 'Este evento es administrado por sus encargados y no admite reservas de jugadores.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_accepts_an_event_reservation_starting_inside_the_event_even_when_its_slot_ends_later(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000005', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111231', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $eventId = $this->createEvent($adminId, $activeStateId, 'Evento permitido', '2026-03-15 14:00:00', 1);
        $this->linkDeviceToEvent($eventId, $deviceId);
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-15 14:30:00', 60, $eventId)
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555111231']))
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $playerId,
            'evento_id' => $eventId,
            'inicio' => '2026-03-15 14:30:00',
            'duracion_min' => 60,
        ]);
    }

    public function test_reservations_in_separate_events_do_not_consume_each_others_capacity(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000006', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $firstPlayerId = $this->createUser('555111232', $activeStateId);
        $secondPlayerId = $this->createUser('555111233', $activeStateId);
        $typeId = $this->createType();
        $firstDeviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $secondDeviceId = $this->createDevice($typeId, $activeStateId, 'PC 2');
        $firstEventId = $this->createEvent($adminId, $activeStateId, 'Evento uno', '2026-03-15 14:00:00', 2);
        $secondEventId = $this->createEvent($adminId, $activeStateId, 'Evento dos', '2026-03-15 14:00:00', 2);
        $this->linkDeviceToEvent($firstEventId, $firstDeviceId);
        $this->linkDeviceToEvent($secondEventId, $secondDeviceId);
        $this->createReservationAction();

        $this->postReservation($firstPlayerId, $typeId, '2026-03-15 14:00:00', 60, $firstEventId)
            ->assertSessionHas('success', 'Reserva creada correctamente.');
        $this->postReservation($secondPlayerId, $typeId, '2026-03-15 14:00:00', 60, $secondEventId)
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_counts_a_shared_association_device_only_once_when_checking_capacity(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createUser('100000007', $activeStateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);
        $playerId = $this->createUser('555111234', $activeStateId);
        $firstAssociatedPlayerId = $this->createUser('555111235', $activeStateId);
        $secondAssociatedPlayerId = $this->createUser('555111236', $activeStateId);
        $typeId = $this->createType();
        $sharedDeviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createDevice($typeId, $activeStateId, 'PC 2');
        $this->createAssociation($firstAssociatedPlayerId, $sharedDeviceId, $activeStateId, '2026-03-15 13:35:00', '2026-03-15 13:45:00');
        $this->createAssociation($secondAssociatedPlayerId, $sharedDeviceId, $activeStateId, '2026-03-15 13:35:00', '2026-03-15 13:45:00');
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 30)
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_allows_a_reservation_when_an_active_association_has_met_its_minimum(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $adminId = $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111240', $activeStateId);
        $associatedPlayerId = $this->createUser('555111241', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createAssociation($associatedPlayerId, $deviceId, $activeStateId, '2026-03-15 13:00:00', '2026-03-15 13:31:00');
        $this->createReservationAction();

        $this->postReservation($playerId, $typeId, '2026-03-15 13:46:00', 30)
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_uses_the_reserved_duration_as_the_minimum_for_an_association_created_from_a_reservation(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $deletedStateId = $this->createState('Eliminado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111242', $activeStateId);
        $associatedPlayerId = $this->createUser('555111243', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $reservationId = $this->createReservation(
            $associatedPlayerId,
            $typeId,
            $deletedStateId,
            null,
            '2026-03-15 13:25:00',
            90,
        );
        $this->createAssociation(
            $associatedPlayerId,
            $deviceId,
            $activeStateId,
            '2026-03-15 13:35:00',
            '2026-03-15 13:45:00',
            $reservationId,
        );

        $this->postReservation($playerId, $typeId, '2026-03-15 14:25:00', 30)
            ->assertSessionHasErrors([
                'tipo_dispo_id' => 'Las asociaciones que aún consumen capacidad ocupan los dispositivos necesarios durante parte de la franja solicitada.',
            ]);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_a_blocked_association_below_its_minimum_keeps_consuming_capacity_until_resolved(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111244', $activeStateId);
        $associatedPlayerId = $this->createUser('555111245', $activeStateId);
        $typeId = $this->createType();
        $deviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $this->createAssociation($associatedPlayerId, $deviceId, $blockedStateId, '2026-03-15 13:35:00', '2026-03-15 13:35:00');

        $this->postReservation($playerId, $typeId, '2026-03-16 14:00:00', 30)
            ->assertSessionHasErrors([
                'tipo_dispo_id' => 'Las asociaciones que aún consumen capacidad ocupan los dispositivos necesarios durante parte de la franja solicitada.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_rejects_a_slot_without_a_single_device_available_for_its_entire_duration(): void
    {
        $this->travelTo('2026-03-15 13:45:00');
        $activeStateId = $this->createState('Activo');
        $this->authenticateAdministrator($activeStateId);
        $playerId = $this->createUser('555111237', $activeStateId);
        $firstAssociatedPlayerId = $this->createUser('555111238', $activeStateId);
        $secondAssociatedPlayerId = $this->createUser('555111239', $activeStateId);
        $typeId = $this->createType();
        $firstDeviceId = $this->createDevice($typeId, $activeStateId, 'PC 1');
        $secondDeviceId = $this->createDevice($typeId, $activeStateId, 'PC 2');
        $this->createAssociation($firstAssociatedPlayerId, $firstDeviceId, $activeStateId, '2026-03-15 14:00:00', '2026-03-15 14:00:00');
        $this->createAssociation($secondAssociatedPlayerId, $secondDeviceId, $activeStateId, '2026-03-15 14:30:00', '2026-03-15 14:30:00');

        $this->postReservation($playerId, $typeId, '2026-03-15 14:00:00', 60)
            ->assertSessionHasErrors([
                'tipo_dispo_id' => 'Ningún dispositivo mantiene disponibilidad continua durante toda la franja solicitada.',
            ]);

        $this->assertDatabaseCount('reservas', 0);
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createUser(string $cedula, int $stateId, string $roleName = 'Estudiante'): int
    {
        $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id')
            ?? DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->where('nombre', $roleName)->value('id')
            ?? DB::table('rols')->insertGetId([
                'nombre' => $roleName,
                'descripcion' => 'Rol de prueba',
            ]);

        return DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => $cedula,
            'codigo' => 'TEST-'.$cedula,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setAuthenticatedAdministrator(int $adminId): void
    {
        auth()->user()->forceFill(['usuario_id' => $adminId]);
    }

    private function authenticateAdministrator(int $stateId): int
    {
        $adminId = $this->createUser('100000001', $stateId, 'Administrador');
        $this->setAuthenticatedAdministrator($adminId);

        return $adminId;
    }

    private function createType(): int
    {
        return DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
    }

    private function createDevice(int $typeId, int $stateId, string $name): int
    {
        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
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
        int $deviceId,
        int $stateId,
        string $start,
        string $final,
        ?int $reservationId = null,
    ): void {
        DB::table('asociaciones')->insert([
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'evento_id' => null,
            'reserva_id' => $reservationId,
            'estado_id' => $stateId,
            'inicio' => $start,
            'final' => $final,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createReservationAction(): void
    {
        DB::table('acciones')->insertOrIgnore([
            'nombre' => 'Creó Reserva',
            'descripcion' => 'Creó una reserva',
        ]);
    }

    private function postReservation(
        int $playerId,
        int $typeId,
        string $start,
        int $duration,
        ?int $eventId = null,
    ): TestResponse {
        $startAt = Carbon::parse($start);

        return $this->post(route('reservas.store'), [
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => $eventId,
            'fecha_reserva' => $startAt->toDateString(),
            'hora_reserva' => $startAt->format('H:i'),
            'duracion_min' => (string) $duration,
        ]);
    }
}
