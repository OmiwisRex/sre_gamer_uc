<?php

namespace Tests;

use App\Models\Rol;
use App\Models\Sesion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function signInAsRole(string $roleName = 'Administrador'): void
    {
        $role = new Rol(['nombre' => $roleName]);
        $usuario = new Usuario([
            'nombres' => 'Usuario',
            'apellidos' => 'Prueba',
        ]);
        $usuario->setRelation('rol', $role);

        $sesion = new Sesion;
        $sesion->forceFill([
            'id' => 1,
            'email' => 'usuario@example.test',
        ]);
        $sesion->setRelation('usuario', $usuario);

        $this->actingAs($sesion);
    }
}
