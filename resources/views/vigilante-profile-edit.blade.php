@extends('layout-vigilante')

@section('content')
 @vite([
    'resources/css/styles.css',
  ])
<div class="container">
    <h1>Editar Perfil</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('perfil.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group mt-3">
            <label for="nombre">Nombre</label>
            <input
                id="nombre"
                name="nombre"
                type="text"
                class="form-control @error('nombre') is-invalid @enderror"
                value="{{ old('nombre', $vigilante->nombre) }}"
                required
            >
            @error('nombre')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mt-3">
            <label for="correo">Correo Electrónico</label>
            <input
                id="correo"
                name="correo"
                type="email"
                class="form-control @error('correo') is-invalid @enderror"
                value="{{ old('correo', $vigilante->correo) }}"
                required
            >
            @error('correo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mt-3">
            <label for="contrasena">Nueva Contraseña (dejar vacío para no cambiar)</label>
            <input
                id="contrasena"
                name="contrasena"
                type="password"
                class="form-control @error('contrasena') is-invalid @enderror"
            >
            @error('contrasena')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mt-3">
            <label for="contrasena_confirmation">Confirmar Nueva Contraseña</label>
            <input
                id="contrasena_confirmation"
                name="contrasena_confirmation"
                type="password"
                class="form-control"
            >
        </div>

        <button type="submit" class="btn btn-primary mt-4">Actualizar Perfil</button>
    </form>
</div>
@endsection
