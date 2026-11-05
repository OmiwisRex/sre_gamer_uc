<x-mail::message>
# Hola, {{ $nombres }}

Tu cuenta de la comunidad fue creada. Esta es tu contraseña temporal:

**{{ $password }}**

Guárdala de forma segura y no la compartas. Si no solicitaste esta cuenta, contacta al administrador.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
