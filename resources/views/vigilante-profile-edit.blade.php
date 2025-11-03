@extends('layout-vigilante')

@section('content')
@vite(['resources/css/styles.css']) 
{{-- Asegúrate de que tu archivo 'styles.css' tenga estilos para 'card' y 'form' si no usas Bootstrap --}}

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            {{-- Tarjeta de Diseño Limpio --}}
            <div class="card shadow-lg border-0 rounded-4"> 
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h2 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i> Editar Perfil de Vigilante
                    </h2>
                </div>
                <div class="card-body p-4">

                    {{-- Alerta de Éxito --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>¡Éxito!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Formulario de Actualización --}}
                    <form method="POST" action="{{ route('perfil.update') }}">
                        @csrf
                        @method('PUT')

                        {{-- Campo: Nombre --}}
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre</label>
                            <input
                                id="nombre"
                                name="nombre"
                                type="text"
                                class="form-control form-control-lg @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre', $vigilante->nombre) }}"
                                required
                                placeholder="Ingresa tu nombre completo"
                            >
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campo: Correo Electrónico --}}
                        <div class="mb-3">
                            <label for="correo" class="form-label fw-bold">Correo Electrónico</label>
                            <input
                                id="correo"
                                name="correo"
                                type="email"
                                class="form-control form-control-lg @error('correo') is-invalid @enderror"
                                value="{{ old('correo', $vigilante->correo) }}"
                                required
                                placeholder="ejemplo@correo.com"
                            >
                            @error('correo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4"> {{-- Separador visual para las contraseñas --}}
                        
                        {{-- Bloque de Contraseña --}}
                        <p class="text-muted mb-3">Solo llena los siguientes campos si deseas cambiar tu contraseña.</p>

                        {{-- Campo: Nueva Contraseña --}}
                        <div class="mb-3">
                            <label for="contrasena" class="form-label fw-bold">Nueva Contraseña</label>
                            <input
                                id="contrasena"
                                name="contrasena"
                                type="password"
                                class="form-control @error('contrasena') is-invalid @enderror"
                                placeholder="Mínimo 6 caracteres"
                            >
                            @error('contrasena')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campo: Confirmar Contraseña --}}
                        <div class="mb-4">
                            <label for="contrasena_confirmation" class="form-label fw-bold">Confirmar Nueva Contraseña</label>
                            <input
                                id="contrasena_confirmation"
                                name="contrasena_confirmation"
                                type="password"
                                class="form-control"
                                placeholder="Confirma tu nueva contraseña"
                            >
                        </div>

                        {{-- Botón de Enviar --}}
                        <div class="d-grid gap-2"> {{-- Para hacer el botón de ancho completo --}}
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                <i class="fas fa-save me-2"></i> Actualizar Perfil
                            </button>
                        </div>
                    </form>

                </div>
            </div>
            {{-- Fin de la Tarjeta --}}
        </div>
    </div>
</div>
@endsection