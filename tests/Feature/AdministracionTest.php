<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_administration_page_renders_truncated_database_options_and_default_reservation_values(): void
    {
        $estadoId = DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado activo',
        ]);
        $generoId = DB::table('generos')->insertGetId(['nombre' => 'No especificado']);
        $rolId = DB::table('rols')->insertGetId([
            'nombre' => 'Jugador',
            'descripcion' => 'Rol de prueba',
        ]);
        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => '123456789',
            'codigo' => 'TEST-001',
            'year_nace' => 2000,
            'genero_id' => $generoId,
            'estado_id' => $estadoId,
            'rol_id' => $rolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tipoDispoId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Tipo de dispositivo',
            'descripcion' => 'Tipo de prueba',
        ]);

        $dispositivoId = DB::table('dispositivos')->insertGetId([
            'nombre' => 'Dispositivo Gamer UC Pro',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $tipoDispoId,
            'estado_id' => $estadoId,
            'descripcion' => 'Dispositivo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $eventoId = DB::table('eventos')->insertGetId([
            'nombre' => 'Evento de prueba nombre extenso',
            'descripcion' => 'Evento de prueba',
            'usuario_id' => $usuarioId,
            'inicio' => '2026-03-15 13:45:00',
            'duracion_horas' => 1,
            'aforo' => 1,
            'estado_id' => $estadoId,
            'auto_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));

        $response = $this->get(route('administracion.index', ['jugador_id' => '123456789']));

        $response->assertOk()
            ->assertSee('<option value="'.$tipoDispoId.'" title="Tipo de dispositivo">Tipo de dispositiv...</option>', false)
            ->assertSee('<option value="'.$dispositivoId.'" title="Dispositivo Gamer UC Pro">Dispositivo Gamer...</option>', false)
            ->assertSee('<option value="'.$eventoId.'" title="Evento de prueba nombre extenso">Evento de prueba n...</option>', false)
            ->assertSee('name="fecha_reserva" value="2026-03-15"', false)
            ->assertSee('name="hora_reserva" value="13:45"', false);

        foreach ([30, 60, 90, 120, 150, 180] as $duration) {
            $option = '<option value="'.$duration.'"'.($duration === 30 ? ' selected' : '').'>'.$duration.' min</option>';
            $response->assertSee($option, false);
        }
    }

    public function test_association_form_carries_reservation_id_and_shows_reserved_badge_when_provided(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $playerId = $this->createPlayer('555123456', 'ST-123', $activeStateId, 'Estudiante');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        DB::table('reservas')->insert([
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => null,
            'estado_id' => $blockedStateId,
            'inicio' => '2026-03-15 14:00:00',
            'duracion_min' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reservationId = DB::table('reservas')->where('usuario_id', $playerId)->value('id');

        $response = $this->get(route('administracion.index', [
            'jugador_id' => '555123456',
            'reserva_id' => $reservationId,
        ]));

        $response->assertOk()
            ->assertSee('<span class="sre-badge">Reservado</span>', false)
            ->assertSee('<input type="hidden" name="reserva_id" value="'.$reservationId.'">', false);
    }

    public function test_association_form_has_empty_reservation_id_and_no_reserved_badge_when_not_provided(): void
    {
        $stateId = $this->createState('Activo');
        $this->createPlayer('123456789', 'ST-123', $stateId, 'Estudiante');
        $response = $this->get(route('administracion.index', ['jugador_id' => '123456789']));

        $response->assertOk()
            ->assertDontSee('<span class="sre-badge">Reservado</span>', false)
            ->assertSee('<input type="hidden" name="reserva_id" value="">', false);
    }

    public function test_player_search_by_identification_shows_user_details_and_empty_forms(): void
    {
        $stateId = $this->createState('Bloqueado');
        $userId = $this->createPlayer('123456789', 'ST-123', $stateId, 'Jugador');

        $this->get(route('administracion.index', ['jugador_id' => '123456789']))
            ->assertOk()
            ->assertSee('value="123456789"', false)
            ->assertSee('>Bloqueado</span>', false)
            ->assertSee('Omar Jordan')
            ->assertSee('C.C. 123456789')
            ->assertSee('Código: ST-123')
            ->assertSee('omar@example.test')
            ->assertSee('Jugador')
            ->assertSee('Crear reserva')
            ->assertSee('Crear asociación')
            ->assertSee('Sin actividad registrada hoy.')
            ->assertSee('Total: 0.0 horas');
    }

    public function test_player_search_by_student_code_loads_active_relations_and_sorted_daily_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $deletedStateId = $this->createState('Eliminado');
        $userId = $this->createPlayer('987654321', 'ST-987', $activeStateId, 'Estudiante');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        $deviceId = DB::table('dispositivos')->insertGetId([
            'nombre' => 'Dispositivo Gamer UC Pro',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => 'Equipo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createReservation($userId, $typeId, $activeStateId, '2026-03-15 15:00:00', 60);
        $this->createReservation($userId, $typeId, $deletedStateId, '2026-03-15 16:00:00', 90);
        $this->createAssociation($userId, $deviceId, $activeStateId, '2026-03-15 09:00:00', '2026-03-15 10:00:00');
        $this->createAssociation($userId, $deviceId, $blockedStateId, '2026-03-15 11:02:00', '2026-03-15 15:02:00');
        $this->createAssociation($userId, null, $deletedStateId, '2026-03-15 08:00:00', '2026-03-15 08:36:00');
        $this->createAssociation($userId, $deviceId, $activeStateId, '2026-03-14 23:00:00', '2026-03-15 01:00:00');

        $this->get(route('administracion.index', ['jugador_id' => 'ST-987']))
            ->assertOk()
            ->assertSee('>Activo</span>', false)
            ->assertSee('Reserva')
            ->assertSee('60 min')
            ->assertDontSee('90 min')
            ->assertSee('Bloqueado')
            ->assertSee('11:02am')
            ->assertSee('09:00am')
            ->assertSee('08:00am')
            ->assertSee('title="Dispositivo Gamer UC Pro">Dispositivo Gamer...</td>', false)
            ->assertSee('4.0h')
            ->assertSee('1.0h')
            ->assertSee('0.6h')
            ->assertSee('Total: 5.6 horas')
            ->assertDontSee('11:00pm');
    }

    public function test_player_search_reports_when_identification_does_not_match(): void
    {
        $this->get(route('administracion.index', ['jugador_id' => 'NO-EXISTE']))
            ->assertOk()
            ->assertSee('No encontrado')
            ->assertSee('No se encontró un jugador')
            ->assertSee('id="comp-reservar-title"', false)
            ->assertSee('id="comp-asociar-title"', false)
            ->assertSee('id="comp-horas-title"', false)
            ->assertDontSee('Crear reserva')
            ->assertDontSee('Crear asociación')
            ->assertDontSee('Sin actividad registrada hoy.')
            ->assertDontSee('Total: 0.0 horas');
    }

    public function test_player_cards_show_only_titles_before_search(): void
    {
        $this->get(route('administracion.index'))
            ->assertOk()
            ->assertSee('id="comp-reservar-title"', false)
            ->assertSee('id="comp-asociar-title"', false)
            ->assertSee('id="comp-horas-title"', false)
            ->assertDontSee('Crear reserva')
            ->assertDontSee('Crear asociación')
            ->assertDontSee('Sin actividad registrada hoy.')
            ->assertDontSee('Total: 0.0 horas');
    }

    public function test_hours_refresh_keeps_the_hours_body_for_the_selected_player(): void
    {
        $stateId = $this->createState('Activo');
        $playerId = $this->createPlayer('123456789', 'ST-123', $stateId, 'Estudiante');

        $this->post(route('administracion.jugadores.horas.refresh', $playerId))
            ->assertOk()
            ->assertSee('id="comp-horas"', false)
            ->assertSee('Sin actividad registrada hoy.')
            ->assertSee('Total: 0.0 horas');
    }

    public function test_active_association_is_shown_instead_of_the_create_form(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $stateId = $this->createState('Activo');
        $userId = $this->createPlayer('111222333', 'ST-111', $stateId, 'Estudiante', 'otro@example.test');
        $this->createAssociation($userId, null, $stateId, '2026-03-15 12:00:00', '2026-03-15 13:00:00');

        $this->get(route('administracion.index', ['jugador_id' => 'ST-111']))
            ->assertOk()
            ->assertSee('Asociación')
            ->assertDontSee('Crear asociación')
            ->assertSee('12:00');
    }

    public function test_reservation_can_be_created_and_refreshes_the_selected_player(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createPlayer('100000010', 'ADMIN-10', $activeStateId, 'Administrador', 'admin10@example.test');
        auth()->user()->forceFill(['usuario_id' => $adminId]);
        $playerId = $this->createPlayer('555111222', 'ST-555', $activeStateId, 'Estudiante');
        $otherPlayerId = $this->createPlayer('555111223', 'ST-556', $activeStateId, 'Estudiante', 'other@example.test');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        $deviceId = DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC 1',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => 'Dispositivo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createReservation($otherPlayerId, $typeId, $activeStateId, '2026-03-15 14:30:00', 60);
        $this->createAssociation($otherPlayerId, $deviceId, $activeStateId, '2026-03-15 14:30:00', '2026-03-15 15:00:00');
        $eventId = DB::table('eventos')->insertGetId([
            'nombre' => 'Torneo',
            'descripcion' => 'Evento de prueba',
            'usuario_id' => $playerId,
            'inicio' => '2026-03-15 14:00:00',
            'duracion_horas' => 1,
            'aforo' => 1,
            'estado_id' => $activeStateId,
            'auto_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('evento_dispos')->insert([
            'evento_id' => $eventId,
            'dispositivo_id' => $deviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('acciones')->insertOrIgnore([
            'nombre' => 'Creó Reserva',
            'descripcion' => 'Creó una reserva',
        ]);

        $response = $this->post(route('reservas.store'), [
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => $eventId,
            'fecha_reserva' => '2026-03-15',
            'hora_reserva' => '14:30',
            'duracion_min' => 60,
        ]);

        $response->assertRedirect(route('administracion.index', ['jugador_id' => '555111222']))
            ->assertSessionHas('success', 'Reserva creada correctamente.');
        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => $eventId,
            'estado_id' => $activeStateId,
            'inicio' => '2026-03-15 14:30:00',
            'duracion_min' => 60,
        ]);
        $this->assertDatabaseCount('reservas', 2);
        $reservationId = DB::table('reservas')
            ->where('usuario_id', $playerId)
            ->where('evento_id', $eventId)
            ->value('id');
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $adminId,
            'tabla' => 'reservas',
            'indice' => $reservationId,
        ]);
        $this->get(route('administracion.index', ['jugador_id' => '555111222']))
            ->assertOk()
            ->assertSee('Reserva')
            ->assertSee('02:30 PM')
            ->assertDontSee('Crear reserva');
    }

    public function test_reservation_start_must_not_be_in_the_past(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $playerId = $this->createPlayer('555333444', 'ST-5553', $activeStateId, 'Estudiante');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);

        $this->from(route('administracion.index', ['jugador_id' => '555333444']))
            ->post(route('reservas.store'), [
                'usuario_id' => $playerId,
                'tipo_dispo_id' => $typeId,
                'fecha_reserva' => '2026-03-15',
                'hora_reserva' => '13:44',
                'duracion_min' => 30,
            ])
            ->assertRedirect(route('administracion.index', ['jugador_id' => '555333444']))
            ->assertSessionHasErrors([
                'fecha_reserva' => 'La fecha y hora de inicio deben ser actuales o futuras.',
            ]);

        $this->assertDatabaseMissing('reservas', ['usuario_id' => $playerId]);
    }

    public function test_player_cannot_have_a_second_active_reservation(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $adminId = $this->createPlayer('100000011', 'ADMIN-11', $activeStateId, 'Administrador', 'admin11@example.test');
        auth()->user()->forceFill(['usuario_id' => $adminId]);
        $playerId = $this->createPlayer('555666777', 'ST-666', $activeStateId, 'Estudiante');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        $this->createReservation($playerId, $typeId, $activeStateId, '2026-03-15 14:00:00', 60);

        $this->post(route('reservas.store'), [
            'usuario_id' => $playerId,
            'tipo_dispo_id' => $typeId,
            'fecha_reserva' => '2026-03-15',
            'hora_reserva' => '15:00',
            'duracion_min' => 30,
        ])->assertSessionHasErrors([
            'usuario_id' => 'El jugador ya tiene una reserva activa o bloqueada que debe resolver antes de crear otra.',
        ]);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_association_can_be_created_with_an_observation_and_deleted_history(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));
        $activeStateId = $this->createState('Activo');
        $deletedStateId = $this->createState('Eliminado');
        $adminId = $this->createPlayer('100000001', 'ADMIN-1', $activeStateId, 'Administrador');
        auth()->user()->forceFill(['usuario_id' => $adminId]);
        $playerId = $this->createPlayer('555888999', 'ST-888', $activeStateId, 'Estudiante', 'player@example.test');
        DB::table('acciones')->insert([
            'nombre' => 'Creó Asociación',
            'descripcion' => 'Creó una asociación',
        ]);
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        $deviceId = DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC 1',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => 'Dispositivo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createAssociation($playerId, null, $deletedStateId, '2026-03-14 10:00:00', '2026-03-14 10:30:00');

        $response = $this->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'observacion' => 'Gamepad con falla',
        ]);

        $response->assertRedirect(route('administracion.index', ['jugador_id' => '555888999']))
            ->assertSessionHas('success', 'Asociación creada correctamente.');
        $this->assertDatabaseHas('asociaciones', [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
            'estado_id' => $activeStateId,
        ]);
        $associationId = DB::table('asociaciones')->where('usuario_id', $playerId)->where('estado_id', $activeStateId)->value('id');
        $this->assertDatabaseHas('observaciones', [
            'usuario_id' => $adminId,
            'mensaje' => 'Gamepad con falla',
            'asociacion_id' => $associationId,
        ]);
        $this->get(route('administracion.index', ['jugador_id' => '555888999']))
            ->assertOk()
            ->assertSee('Asociación')
            ->assertSee('PC 1')
            ->assertDontSee('Crear asociación');
    }

    public function test_association_cannot_be_created_while_a_previous_association_is_not_deleted(): void
    {
        $activeStateId = $this->createState('Activo');
        $blockedStateId = $this->createState('Bloqueado');
        $adminId = $this->createPlayer('100000002', 'ADMIN-2', $activeStateId, 'Administrador', 'admin2@example.test');
        auth()->user()->forceFill(['usuario_id' => $adminId]);
        $playerId = $this->createPlayer('555000111', 'ST-000', $activeStateId, 'Estudiante');
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Tipo de prueba',
        ]);
        $deviceId = DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC 1',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => 'Dispositivo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createAssociation($playerId, null, $blockedStateId, '2026-03-14 10:00:00', '2026-03-14 10:30:00');

        $this->post(route('asociaciones.store'), [
            'usuario_id' => $playerId,
            'dispositivo_id' => $deviceId,
        ])->assertSessionHasErrors([
            'usuario_id' => 'El jugador ya tiene una asociación Activa o Bloqueada que debe resolver antes de crear otra.',
        ]);

        $this->assertDatabaseCount('asociaciones', 1);
    }

    public function test_schedule_groups_half_hour_reservations_and_device_activity_without_assigning_reservations_to_devices(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 10:15:00'));
        $active = $this->createState('Activo');
        $deleted = $this->createState('Eliminado');
        $player = $this->createPlayer('123456789', 'ST-123', $active, 'Estudiante');
        $type = DB::table('tipo_dispos')->insertGetId(['nombre' => 'Computador', 'descripcion' => 'Tipo']);
        $device = DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC 1', 'direccion' => '', 'password' => '', 'tipo_dispo_id' => $type,
            'estado_id' => $active, 'descripcion' => 'Equipo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherDevice = DB::table('dispositivos')->insertGetId([
            'nombre' => 'PC 2', 'direccion' => '', 'password' => '', 'tipo_dispo_id' => $type,
            'estado_id' => $active, 'descripcion' => 'Equipo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $event = DB::table('eventos')->insertGetId([
            'nombre' => 'Torneo', 'descripcion' => '', 'usuario_id' => $player,
            'inicio' => '2026-03-15 10:30:00', 'duracion_horas' => 1, 'aforo' => 2,
            'estado_id' => $active, 'auto_admin' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('evento_dispos')->insert([
            'evento_id' => $event, 'dispositivo_id' => $device, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->createReservation($player, $type, $active, '2026-03-15 10:00:00', 30);
        $this->createReservation($player, $type, $active, '2026-03-14 23:45:00', 30);
        $this->createReservation($player, $type, $deleted, '2026-03-15 10:00:00', 30);
        DB::table('reservas')->insert([
            'usuario_id' => $player, 'tipo_dispo_id' => $type, 'evento_id' => $event, 'estado_id' => $active,
            'inicio' => '2026-03-15 10:15:00', 'duracion_min' => 30, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->createAssociation($player, $device, $active, '2026-03-15 10:00:00', '2026-03-15 10:30:00');
        DB::table('asociaciones')->insert([
            'usuario_id' => $player, 'dispositivo_id' => $device, 'evento_id' => $event, 'reserva_id' => null,
            'estado_id' => $active, 'inicio' => '2026-03-15 10:15:00', 'final' => '2026-03-15 11:00:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson(route('administracion.horario.get', ['fecha' => '2026-03-15']));

        $response->assertOk()
            ->assertJsonPath("reservasPorTipo.$type.0.cantidad", 1)
            ->assertJsonPath("reservasPorTipo.$type.20.cantidad", 2)
            ->assertJsonPath("reservasPorTipo.$type.20.tipo", 'reservation-event')
            ->assertJsonPath("reservasPorTipo.$type.21.cantidad", 1)
            ->assertJsonMissingPath("reservasPorTipo.$type.22")
            ->assertJsonPath("ocupaciones.$device.20.cantidad", 2)
            ->assertJsonPath("ocupaciones.$device.20.tipo", 'association-event')
            ->assertJsonPath("ocupaciones.$device.22.tipo", 'event')
            ->assertJsonMissingPath("ocupaciones.$device.23")
            ->assertJsonMissingPath("ocupaciones.$otherDevice");
        $this->assertStringContainsString('Computador', $response->json('html'));
        $this->assertStringContainsString('10 Am', $response->json('html'));
        $this->assertStringContainsString('title="23:30"', $response->json('html'));

        $this->postJson(route('administracion.horario.seleccion'), [
            'fecha' => '2026-03-15', 'franja' => 20, 'tipo_dispo_id' => $type,
        ])->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('items.0.identificacion', '123456789');

        $this->postJson(route('administracion.horario.seleccion'), [
            'fecha' => '2026-03-15', 'franja' => 21, 'dispositivo_id' => $device,
        ])->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.tipo', 'association-event');

        $this->postJson(route('administracion.horario.seleccion'), [
            'fecha' => '2026-03-15', 'franja' => 48, 'dispositivo_id' => $device,
        ])->assertUnprocessable()->assertJsonValidationErrors('franja');

        $this->postJson(route('administracion.horario.seleccion'), [
            'fecha' => '2026-03-15', 'franja' => 20, 'dispositivo_id' => $device, 'tipo_dispo_id' => $type,
        ])->assertUnprocessable()->assertJsonValidationErrors(['dispositivo_id', 'tipo_dispo_id']);
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createPlayer(
        string $cedula,
        string $codigo,
        int $stateId,
        string $roleName,
        string $email = 'omar@example.test',
    ): int {
        $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id')
            ?? DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->where('nombre', $roleName)->value('id')
            ?? DB::table('rols')->insertGetId([
                'nombre' => $roleName,
                'descripcion' => 'Rol de prueba',
            ]);
        $userId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Omar',
            'apellidos' => 'Jordan',
            'cedula' => $cedula,
            'codigo' => $codigo,
            'year_nace' => 2000,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sesiones')->insert([
            'usuario_id' => $userId,
            'email' => $email,
            'password' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $userId;
    }

    private function createReservation(
        int $userId,
        int $typeId,
        int $stateId,
        string $start,
        int $duration,
    ): void {
        DB::table('reservas')->insert([
            'usuario_id' => $userId,
            'tipo_dispo_id' => $typeId,
            'evento_id' => null,
            'estado_id' => $stateId,
            'inicio' => $start,
            'duracion_min' => $duration,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAssociation(
        int $userId,
        ?int $deviceId,
        int $stateId,
        string $start,
        string $end,
    ): void {
        DB::table('asociaciones')->insert([
            'usuario_id' => $userId,
            'dispositivo_id' => $deviceId,
            'evento_id' => null,
            'reserva_id' => null,
            'estado_id' => $stateId,
            'inicio' => $start,
            'final' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
