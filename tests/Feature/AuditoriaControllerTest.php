<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_index_loads_filter_options_and_search_interface(): void
    {
        $usuario = $this->createUser('123', 'Ana', 'Pérez');
        $accion = $this->createAction('Creó usuario');
        $this->createAudit($usuario, $accion, 'usuarios', 7, '2026-03-10 11:00:00');

        $this->get(route('auditorias.index'))
            ->assertOk()
            ->assertSee('Creó usuario')
            ->assertSee('<option value="usuarios"', false)
            ->assertSee('data-resource-search-form', false)
            ->assertSee('data-resource-results', false)
            ->assertSee(route('auditorias.search'));
    }

    public function test_search_applies_each_optional_filter_and_combines_them(): void
    {
        $ana = $this->createUser('123456', 'Ana María', 'Pérez López');
        $luis = $this->createUser('987654', 'Luis', 'Rojas');
        $create = $this->createAction('Creó usuario');
        $edit = $this->createAction('Editó usuario');
        $matching = $this->createAudit($ana, $create, 'usuarios', 7, '2026-03-10 11:00:00');
        $this->createAudit($ana, $edit, 'dispositivos', 8, '2026-03-11 11:00:00');
        $this->createAudit($luis, $create, 'usuarios', 9, '2026-03-11 12:00:00');

        foreach ([
            ['cedula' => '123456'],
            ['nombre' => 'María Pérez'],
            ['accion_id' => $edit],
            ['tabla' => 'dispositivos'],
            ['fecha' => '2026-03-10'],
        ] as $filter) {
            $expectedCount = isset($filter['cedula']) || isset($filter['nombre']) ? 2 : 1;
            $this->getJson(route('auditorias.search', $filter))->assertOk()->assertJsonCount($expectedCount, 'data');
        }

        $this->getJson(route('auditorias.search', [
            'cedula' => '123',
            'nombre' => 'Ana Pérez',
            'accion_id' => $create,
            'tabla' => 'usuarios',
            'fecha' => '2026-03-10',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching)
            ->assertJsonPath('data.0.fecha', '10/03/2026 11:00')
            ->assertJsonPath('data.0.accion', 'Creó usuario')
            ->assertJsonPath('data.0.tabla', 'usuarios')
            ->assertJsonPath('data.0.indice', 7)
            ->assertJsonPath('data.0.usuario', 'Ana María Pérez López')
            ->assertJsonPath('data.0.cedula', '123456');
    }

    public function test_cursor_preserves_filtered_results_with_identical_timestamps(): void
    {
        config()->set('app.auditorias_cursor_size', 1);
        $usuario = $this->createUser('123', 'Ana', 'Pérez');
        $accion = $this->createAction('Creó usuario');
        $older = $this->createAudit($usuario, $accion, 'usuarios', 1, '2026-03-10 11:00:00');
        $newer = $this->createAudit($usuario, $accion, 'usuarios', 2, '2026-03-10 11:00:00');
        $this->createAudit($usuario, $accion, 'dispositivos', 3, '2026-03-10 12:00:00');

        $first = $this->getJson(route('auditorias.search', ['tabla' => 'usuarios']))
            ->assertOk()->assertJsonPath('data.0.id', $newer)->assertJsonPath('has_more', true);

        $this->getJson(route('auditorias.search', ['tabla' => 'usuarios', 'cursor' => $first->json('next_cursor')]))
            ->assertOk()->assertJsonPath('data.0.id', $older)->assertJsonPath('has_more', false);
    }

    public function test_empty_filters_return_all_audits_and_invalid_filters_are_rejected(): void
    {
        $usuario = $this->createUser('123', 'Ana', 'Pérez');
        $accion = $this->createAction('Creó usuario');
        $this->createAudit($usuario, $accion, 'usuarios', 1, '2026-03-10 11:00:00');

        $this->getJson(route('auditorias.search', ['cedula' => '', 'nombre' => '', 'accion_id' => '', 'tabla' => '', 'fecha' => '']))
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('auditorias.search', ['fecha' => 'no-es-fecha', 'accion_id' => 999]))
            ->assertUnprocessable()->assertJsonValidationErrors(['fecha', 'accion_id']);
    }

    public function test_audit_endpoints_require_administrator_access(): void
    {
        $this->signInAsRole('Estudiante');

        $this->get(route('auditorias.index'))->assertForbidden();
        $this->getJson(route('auditorias.search'))->assertForbidden();
    }

    private function createUser(string $cedula, string $nombres, string $apellidos): int
    {
        $gender = DB::table('generos')->where('nombre', 'Indefinido')->value('id')
            ?? DB::table('generos')->insertGetId(['nombre' => 'Indefinido']);
        $role = DB::table('rols')->where('nombre', 'Estudiante')->value('id')
            ?? DB::table('rols')->insertGetId(['nombre' => 'Estudiante', 'descripcion' => 'Rol de prueba']);
        $state = DB::table('estados')->where('nombre', 'Activo')->value('id')
            ?? DB::table('estados')->insertGetId(['nombre' => 'Activo', 'descripcion' => 'Estado de prueba']);

        return DB::table('usuarios')->insertGetId([
            'cedula' => $cedula, 'nombres' => $nombres, 'apellidos' => $apellidos,
            'codigo' => 'TEST-'.$cedula, 'year_nace' => 2000, 'genero_id' => $gender,
            'rol_id' => $role, 'estado_id' => $state, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createAction(string $name): int
    {
        return DB::table('acciones')->insertGetId(['nombre' => $name, 'descripcion' => 'Acción de prueba']);
    }

    private function createAudit(int $user, int $action, string $table, int $index, string $createdAt): int
    {
        return DB::table('auditorias')->insertGetId([
            'usuario_id' => $user, 'accion_id' => $action, 'tabla' => $table, 'indice' => $index,
            'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }
}
