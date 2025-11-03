<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Parking</title>

    {{-- CSS --}}
     @vite(['resources/css/styles.css'])
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">
                <a href="{{ url('/') }}">
                    <span class="logo-icon" aria-label="Parking logo" role="img">🅿️</span>
                    <span class="logo-text">Parking</span>
                </a>
            </div>

            <div class="header-actions">
                <div class="user-menu">
                    @if(session('vigilante'))
                        <span>Hola, {{ session('vigilante.nombre') }}</span>
                        <nav>
                            <a href="{{ route('perfil.edit') }}" class="nav-link">Editar Perfil</a> |
                            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-ghost logout-button">Cerrar sesión</button>
                            </form>
                        </nav>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesión</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="main-scroll">
        @yield('content')
    </main>

    {{-- JS --}}
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/login.js') }}" defer></script>
    <script src="{{ asset('js/register.js') }}" defer></script>
    <script src="{{ asset('js/dashboard_vehiculos.js') }}" defer></script>
</body>
</html>
