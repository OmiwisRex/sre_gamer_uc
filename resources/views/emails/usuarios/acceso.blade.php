<x-mail::message>
# Hola, {{ $nombres }}

Esta es tu contraseña temporal para acceder a tu cuenta:

**{{ $password }}**

Guárdala de forma segura y no la compartas. Si no esperabas este mensaje, contacta al administrador.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
