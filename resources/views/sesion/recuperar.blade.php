<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="event-expiration-url" content="{{ route('eventos.expire') }}">
    <title>Recuperar contraseña | SGUC</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-institucional text-gris-oscuro">
    <main class="sre-auth-page">
        <section class="sre-auth-card" aria-labelledby="recover-title">
            <a class="sre-auth-logo" href="{{ route('index') }}" aria-label="Ir a la página principal de SGUC">
                <img src="{{ asset('img/logo_color.png') }}" alt="Logo de Unicatólica">
            </a>
            <div class="sre-auth-heading">
                <p class="sre-eyebrow">Sala Gamer UC</p>
                <h1 class="sre-auth-title" id="recover-title">Recuperar contraseña</h1>
                <p class="sre-auth-description">Escribe el correo asociado a tu cuenta y te enviaremos una nueva contraseña.</p>
            </div>

            @if (session('success'))
                <p class="sre-hint" role="status">{{ session('success') }}</p>
            @endif
            @if ($errors->any())
                <ul class="sre-hint" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form class="sre-auth-form" action="{{ route('sesion.recover') }}" method="POST">
                @csrf
                <label class="sre-field">
                    <span class="sre-label">Correo electrónico</span>
                    <input class="sre-input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nombre@dominio.com" required>
                </label>
                <button class="sre-button sre-auth-submit" type="submit">Enviar nueva contraseña</button>
            </form>

            <a class="sre-auth-link" href="{{ route('sesion.login') }}">¿Ya tienes cuenta? Volver al inicio de sesión</a>
        </section>
        <footer class="sre-auth-footer">
            Unicatólica - Universidad Católica de Colombia Lumen Gentium &copy; {{ date('Y') }}.
        </footer>
    </main>
</body>
</html>