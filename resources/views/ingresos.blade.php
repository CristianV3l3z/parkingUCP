{{-- resources/views/ingresos.blade.php --}}
@extends('layout-vigilante')

@section('content')
  @vite([
    'resources/css/app.css',
    'resources/css/styles.css',
    'resources/js/app.js',
    'resources/js/login.js',
    'resources/js/register.js',
    'resources/js/dashboard_vehiculos.js'
  ])

<!-- ====== Layout: sidebar + contenido (mismo estilo que ingresos) ====== -->
<div class="app-shell" style="min-height:100vh;display:flex;background:var(--bg)">

  @includeWhen(session('vigilante') || auth()->check(), 'sidebar')

  <!-- MAIN CONTENT -->
  <main class="app-main" style="flex:1;">

    <div class="container" style="padding:40px 20px;">
      <h1>Panel de Ingresos - Vigilante</h1>
      <p class="muted">Interfaz para registrar vehículos al ingreso del parqueadero.</p>

      <div style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
        <!-- KPIs -->
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
          <div class="stat" style="flex:0 0 160px;background:var(--card);padding:16px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.06);">
            <div class="stat-label">Total vehículos</div>
            <div class="stat-val" id="totalCount" style="font-size:24px;font-weight:800;">0</div>
          </div>
          <div class="stat" style="flex:0 0 160px;background:var(--card);padding:16px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.06);">
            <div class="stat-label">Carros</div>
            <div class="stat-val" id="countCar" style="font-size:24px;font-weight:800;">0</div>
          </div>
          <div class="stat" style="flex:0 0 160px;background:var(--card);padding:16px;border-radius:12px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.06);">
            <div class="stat-label">Motos</div>
            <div class="stat-val" id="countMoto" style="font-size:24px;font-weight:800;">0</div>
          </div>
        </div>

        <!-- Buscador -->
        <div>
          <input id="searchInput" class="nav-link" placeholder="Buscar por placa..." aria-label="Buscar por placa"
            style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.1);background:transparent;min-width:240px;" />
          <button id="openFormBtn" class="btn btn-primary" style="margin-left:8px;">Agregar vehículo</button>
        </div>
      </div>

      <hr style="margin:28px 0;">

      <!-- LISTA DE VEHÍCULOS EN COLUMNAS -->
      <h3 style="margin-bottom:16px;">Lista de vehículos</h3>
      <div id="vehiclesList" 
          style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
        <!-- tarjetas inyectadas por JS -->
      </div>
    </div>

    <!-- Modal (igual que antes) -->
    <div id="vehicleModal" class="modal" aria-hidden="true" role="dialog">
      <div class="modal-backdrop" id="modalBackdrop"></div>
      <div class="modal-panel" role="document" aria-labelledby="modalTitle">
        <button class="modal-close" id="modalCloseBtn">×</button>
        <h3 id="modalTitle">Agregar vehículo</h3>

        <form id="vehicleForm" autocomplete="off">
          <label for="placa">Placa <small style="color:var(--accent)">*</small></label>
          <input id="placa" name="placa" type="text" placeholder="ABC123" required />

          <label for="tipo_vehiculo">Tipo de vehículo <small style="color:var(--accent)">*</small></label>
          <select id="tipo_vehiculo" name="tipo_vehiculo" required>
            <option value="">-- Seleccione --</option>
            <option value="carro">Carro</option>
            <option value="moto">Moto</option>
          </select>

          <label for="marca">Marca (opcional)</label>
          <input id="marca" name="marca" type="text" placeholder="Toyota" />

          <label for="id_tarifa">Tarifa</label>
          <select id="id_tarifa" name="id_tarifa">
            <option value="">Cargando tarifas...</option>
          </select>
          <div style="margin-top:8px;color:var(--muted);font-size:14px">
            Valor tarifa: <span id="tarifaValue">-</span>
          </div>

          <label for="descripcion">Descripción (opcional)</label>
          <textarea id="descripcion" name="descripcion" placeholder="Notas..."></textarea>

          <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn btn-ghost" id="cancelBtn">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">Agregar vehículo</button>
          </div>
        </form>
      </div>
    </div>

    <div id="toast" class="flash flash-success" 
         style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:120">
         ¡Listo!
    </div>
  </main>
</div>

<!-- ====== Estilos específicos del sidebar (puedes moverlo a style.css) ====== -->
<style>
  /* Sidebar base */
  .app-sidebar {
    width:240px;
    background: linear-gradient(180deg, #fff, #fbf9f8);
    border-right:1px solid rgba(0,0,0,0.03);
    padding:18px;
    box-sizing:border-box;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
  }
  .sidebar-logo{ display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--dark);font-weight:700 }
  .sidebar-logo .logo-icon{ font-size:20px; transform:translateY(-1px) }
  .sidebar-nav ul{ list-style:none;padding:0;margin:18px 0 0 0; display:flex;flex-direction:column; gap:6px }
  .sidebar-nav .nav-item{ display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--dark);text-decoration:none }
  .sidebar-nav .nav-item:hover{ background:rgba(0,0,0,0.02) }
  .sidebar-nav .nav-ico{ width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;background:rgba(0,0,0,0.02);font-size:14px }

  .sidebar-footer{ padding-top:12px;border-top:1px solid rgba(0,0,0,0.03); margin-top:18px }
  .user-block{ display:flex;align-items:center;gap:10px }
  .user-avatar{ width:42px;height:42px;border-radius:10px;background:rgba(0,0,0,0.02);display:flex;align-items:center;justify-content:center;font-weight:800 }
  .user-name{ font-weight:700 }
  .user-role{ font-size:13px;color:var(--muted) }

  /* Mobile: sidebar collapsible */
  @media (max-width: 980px) {
    .app-shell { flex-direction:column; }
    .app-sidebar { width:100%; display:flex;flex-direction:row;align-items:center;gap:8px;padding:10px; }
    .sidebar-nav ul{ flex-direction:row; gap:8px; overflow:auto; margin-left:8px }
    .sidebar-footer{ display:none }
    .app-main { padding-top:12px; }
  }

  /* Ensure vehicles grid still works and is responsive */
  #vehiclesList { margin-top:6px; }
  @media (max-width: 720px) {
    #vehiclesList { grid-template-columns: 1fr !important; }
  }
</style>

<script>
  // expose current user id for form (optional)
  window.__CURRENT_USER_ID = "{{ auth()->id() ?? session('vigilante.id_usuario') ?? '' }}";
</script>

<!-- Reuse your main scripts (app.js + dashboard_vehiculos.js) -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/dashboard_vehiculos.js') }}"></script>

@endsection
