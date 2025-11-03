@extends('layout-vigilante')

@section('content')
    @vite(['resources/css/styles.css'])

    <div class="container" style="max-width: 600px; margin-top: 3rem;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: clamp(28px, 5vw, 56px); margin-bottom: 1.5rem; color: var(--dark); text-align:center;">
            Editar Perfil
        </h1>

        @if(session('success'))
            <div class="flash flash-success" role="alert" style="margin-bottom:1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('perfil.update') }}" novalidate>
            @csrf
            @method('PUT')

            <label for="nombre" style="display:block; font-size:14px; font-weight:600; margin-bottom:6px; color: var(--dark);">Nombre</label>
            <input
                id="nombre"
                name="nombre"
                type="text"
                value="{{ old('nombre', $vigilante->nombre) }}"
                required
                style="width:100%; padding:12px 14px; border-radius: var(--radius); border:1px solid rgba(0,0,0,0.07); margin-bottom:10px; font-size:16px;"
                class="@error('nombre') is-invalid @enderror"
            >
            @error('nombre')
                <div class="invalid-feedback" style="color: var(--danger); margin-bottom:12px;">{{ $message }}</div>
            @enderror

            <label for="correo" style="display:block; font-size:14px; font-weight:600; margin-bottom:6px; color: var(--dark);">Correo Electrónico</label>
            <input
                id="correo"
                name="correo"
                type="email"
                value="{{ old('correo', $vigilante->correo) }}"
                required
                style="width:100%; padding:12px 14px; border-radius: var(--radius); border:1px solid rgba(0,0,0,0.07); margin-bottom:10px; font-size:16px;"
                class="@error('correo') is-invalid @enderror"
            >
            @error('correo')
                <div class="invalid-feedback" style="color: var(--danger); margin-bottom:12px;">{{ $message }}</div>
            @enderror

            <label for="contrasena" style="display:block; font-size:14px; font-weight:600; margin-bottom:6px; color: var(--dark);">
                Nueva Contraseña <span style="font-weight:400; font-size:13px; color: var(--muted);">(dejar vacío para no cambiar)</span>
            </label>
            <input
                id="contrasena"
                name="contrasena"
                type="password"
                style="width:100%; padding:12px 14px; border-radius: var(--radius); border:1px solid rgba(0,0,0,0.07); margin-bottom:10px; font-size:16px;"
                class="@error('contrasena') is-invalid @enderror"
            >
            @error('contrasena')
                <div class="invalid-feedback" style="color: var(--danger); margin-bottom:12px;">{{ $message }}</div>
            @enderror

            <label for="contrasena_confirmation" style="display:block; font-size:14px; font-weight:600; margin-bottom:6px; color: var(--dark);">
                Confirmar Nueva Contraseña
            </label>
            <input
                id="contrasena_confirmation"
                name="contrasena_confirmation"
                type="password"
                style="width:100%; padding:12px 14px; border-radius: var(--radius); border:1px solid rgba(0,0,0,0.07); margin-bottom:20px; font-size:16px;"
            >

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px 0; font-weight: 700; font-size: 18px; border-radius: var(--radius);">
                Actualizar Perfil
            </button>
        </form>
    </div>
@endsection
