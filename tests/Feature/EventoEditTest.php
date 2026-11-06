<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventoEditTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_edit_form_loads_existing_event_and_linked_devices(): void
    {
        $stateId = $this->createState('Programado');
        $organizerId = $this->createUser($stateId);
        $deviceId = $this->createDevice($stateId, 'PC Gamer 01');
        $eventId = $this->createEvent($stateId, $organizerId, [$deviceId]);

        $this->get(route('eventos.edit', $eventId))
            ->assertOk()
            ->assertSee('action="'.route('eventos.update', $eventId).'"', false)
            ->assertSee('value="Torneo de prueba"', false)
            ->assertSee('value="123456789"', false)
            ->assertSee('<option value="'.$stateId.'" selected>Programado</option>', false)
            ->assertSee('name="dispositivos[]" value="'.$deviceId.'" checked', false);
    }

    public function test_valid_update_changes_event_data_and_synchronizes_linked_devices(): void
    {
        $stateId = $this->createState('Programado');
        $newStateId = $this->createState('Cancelado');
        $organizerId = $this->createUser($stateId);
        $firstDeviceId = $this->createDevice($stateId, 'PC Gamer 01');
        $secondDeviceId = $this->createDevice($stateId, 'PC Gamer 02');
        $eventId = $this->createEvent($stateId, $organizerId, [$firstDeviceId]);

        $this->put(route('eventos.update', $eventId), [
            'nombre' => 'Torneo actualizado',
            'organizador_cedula' => '123456789',
            'inicio' => '2026-03-16T14:30',
            'duracion_horas' => '3',
            'aforo' => '40',
            'estado_id' => $newStateId,
            'auto_admin' => '0',
            'descripcion' => 'Nueva descripción',
            'dispositivos' => [(string) $secondDeviceId],
        ])
            ->assertRedirect(route('eventos.index'))
            ->assertSessionHas('success', 'El evento se actualizó correctamente.');

        $this->assertDatabaseHas('eventos', [
            'id' => $eventId,
            'nombre' => 'Torneo actualizado',
            'inicio' => '2026-03-16 14:30:00',
            'duracion_horas' => 3,
            'aforo' => 40,
            'estado_id' => $newStateId,
            'auto_admin' => false,
            'descripcion' => 'Nueva descripción',
        ]);
        $this->assertDatabaseHas('evento_dispos', [
            'evento_id' => $eventId,
            'dispositivo_id' => $secondDeviceId,
        ]);
        $this->assertDatabaseMissing('evento_dispos', [
            'evento_id' => $eventId,
            'dispositivo_id' => $firstDeviceId,
        ]);
    }

    public function test_update_rejects_an_unknown_organizer_and_keeps_event_unchanged(): void
    {
        $stateId = $this->createState('Programado');
        $organizerId = $this->createUser($stateId);
        $eventId = $this->createEvent($stateId, $organizerId);

        $this->put(route('eventos.update', $eventId), [
            'nombre' => 'Torneo actualizado',
            'organizador_cedula' => '999999999',
            'inicio' => '2026-03-16T14:30',
            'duracion_horas' => '2',
            'aforo' => '30',
            'estado_id' => $stateId,
            'auto_admin' => '1',
        ])
            ->assertSessionHasErrors([
                'organizador_cedula' => 'No se encontró un usuario con esa cédula.',
            ]);

        $this->assertDatabaseHas('eventos', [
            'id' => $eventId,
            'nombre' => 'Torneo de prueba',
        ]);
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createUser(int $stateId): int
    {
        $genderId = DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->insertGetId([
            'nombre' => 'Estudiante',
            'descripcion' => 'Rol de prueba',
        ]);

        return DB::table('usuarios')->insertGetId([
            'nombres' => 'Ana',
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
    }

    private function createDevice(int $stateId, string $name): int
    {
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Tipo '.$name,
            'descripcion' => 'Equipo de prueba',
        ]);

        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $stateId,
            'descripcion' => 'Equipo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<int>  $deviceIds
     */
    private function createEvent(int $stateId, int $organizerId, array $deviceIds = []): int
    {
        $eventId = DB::table('eventos')->insertGetId([
            'nombre' => 'Torneo de prueba',
            'descripcion' => 'Descripción original',
            'usuario_id' => $organizerId,
            'inicio' => '2026-03-15 13:45:00',
            'duracion_horas' => 2,
            'aforo' => 30,
            'estado_id' => $stateId,
            'auto_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($deviceIds as $deviceId) {
            DB::table('evento_dispos')->insert([
                'evento_id' => $eventId,
                'dispositivo_id' => $deviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $eventId;
    }
}
