<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Parking — Dashboard</title>

  <!-- CSS desde public/ -->
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

      <nav class="main-nav" aria-label="Main navigation">
        <!-- Importante: apuntar a la ruta raíz con el hash para que funcione desde cualquier página -->
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

  <!-- JS desde public/ -->
  <script src="{{ asset('js/app.js') }}" defer></script>
  <script src="{{ asset('js/login.js')}}" defer></script>
  <script src="{{ asset('JS/register.js')}}" defer></script>
  <script src="{{ asset('js/dashboard_vehiculos.js') }}"></script>
</body>
</html>