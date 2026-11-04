<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | SGUC</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-institucional text-gris-oscuro">
    <main class="sre-auth-page">
        <section class="sre-auth-card" aria-labelledby="login-title">
            <a class="sre-auth-logo" href="{{ route('index') }}" aria-label="Ir a la página principal de SGUC">
                <img src="{{ asset('img/logo_color.png') }}" alt="Logo de Unicatólica">
            </a>
            <div class="sre-auth-heading">
                <p class="sre-eyebrow">Sala Gamer UC</p>
                <h1 class="sre-auth-title" id="login-title">Iniciar sesión</h1>
                <p class="sre-auth-description">Ingresa tus credenciales para continuar.</p>
            </div>

            <form class="sre-auth-form">
                <label class="sre-field">
                    <span class="sre-label">Correo electrónico</span>
                    <input class="sre-input" type="email" name="email" autocomplete="username" placeholder="nombre@dominio.com">
                </label>
                <label class="sre-field">
                    <span class="sre-label">Contraseña</span>
                    <input class="sre-input" type="password" name="password" autocomplete="current-password" placeholder="Ingresa tu contraseña">
                </label>
                <button class="sre-button sre-auth-submit" type="button">Ingresar</button>
            </form>

            <a class="sre-auth-link" href="{{ route('sesion.recuperar') }}">¿Olvidaste tu contraseña?</a>
        </section>
        <footer class="sre-auth-footer">
            Unicatólica - Universidad Católica de Colombia Lumen Gentium &copy; {{ date('Y') }}.
        </footer>
    </main>
</body>
</html>