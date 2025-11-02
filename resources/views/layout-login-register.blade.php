<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Parking — Dashboard</title>

  {{-- Carga completa de CSS y JS con Vite --}}
  @vite([
    'resources/css/app.css',
    'resources/css/styles.css',
    'resources/js/app.js',
    'resources/js/login.js',
    'resources/js/register.js',
    'resources/js/dashboard_vehiculos.js'
  ])
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

      <nav class="main-nav" aria-label="Main navigation">
        <a class="nav-link" href="{{ url('/').'#servicios' }}">Servicios</a>
        <a class="nav-link" href="{{ url('/').'#acerca' }}">Acerca de</a>
        <a class="nav-link" href="{{ url('/').'#contacto' }}">Contacto</a>
      </nav>

      <div class="header-actions">
        <a href="{{ url('/login') }}" class="btn btn-ghost">Iniciar sesión</a>
        <a href="{{ url('/register') }}" class="btn btn-ghost">Register</a>
      </div>
    </div>
  </header>

  <main id="mainScroll" class="main-scroll">
    @yield('content')
  </main>
</body>
</html>
