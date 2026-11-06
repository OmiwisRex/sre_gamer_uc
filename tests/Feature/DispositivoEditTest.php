<?php

namespace Tests\Feature;

use App\Models\Sesion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DispositivoEditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_edit_form_loads_existing_device_values_and_available_options(): void
    {
        $typeId = $this->createType('Computador');
        $otherTypeId = $this->createType('Consola');
        $stateId = $this->createState('Activo');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $stateId);

        $this->get(route('dispositivos.edit', $deviceId))
            ->assertOk()
            ->assertSee('action="'.route('dispositivos.update', $deviceId).'"', false)
            ->assertSee('value="PC Gamer 01"', false)
            ->assertSee('<option value="'.$typeId.'" selected>Computador</option>', false)
            ->assertSee('<option value="'.$otherTypeId.'">Consola</option>', false)
            ->assertSee('<option value="'.$stateId.'" selected>Activo</option>', false)
            ->assertSee('Equipo de prueba');
    }

    public function test_valid_update_changes_device_attributes_and_keeps_private_values(): void
    {
        $typeId = $this->createType('Computador');
        $newTypeId = $this->createType('Consola');
        $stateId = $this->createState('Activo');
        $newStateId = $this->createState('Bloqueado');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $stateId);

        $this->put(route('dispositivos.update', $deviceId), [
            'nombre' => 'PC Gamer Principal',
            'tipo_dispo_id' => $newTypeId,
            'estado_id' => $newStateId,
            'descripcion' => 'Descripción actualizada',
        ])
            ->assertRedirect(route('dispositivos.index'))
            ->assertSessionHas('success', 'El dispositivo se actualizó correctamente.');

        $this->assertDatabaseHas('dispositivos', [
            'id' => $deviceId,
            'nombre' => 'PC Gamer Principal',
            'tipo_dispo_id' => $newTypeId,
            'estado_id' => $newStateId,
            'descripcion' => 'Descripción actualizada',
            'direccion' => '192.0.2.10',
        ]);
        $this->assertSame(
            'private-device-password',
            DB::table('dispositivos')->where('id', $deviceId)->value('password'),
        );
    }

    public function test_update_rejects_an_invalid_type_without_changing_the_device(): void
    {
        $typeId = $this->createType('Computador');
        $stateId = $this->createState('Activo');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $stateId);

        $this->put(route('dispositivos.update', $deviceId), [
            'nombre' => 'PC Gamer Renombrado',
            'tipo_dispo_id' => 999999,
            'estado_id' => $stateId,
            'descripcion' => 'Descripción',
        ])
            ->assertSessionHasErrors('tipo_dispo_id');

        $this->assertDatabaseHas('dispositivos', [
            'id' => $deviceId,
            'nombre' => 'PC Gamer 01',
            'tipo_dispo_id' => $typeId,
        ]);
    }

    public function test_non_master_cannot_set_device_state_to_deleted(): void
    {
        $typeId = $this->createType('Computador');
        $activeStateId = $this->createState('Activo');
        $deletedStateId = $this->createState('Eliminado');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $activeStateId);

        $this->put(route('dispositivos.update', $deviceId), [
            'nombre' => 'PC Gamer 01',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $deletedStateId,
            'descripcion' => 'Equipo de prueba',
        ])
            ->assertSessionHasErrors([
                'estado_id' => 'El estado no existe o solo el administrador Master puede eliminar un dispositivo.',
            ]);

        $this->assertDatabaseHas('dispositivos', [
            'id' => $deviceId,
            'estado_id' => $activeStateId,
        ]);
    }

    public function test_master_can_set_device_state_to_deleted(): void
    {
        $typeId = $this->createType('Computador');
        $activeStateId = $this->createState('Activo');
        $deletedStateId = $this->createState('Eliminado');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $activeStateId);
        $master = $this->createMasterSession($activeStateId);

        $this->actingAs($master)
            ->put(route('dispositivos.update', $deviceId), [
                'nombre' => 'PC Gamer 01',
                'tipo_dispo_id' => $typeId,
                'estado_id' => $deletedStateId,
                'descripcion' => 'Equipo de prueba',
            ])
            ->assertRedirect(route('dispositivos.index'))
            ->assertSessionHas('success', 'El dispositivo se actualizó correctamente.');

        $this->assertDatabaseHas('dispositivos', [
            'id' => $deviceId,
            'estado_id' => $deletedStateId,
        ]);
    }

    private function createType(string $name): int
    {
        return DB::table('tipo_dispos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Tipo de prueba',
        ]);
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado de prueba',
        ]);
    }

    private function createDevice(string $name, int $typeId, int $stateId): int
    {
        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '192.0.2.10',
            'password' => 'private-device-password',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $stateId,
            'descripcion' => 'Equipo de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMasterSession(int $stateId): Sesion
    {
        $genderId = DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->insertGetId([
            'nombre' => 'Master',
            'descripcion' => 'Administrador de prueba',
        ]);
        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombres' => 'Admin',
            'apellidos' => 'Master',
            'cedula' => '987654321',
            'codigo' => 'MASTER-001',
            'year_nace' => 1980,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sessionId = DB::table('sesiones')->insertGetId([
            'usuario_id' => $usuarioId,
            'email' => 'master@example.test',
            'password' => Hash::make('master-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Sesion::query()->findOrFail($sessionId);
    }
}
