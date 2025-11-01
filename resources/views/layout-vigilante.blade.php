<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Parking</title>

    {{-- css --}}
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">
                <a href="{{ url('/') }}">
                    <span class="logo-icon">🅿️</span>
                    <span class="logo-text">Parking</span>
                </a>
            </div>

            {{-- Perfil y notificaciones --}}
            <div>

            </div>
        </div>
    </header>

    <main id="main" class="main-scroll">
        @yield('content')
    </main>

    {{-- JS desde public/ --}}
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/login.js')}}" defer></script>
    <script src="{{ asset('JS/register.js')}}" defer></script>
    <script src="{{ asset('js/dashboard_vehiculos.js') }}"></script>
</body>
</html>