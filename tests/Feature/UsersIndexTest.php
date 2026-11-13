<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsersIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signInAsRole();
    }

    public function test_index_lists_states_used_by_users_and_excludes_master_role(): void
    {
        $activeStateId = $this->createState('Activo');
        $this->createState('Solo para dispositivos');
        $studentRoleId = $this->createRole('Estudiante');
        $this->createRole('Administrador');
        $masterRoleId = $this->createRole('Master');
        $genderId = $this->createGender('Indefinido');
        $this->createUsuario('123456789', 'EST-001', 2000, $genderId, $activeStateId, $studentRoleId);

        $this->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Activo')
            ->assertDontSee('Solo para dispositivos')
            ->assertSee('Administrador')
            ->assertDontSee('<option value="'.$masterRoleId.'">Master</option>', false)
            ->assertSee('<div class="sre-empty-state" data-user-results-status', false)
            ->assertSee('<strong>No hay usuarios para mostrar</strong>', false)
            ->assertSee('<span>Prueba con otros filtros de búsqueda.</span>', false);
    }

    public function test_search_matches_cedula_or_codigo_and_applies_role_and_state_filters(): void
    {
        $activeStateId = $this->createState('Activo');
        $inactiveStateId = $this->createState('Inactivo');
        $studentRoleId = $this->createRole('Estudiante');
        $adminRoleId = $this->createRole('Administrador');
        $genderId = $this->createGender('Indefinido');
        $cedulaMatchId = $this->createUsuario(
            'CC-112233',
            'EST-001',
            2000,
            $genderId,
            $activeStateId,
            $studentRoleId,
        );
        $codeMatchId = $this->createUsuario(
            'CC-778899',
            'COD-112233',
            2000,
            $genderId,
            $activeStateId,
            $adminRoleId,
        );
        $this->createUsuario('CC-445566', 'OTRO-002', 2000, $genderId, $inactiveStateId, $studentRoleId);

        $this->getJson(route('usuarios.search', [
            'identificacion' => '112233',
            'estado_id' => $activeStateId,
            'rol_id' => $studentRoleId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $cedulaMatchId)
            ->assertJsonPath('has_more', false);

        $this->getJson(route('usuarios.search', ['identificacion' => 'COD-112233']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $codeMatchId);
    }

    public function test_search_returns_users_in_stable_cursor_pages(): void
    {
        config()->set('app.usuarios_cursor_size', 1);
        $stateId = $this->createState('Activo');
        $roleId = $this->createRole('Estudiante');
        $genderId = $this->createGender('Indefinido');
        $olderId = $this->createUsuario(
            'CC-000001',
            'EST-001',
            2000,
            $genderId,
            $stateId,
            $roleId,
            '2025-01-01 10:00:00',
        );
        $newerId = $this->createUsuario(
            'CC-000002',
            'EST-002',
            2000,
            $genderId,
            $stateId,
            $roleId,
            '2025-01-01 11:00:00',
        );

        $firstPage = $this->getJson(route('usuarios.search'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerId)
            ->assertJsonPath('has_more', true);
        $cursor = $firstPage->json('next_cursor');
        $this->assertIsString($cursor);

        $this->getJson(route('usuarios.search', ['cursor' => $cursor]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $olderId)
            ->assertJsonPath('has_more', false);
    }

    public function test_search_treats_identification_as_a_bound_value(): void
    {
        $stateId = $this->createState('Activo');
        $roleId = $this->createRole('Estudiante');
        $genderId = $this->createGender('Indefinido');
        $this->createUsuario('CC-123456', 'EST-001', 2000, $genderId, $stateId, $roleId);

        $this->getJson(route('usuarios.search', ['identificacion' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_guest_detail_hides_internal_code_and_session_email(): void
    {
        $stateId = $this->createState('Activo');
        $roleId = $this->createRole('Invitado');
        $genderId = $this->createGender('Indefinido');
        $usuarioId = $this->createUsuario('CC-123456', 'INV-INTERNAL-CODE', 0, $genderId, $stateId, $roleId);
        $this->createSession($usuarioId, 'invitado+'.$usuarioId.'@example.invalid', 'hash-placeholder-secret');

        $this->getJson(route('usuarios.detail', $usuarioId))
            ->assertOk()
            ->assertJsonPath('data.codigo', null)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.edad', null)
            ->assertDontSee('INV-INTERNAL-CODE')
            ->assertDontSee('invitado+'.$usuarioId.'@example.invalid')
            ->assertDontSee('hash-placeholder-secret');
    }

    public function test_community_detail_returns_public_profile_and_calculated_age(): void
    {
        $this->travelTo('2025-01-02 12:00:00');
        $stateId = $this->createState('Activo');
        $roleId = $this->createRole('Estudiante');
        $genderId = $this->createGender('Mujer');
        $usuarioId = $this->createUsuario('CC-123456', 'EST-0001', 2000, $genderId, $stateId, $roleId);
        $this->createSession($usuarioId, 'estudiante@example.test', 'hashed-password');

        $this->getJson(route('usuarios.detail', $usuarioId))
            ->assertOk()
            ->assertJsonPath('data.codigo', 'EST-0001')
            ->assertJsonPath('data.edad', 25)
            ->assertJsonPath('data.email', 'estudiante@example.test')
            ->assertJsonPath('data.genero', 'Mujer')
            ->assertJsonPath('data.estado', 'Activo')
            ->assertJsonPath('data.rol', 'Estudiante')
            ->assertDontSee('hashed-password');
    }

    public function test_search_returns_422_when_filtering_by_master_role(): void
    {
        $masterRoleId = $this->createRole('Master');

        $this->getJson(route('usuarios.search', ['rol_id' => $masterRoleId]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rol_id');
    }

    public function test_search_excludes_master_users_when_no_role_filter_is_selected(): void
    {
        $stateId = $this->createState('Activo');
        $masterRoleId = $this->createRole('Master');
        $studentRoleId = $this->createRole('Estudiante');
        $genderId = $this->createGender('Indefinido');
        $this->createUsuario('CC-100001', 'USR-001', 1980, $genderId, $stateId, $masterRoleId);
        $studentId = $this->createUsuario('CC-100002', 'EST-001', 2000, $genderId, $stateId, $studentRoleId);

        $this->getJson(route('usuarios.search'))
            ->assertOk()
            ->assertJsonPath('data', fn (array $users): bool => count($users) === 1 && $users[0]['id'] === $studentId);
    }

    public function test_detail_returns_404_for_an_unknown_user(): void
    {
        $this->getJson(route('usuarios.detail', 999999))
            ->assertNotFound();
    }

    private function createState(string $name): int
    {
        return DB::table('estados')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Estado para prueba',
        ]);
    }

    private function createRole(string $name): int
    {
        return DB::table('rols')->insertGetId([
            'nombre' => $name,
            'descripcion' => 'Rol para prueba',
        ]);
    }

    private function createGender(string $name): int
    {
        return DB::table('generos')->insertGetId(['nombre' => $name]);
    }

    private function createUsuario(
        string $cedula,
        string $codigo,
        int $yearOfBirth,
        int $genderId,
        int $stateId,
        int $roleId,
        ?string $createdAt = null,
    ): int {
        return DB::table('usuarios')->insertGetId([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
            'cedula' => $cedula,
            'codigo' => $codigo,
            'year_nace' => $yearOfBirth,
            'genero_id' => $genderId,
            'estado_id' => $stateId,
            'rol_id' => $roleId,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }

    private function createSession(int $usuarioId, string $email, string $password): void
    {
        DB::table('sesiones')->insert([
            'usuario_id' => $usuarioId,
            'email' => $email,
            'password' => $password,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
