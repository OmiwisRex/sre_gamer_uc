<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SGUC')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-institucional text-gris-oscuro">
    @php
        $signedInUser = auth()->user();
        $menuItems = [
            ['label' => 'Principal', 'route' => 'index'],
            ['label' => 'Administración', 'route' => 'administracion.index'],
            ['label' => 'Usuarios', 'route' => 'usuarios.index'],
            ['label' => 'Eventos', 'route' => 'eventos.index'],
            ['label' => 'Dispositivos', 'route' => 'dispositivos.index'],
            ['label' => 'Observaciones', 'route' => 'observaciones.index'],
            ['label' => 'Estadísticas', 'route' => 'estadisticas.index'],
            ['label' => 'Auditorías', 'route' => 'auditorias.index'],
            ['label' => 'Configuración', 'route' => 'configuracion.index'],
        ];
    @endphp

    <div class="flex h-screen min-h-[480px] flex-col">
        <header class="z-10 h-20 shrink-0 border-b-2 border-aqua-uno bg-white shadow-sm">
            <div class="flex h-full items-center justify-between gap-4 px-4">
                <div class="flex w-24 shrink-0 items-center">
                    <a href="{{ route('index') }}" aria-label="SGUC, página principal">
                        <img
                            src="{{ asset('img/logo_color.png') }}"
                            alt="Logo institucional"
                            class="h-25 w-auto max-w-45 object-contain"
                        >
                    </a>
                </div>
                <div class="min-w-0 flex-1 text-center">
                    <h1 class="sre-title m-0 whitespace-nowrap font-bold tracking-wide text-azul-oscuro">Sala Gamer UC</h1>
                    <p class="m-0 whitespace-nowrap text-xs text-gris-claro">Sistema de gestión de sala gamer</p>
                </div>
                <div class="flex w-60 shrink-0 justify-end self-start pt-1">
                    <div class="flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-azul-medio text-sm font-bold text-white">
                            {{ mb_substr($signedInUser?->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="min-w-0 text-right leading-tight">
                            <p class="m-0 truncate text-sm font-bold text-azul-oscuro">
                                {{ $signedInUser?->name ?? 'Usuario' }}
                            </p>
                            <p class="m-0 truncate text-xs text-gris-claro">
                                {{ $signedInUser?->email ?? 'Correo institucional' }}
                            </p>
                            <p class="m-0 text-[0.7rem] font-semibold text-aqua-dos">
                                {{ $signedInUser?->getAttribute('role') ?? 'Rol' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <aside class="w-52 shrink-0 bg-azul-oscuro px-2.5 py-5 text-white">
                <p class="mb-3 px-2.5 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-white/55">
                    Menú principal
                </p>
                <nav class="flex flex-col gap-1" aria-label="Navegación principal">
                    @foreach ($menuItems as $item)
                        @php
                            $isActive = request()->routeIs($item['route']);
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'sre-sidebar-link flex items-center gap-3 whitespace-nowrap rounded-lg px-2.5 py-2.5 text-sm font-semibold transition-colors',
                                'bg-aqua-uno text-white shadow-sm hover:bg-aqua-dos' => $isActive,
                                'text-white/75 hover:bg-white/10 hover:text-white' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            <span class="size-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <main class="min-h-0 min-w-0 flex-1 overflow-hidden bg-white">
                @yield('content')
            </main>
        </div>

        <footer class="flex h-10 shrink-0 items-center justify-center border-t border-slate-200 bg-white px-4 text-center text-xs text-gris-claro">
            Unicatólica - Universidad Católica de Colombia Lumen Gentium &copy; {{ date('Y') }}. Todos los derechos reservados.
        </footer>
    </div>
</body>
</html>
