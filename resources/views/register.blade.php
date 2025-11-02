@extends('layout-login-register')

@section('content')

<link rel="stylesheet" href="{{ asset('css/register.css') }}">

<div class="page"><!-- grid: visual (izq) | auth (der) -->
  <!-- visual -->
  <section class="visual" aria-hidden="true">
    <div class="visual-left">
      <img src="{{ asset('images/carro-login.avif') }}" alt="Carro">
    </div>

    <div class="visual-right">
      <img src="{{ asset('images/estrellas-logo.avif') }}" alt="Estrellas">
    </div>
  </section>

  <!-- auth (formulario) -->
  <section class="auth">
    <div class="card" role="region" aria-labelledby="register-title">
      <h1 id="register-title">Register</h1>
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

      <form id="registerForm" action="{{ route('register') }}" method="POST" novalidate>
        @csrf

        <label class="input-group" for="nombre">
          <span class="label-text">Nombre</span>
          <input id="nombre" name="nombre" type="text" placeholder="Tu nombre" required value="{{ old('nombre') }}">
        </label>

        <label class="input-group" for="email">
          <span class="label-text">Correo</span>
          <input id="email" name="email" type="email" placeholder="correo@ejemplo.com" required value="{{ old('email') }}">
        </label>

        <label class="input-group" for="telefono">
          <span class="label-text">Teléfono</span>
          <input id="telefono" name="telefono" type="tel" placeholder="+57 300 000 0000" required value="{{ old('telefono') }}">
        </label>

        <label class="input-group" for="password">
          <span class="label-text">Contraseña</span>
          <input id="password" name="password" type="password" placeholder="Contraseña (min 8 caracteres)" required minlength="8" autocomplete="new-password">
        </label>

        <label class="input-group" for="password_confirmation">
          <span class="label-text">Confirmar contraseña</span>
          <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite tu contraseña" required minlength="8" autocomplete="new-password">
        </label>

        <button type="submit" class="primary-btn">Register</button>
      </form>

      <p class="small">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
      </p>
    </div>
  </section>
</div>

@endsection