<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateDispositivoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_form_lists_device_types(): void
    {
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Equipo de escritorio',
        ]);

        $this->get(route('dispositivos.create'))
            ->assertOk()
            ->assertSee('name="nombre" value="" maxlength="24" pattern=".*\S.*"', false)
            ->assertSee('placeholder="Ej. PC Gamer 01" required', false)
            ->assertSee('<option value="'.$typeId.'"', false)
            ->assertSee('Computador');
    }

    public function test_valid_submission_creates_active_device_with_empty_optional_values(): void
    {
        $activeStateId = DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'descripcion' => 'Estado activo',
        ]);
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Equipo de escritorio',
        ]);

        $this->post(route('dispositivos.store'), [
            'nombre' => 'PC Gamer 01',
            'tipo_dispo_id' => $typeId,
        ])
            ->assertRedirect(route('dispositivos.create'))
            ->assertSessionHas('success', 'El dispositivo se creó correctamente.');

        $this->assertDatabaseHas('dispositivos', [
            'nombre' => 'PC Gamer 01',
            'direccion' => '',
            'password' => '',
            'tipo_dispo_id' => $typeId,
            'estado_id' => $activeStateId,
            'descripcion' => '',
        ]);
    }

    public function test_empty_device_name_is_rejected(): void
    {
        $typeId = DB::table('tipo_dispos')->insertGetId([
            'nombre' => 'Computador',
            'descripcion' => 'Equipo de escritorio',
        ]);

        $this->post(route('dispositivos.store'), [
            'nombre' => ' ',
            'tipo_dispo_id' => $typeId,
        ])
            ->assertSessionHasErrors([
                'nombre' => 'El nombre del dispositivo es obligatorio.',
            ]);

        $this->assertDatabaseCount('dispositivos', 0);
    }
}
