<?php

namespace App\Services;

use App\Mail\UsuarioAccesoMail;
use App\Models\Sesion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsuarioAccesoService
{
    public function sendTemporaryPassword(Sesion $sesion, string $nombres): string
    {
        $password = Str::upper(Str::random(6));
        $sesion->password = $password;
        $sesion->save();

        Mail::to($sesion->email)->send(new UsuarioAccesoMail($nombres, $password));

        return $password;
    }
}
