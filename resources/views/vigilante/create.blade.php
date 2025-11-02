@extends('layout') 

@section('title', 'Crear Vigilante Temporal')

@section('content')

<div style="max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
    <h2>Crear Nuevo Vigilante (Temporal)</h2>
    <p style="color: red;">¡ADVERTENCIA! Elimina esta ruta y vista después de crear los usuarios necesarios.</p>

    @if ($errors->any())
        <div style="color: white; background-color: #f44336; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div style="color: white; background-color: #4CAF50; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('vigilante.store.web') }}">
        @csrf

        <div style="margin-bottom: 15px;">
            <label for="nombre">Nombre</label>
            <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" required 
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="correo">Correo Electrónico</label>
            <input id="correo" type="email" name="correo" value="{{ old('correo') }}" required 
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="contrasena">Contraseña</label>
            <input id="contrasena" type="password" name="contrasena" required minlength="6"
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label for="contrasena_confirmation">Confirmar Contraseña</label>
            <input id="contrasena_confirmation" type="password" name="contrasena_confirmation" required minlength="6"
                   style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" 
                style="background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
            Crear Vigilante
        </button>
    </form>
</div>

@endsection