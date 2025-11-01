{{-- resources/views/partials/sidebar.blade.php --}}
<aside class="app-sidebar" aria-label="Sidebar">
  <div class="sidebar-inner">

    <nav class="sidebar-nav" aria-label="Main navigation">
      <ul>
        <li><a href="{{ route('home') }}" class="nav-item"><span class="nav-ico">🏠</span><span class="nav-label">Home</span></a></li>
        <li><a href="{{ route('ingresos') }}" class="nav-item"><span class="nav-ico">🚗</span><span class="nav-label">Ingresos</span></a></li>
        <li><a href="{{ route('datos') }}" class="nav-item"><span class="nav-ico">📊</span><span class="nav-label">Datos</span></a></li>
        <li><a href="{{ route('tiquetes') }}" class="nav-item"><span class="nav-ico">🎟️</span><span class="nav-label">Tiquetes</span></a></li>
        <li><a href="{{ route('tarifas') }}" class="nav-item"><span class="nav-ico">📦</span><span class="nav-label">Tarifas</span></a></li>
        {{-- <li><a href="#" class="nav-item"><span class="nav-ico">👥</span><span class="nav-label">Usuarios</span></a></li> --}}
      </ul>
    </nav>

    <div class="sidebar-footer">
      @if(session('vigilante'))
        <div class="user-block">
          <div class="user-avatar">{{ strtoupper(substr(session('vigilante.nombre') ?? '', 0, 2)) }}</div>
          <div class="user-info">
            <div class="user-name">{{ session('vigilante.nombre') }}<tar/div>
            <div class="user-role muted">Vigilante</div>
          </div>
        </div>

        <form action="{{ route('vigilante.logout') }}" method="POST" style="margin-top:12px;">
          @csrf
          <button type="submit" class="btn btn-ghost" style="width:100%;">Cerrar sesión</button>
        </form>

      @else
        <div class="user-block">
          <div class="user-avatar">{{ strtoupper(substr(auth()->user()->nombre ?? 'US', 0, 2)) }}</div>
          <div class="user-info">
            <div class="user-name">{{ auth()->user() ? (auth()->user()->nombre ?? auth()->user()->email) : 'Usuario' }}</div>
            <div class="user-role muted">Vigilante</div>
          </div>
        </div>

        @if(auth()->check())
          <form action="{{ route('logout') }}" method="POST" style="margin-top:12px;">
            @csrf
            <button type="submit" class="btn btn-ghost" style="width:100%;">Cerrar sesión</button>
          </form>
        @endif

      @endif
    </div>
  </div>
</aside>
