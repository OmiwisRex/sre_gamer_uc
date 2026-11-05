<?php

namespace Tests\Feature\Mail;

use App\Mail\UsuarioAccesoMail;
use Tests\TestCase;

class UsuarioAccesoMailTest extends TestCase
{
    public function test_mail_escapes_user_supplied_names_and_includes_temporary_password(): void
    {
        $content = (new UsuarioAccesoMail('<script>alert(1)</script>', 'GS2T80'))->render();

        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
        $this->assertStringContainsString('GS2T80', $content);
    }
}
