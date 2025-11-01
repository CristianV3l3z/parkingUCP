{{-- resources/views/tiquetes.blade.php --}}
@extends('layout-vigilante')

@section('content')
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">

<!-- ====== Layout: sidebar + contenido (mismo estilo que ingresos) ====== -->
<div class="app-shell" style="min-height:100vh;display:flex;background:var(--bg)">

    {{-- Mostrar el sidebar SOLO si hay sesión de vigilante o el usuario autenticado es vigilante.
      IMPORTANTE: aquí usamos el partial real que tienes en resources/views/partials/sidebar.blade.php --}}
  @if(session('vigilante') || (auth()->check() && (auth()->user()->is_vigilante ?? false)))
    @include('sidebar')
  @endif

    <!-- MAIN CONTENT (estilo igual a ingresos) -->
  <main class="app-main" style="flex:1;">

<div class="container" style="padding:36px 20px;">
  <h1>Panel de Tiquetes</h1>
  <p class="muted">Listado de tiquetes generados por los vigilantes.</p>

  <div style="margin-top:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <!-- KPIs -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;">
      <div class="stat" style="flex:0 0 180px;background:var(--card);padding:14px;border-radius:12px;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,0.04);">
        <div class="stat-label">Total tiquetes</div>
        <div class="stat-val" id="totalTickets" style="font-size:22px;font-weight:800">0</div>
      </div>
      <div class="stat" style="flex:0 0 180px;background:var(--card);padding:14px;border-radius:12px;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,0.04);">
        <div class="stat-label">Tiquetes activos</div>
        <div class="stat-val" id="activeTickets" style="font-size:22px;font-weight:800">0</div>
      </div>
      <div class="stat" style="flex:0 0 180px;background:var(--card);padding:14px;border-radius:12px;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,0.04);">
        <div class="stat-label">Total adeudo</div>
        <div class="stat-val" id="totalDebt" style="font-size:18px;font-weight:800">$0.00</div>
      </div>
    </div>

    <!-- Buscador -->
    <div style="display:flex;gap:12px;align-items:center;">
      <input id="searchTickets" placeholder="Buscar por placa o código..." style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.06);min-width:260px" />
    </div>
  </div>

  <hr style="margin:20px 0 18px 0;">

  <h3 style="margin-bottom:12px">Lista de tiquetes</h3>
  <div id="ticketsList" style="display:grid; grid-template-columns: repeat(auto-fill,minmax(480px,1fr)); gap:18px;">
  </div>
</div>
  </main>
</div>

<script>
  // pasar current user id (opcional)
  window.__CURRENT_USER_ID = "{{ auth()->id() ?? session('vigilante.id_usuario') ?? '' }}";
</script>

<!-- scripts: app.js + tu tiquetes.js (asegúrate de tener public/JS/tiquetes.js con la lógica que te entregué) -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('JS/tiquetes.js') }}"></script>

@endsection
