@extends('layout-login-register')
@section('content')

  @vite([
    'resources/css/app.css',
    'resources/css/styles.css',
    'resources/css/login.css',
    'resources/js/app.js',
    'resources/js/login.js',
    'resources/js/register.js',
    'resources/js/dashboard_vehiculos.js'
  ])

<div class="page"><!-- <- contenedor GRID: visual (izq) | auth (der) -->
  <!-- visual -->
  <section class="visual" aria-hidden="true">
    <div class="visual-left">
      <!-- dejamos el <img> para conservación y acceso por SEO/alt, pero lo forzamos a cubrir -->
      <img src="{{ asset('images/carro-login.avif') }}" alt="Carro">
    </div>

    <div class="visual-right">
      <img src="{{ asset('images/estrellas-logo.avif') }}" alt="Estrellas">
    </div>
  </section>

  <!-- auth (formulario) -->
  <section class="auth">
    <div class="card" role="region" aria-labelledby="signin-title">
      <h1 id="signin-title">Sign In</h1>
      <p class="subtitle">Parking Payment</p>

      @if ($errors->any())
        <div class="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('status'))
        <div class="status">{{ session('status') }}</div>
      @endif

      <form id="loginForm" action="{{ route('login') }}" method="POST" novalidate>
        @csrf

        <label class="input-group" for="correo">
          <span class="label-text">Correo</span>
          <input id="correo" name="correo" type="email" placeholder="correo@ejemplo.com" required value="{{ old('correo') }}">
        </label>

        <label class="input-group" for="contrasena">
          <span class="label-text">Contraseña</span>
          <input id="contrasena" name="contrasena" type="password" placeholder="Contraseña" required minlength="8" autocomplete="current-password">
        </label>

        <div class="row-between">
          <label class="checkbox">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span>Recordarme</span>
          </label>
          {{-- <a class="forgot" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a> --}}
        </div>

        <button type="submit" class="primary-btn">Sign In</button>
      </form>

      <p class="small">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}">Regístrate</a>
      </p>
    </div>
  </section>

</div>

@if(session('error'))
  <div class="flash flash-error">{{ session('error') }}</div>
@endif


@endsection