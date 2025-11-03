<header class="site-header">
    <div class="container header-inner">
        <div class="logo">
            <a href="{{ url('/') }}">
                <span class="logo-icon">🅿️</span>
                <span class="logo-text">Parking</span>
            </a>
        </div>

        {{-- PERFIL Y NOTIFICACIONES: Uso de flex para centrar y alinear --}}
        <div class="user-area d-flex align-items-center"> 
            <div class="user-menu">
                @if(session('vigilante'))
                    
                    {{-- Usamos un div para el contenido del usuario logueado --}}
                    <div class="d-flex align-items-center gap-3"> 
                        {{-- 1. Nombre de Usuario --}}
                        <span class="user-greeting text-primary fw-bold">
                            <i class="fas fa-user-circle me-1"></i> Hola, {{ session('vigilante.nombre') }}
                        </span>

                        {{-- 2. Navegación/Acciones --}}
                        <nav class="user-nav d-flex align-items-center gap-2">
                            <a href="{{ route('perfil.edit') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Editar Perfil
                            </a> 
                            
                            {{-- 3. Formulario de Logout (Mejor Estilizado) --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger logout-button">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                                </button>
                            </form>
                        </nav>
                    </div>

                @else
                    {{-- Usuario no logueado --}}
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-1"></i> Iniciar sesión
                    </a>
                @endif
            </div>
        </div>
        {{-- Fin de Perfil y Notificaciones --}}

    </div>
</header>