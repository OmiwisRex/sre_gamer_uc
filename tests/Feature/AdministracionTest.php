<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use LazilyRefreshDatabase;

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
            'auto_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->travelTo(Carbon::parse('2026-03-15 13:45:00'));

        $response = $this->get(route('administracion.index'));

        $response->assertOk()
            ->assertSee('<option value="'.$tipoDispoId.'" title="Tipo de dispositivo">Tipo de dispositiv...</option>', false)
            ->assertSee('<option value="'.$dispositivoId.'" title="Dispositivo Gamer UC Pro">Dispositivo Gamer...</option>', false)
            ->assertSee('<option value="'.$eventoId.'" title="Evento de prueba nombre extenso">Evento de prueba n...</option>', false)
            ->assertSee('name="fecha_reserva" value="2026-03-15"', false)
            ->assertSee('name="hora_reserva" value="13:45"', false);

        foreach ([30, 60, 90, 120, 150, 180] as $duration) {
            $response->assertSee('<option value="'.$duration.'">'.$duration.' min</option>', false);
        }
    }

    public function test_association_form_carries_reservation_id_and_shows_reserved_badge_when_provided(): void
    {
        $response = $this->get(route('administracion.index', ['reserva_id' => 42]));

        $response->assertOk()
            ->assertSee('<span class="sre-badge">Reservado</span>', false)
            ->assertSee('<input type="hidden" name="reserva_id" value="42">', false);
    }

    public function test_association_form_has_empty_reservation_id_and_no_reserved_badge_when_not_provided(): void
    {
        $response = $this->get(route('administracion.index'));

        $response->assertOk()
            ->assertDontSee('<span class="sre-badge">Reservado</span>', false)
            ->assertSee('<input type="hidden" name="reserva_id" value="">', false);
    }
}
