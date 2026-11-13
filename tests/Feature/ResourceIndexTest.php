<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResourceIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_device_index_lists_types_and_only_states_used_by_devices(): void
    {
        $deviceStateId = $this->createState('Operativo');
        $eventStateId = $this->createState('Evento pendiente');
        $typeId = $this->createDeviceType('Computador');
        $this->createDevice('PC Gamer 01', $typeId, $deviceStateId);
        $organizerId = $this->createUser('123456789', $deviceStateId);
        $this->createEvent('Torneo', $eventStateId, $organizerId, '2026-01-01 10:00:00');

        $this->get(route('dispositivos.index'))
            ->assertOk()
            ->assertSee('<option value="'.$typeId.'">Computador</option>', false)
            ->assertSee('<option value="'.$deviceStateId.'">Operativo</option>', false)
            ->assertDontSee('<option value="'.$eventStateId.'">Evento pendiente</option>', false)
            ->assertSee('data-resource-search-form', false)
            ->assertSee('data-resource-results', false);
    }

    public function test_device_search_filters_by_type_and_state_and_uses_cursor_pages(): void
    {
        config()->set('app.dispositivos_cursor_size', 1);
        $activeStateId = $this->createState('Operativo');
        $blockedStateId = $this->createState('Bloqueado');
        $computerTypeId = $this->createDeviceType('Computador');
        $consoleTypeId = $this->createDeviceType('Consola');
        $olderId = $this->createDevice('PC Gamer 01', $computerTypeId, $activeStateId, '2026-01-01 10:00:00');
        $newerId = $this->createDevice('PC Gamer 02', $computerTypeId, $activeStateId, '2026-01-02 10:00:00');
        $this->createDevice('Consola 01', $consoleTypeId, $activeStateId);
        $this->createDevice('PC Gamer 03', $computerTypeId, $blockedStateId);

        $firstPage = $this->getJson(route('dispositivos.search', [
            'tipo_dispo_id' => $computerTypeId,
            'estado_id' => $activeStateId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerId)
            ->assertJsonPath('data.0.tipo', 'Computador')
            ->assertJsonPath('has_more', true);

        $this->getJson(route('dispositivos.search', [
            'tipo_dispo_id' => $computerTypeId,
            'estado_id' => $activeStateId,
            'cursor' => $firstPage->json('next_cursor'),
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $olderId)
            ->assertJsonPath('has_more', false);
    }

    public function test_device_detail_returns_device_information_without_password(): void
    {
        $stateId = $this->createState('Operativo');
        $typeId = $this->createDeviceType('Computador');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $stateId, password: 'internal-secret');

        $this->getJson(route('dispositivos.detail', $deviceId))
            ->assertOk()
            ->assertJsonPath('data.nombre', 'PC Gamer 01')
            ->assertJsonPath('data.tipo', 'Computador')
            ->assertJsonPath('data.estado', 'Operativo')
            ->assertJsonPath('data.descripcion', 'Equipo de prueba')
            ->assertJsonPath('data.direccion', '192.0.2.10')
            ->assertDontSee('internal-secret');
    }

    public function test_event_index_lists_only_states_used_by_events(): void
    {
        $eventStateId = $this->createState('Programado');
        $deviceStateId = $this->createState('Solo dispositivos');
        $organizerId = $this->createUser('123456789', $eventStateId);
        $this->createEvent('Torneo', $eventStateId, $organizerId, '2026-01-01 10:00:00');
        $typeId = $this->createDeviceType('Computador');
        $this->createDevice('PC Gamer 01', $typeId, $deviceStateId);

        $this->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('<option value="'.$eventStateId.'">Programado</option>', false)
            ->assertDontSee('<option value="'.$deviceStateId.'">Solo dispositivos</option>', false)
            ->assertSee('name="usuario_cedula"', false)
            ->assertSee('data-resource-search-form', false)
            ->assertSee('data-resource-results', false);
    }

    public function test_event_search_filters_by_state_and_organizer_and_uses_start_date_cursor(): void
    {
        config()->set('app.eventos_cursor_size', 1);
        $activeStateId = $this->createState('Programado');
        $otherStateId = $this->createState('Cancelado');
        $organizerId = $this->createUser('CC-123456', $activeStateId);
        $otherOrganizerId = $this->createUser('CC-987654', $activeStateId);
        $laterEventId = $this->createEvent('Torneo tarde', $activeStateId, $organizerId, '2026-02-02 12:30:00');
        $sameTimeEventId = $this->createEvent('Torneo misma hora', $activeStateId, $organizerId, '2026-02-02 12:30:00');
        $earlierEventId = $this->createEvent('Torneo temprano', $activeStateId, $organizerId, '2026-02-01 10:00:00');
        $this->createEvent('Otro organizador', $activeStateId, $otherOrganizerId, '2026-01-01 10:00:00');
        $this->createEvent('Cancelado', $otherStateId, $organizerId, '2026-01-02 10:00:00');

        $firstPage = $this->getJson(route('eventos.search', [
            'usuario_cedula' => '123456',
            'estado_id' => $activeStateId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $sameTimeEventId)
            ->assertJsonPath('data.0.inicio', '02/02/2026 - 12:30 PM')
            ->assertJsonPath('has_more', true);

        $secondPage = $this->getJson(route('eventos.search', [
            'usuario_cedula' => '123456',
            'estado_id' => $activeStateId,
            'cursor' => $firstPage->json('next_cursor'),
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $laterEventId)
            ->assertJsonPath('has_more', true);

        $this->getJson(route('eventos.search', [
            'usuario_cedula' => '123456',
            'estado_id' => $activeStateId,
            'cursor' => $secondPage->json('next_cursor'),
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $earlierEventId)
            ->assertJsonPath('has_more', false);
    }

    public function test_event_detail_returns_event_and_linked_device_information(): void
    {
        $stateId = $this->createState('Programado');
        $organizerId = $this->createUser('123456789', $stateId);
        $typeId = $this->createDeviceType('Computador');
        $deviceId = $this->createDevice('PC Gamer 01', $typeId, $stateId);
        $eventId = $this->createEvent('Torneo', $stateId, $organizerId, '2026-02-01 10:00:00', [$deviceId]);

        $this->getJson(route('eventos.detail', $eventId))
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Torneo')
            ->assertJsonPath('data.descripcion', 'Descripción de prueba')
            ->assertJsonPath('data.organizador', 'Usuario Prueba')
            ->assertJsonPath('data.inicio', '01/02/2026 - 10:00 AM')
            ->assertJsonPath('data.duracion', '2 horas')
            ->assertJsonPath('data.aforo', '30 personas')
            ->assertJsonPath('data.estado', 'Programado')
            ->assertJsonPath('data.administracion', 'Permite reservas y asociaciónes')
            ->assertJsonPath('data.dispositivos.0', 'PC Gamer 01');
    }

    public function test_resource_indexes_enable_initial_search(): void
    {
        foreach (['usuarios.index', 'dispositivos.index', 'eventos.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('data-load-initial', false);
        }
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

    private function createDevice(
        string $name,
        int $typeId,
        int $stateId,
        ?string $createdAt = null,
        string $password = '',
    ): int {
        return DB::table('dispositivos')->insertGetId([
            'nombre' => $name,
            'direccion' => '192.0.2.10',
            'password' => $password,
            'tipo_dispo_id' => $typeId,
            'estado_id' => $stateId,
            'descripcion' => 'Equipo de prueba',
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }

    private function createUser(string $cedula, int $stateId): int
    {
        $genderId = DB::table('generos')->where('nombre', 'Indefinido')->value('id')
            ?? DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $roleId = DB::table('rols')->where('nombre', 'Estudiante')->value('id')
            ?? DB::table('rols')->insertGetId([
                'nombre' => 'Estudiante',
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

    /**
     * @param  list<int>  $deviceIds
     */
    private function createEvent(
        string $name,
        int $stateId,
        int $organizerId,
        string $start,
        array $deviceIds = [],
    ): int {
        $eventId = DB::table('eventos')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Descripción de prueba',
            'usuario_id' => $organizerId,
            'inicio' => $start,
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
