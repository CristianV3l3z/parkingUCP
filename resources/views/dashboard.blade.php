{{-- resources/views/dashboard.blade.php --}}
@extends('layout')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">



<!-- Small responsive overrides specifically for this view -->
<style>
  :root{
    --muted: #6f6f6f;
    --card: #fff;
  }

  /* Container */
  .dash-container {
    padding: 18px;
    box-sizing: border-box;
    max-width: 1100px;
    margin: 0 auto;
  }

  /* Header row (mobile-first) */
  .dash-header {
    display:flex;
    flex-direction:column;
    gap:12px;
    align-items:stretch;
  }
  .dash-header .title-wrap { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
  .dash-title { margin:0; font-size:18px; font-weight:800; }

  /* Actions: search + refresh */
  .dash-actions { display:flex; gap:10px; align-items:center; width:100%; }
  #dashSearch {
    flex:1;
    min-width:0;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid rgba(0,0,0,0.06);
    background:transparent;
    font-size:15px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.02);
  }
  #refreshBtn { padding:10px 12px; border-radius:10px; }

  /* KPIs: scroll horizontal on mobile */
  .kpis {
    display:flex;
    gap:12px;
    margin-top:10px;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    padding-bottom:6px;
  }
  .kpis .stat {
    min-width:140px;
    flex:0 0 auto;
    background:var(--card);
    border-radius:12px;
    padding:12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
  }
  .kpis .stat-label { color:var(--muted); font-size:13px }
  .kpis .stat-val { font-weight:800; font-size:20px; margin-top:6px }

  /* Vehicles list */
  .vehicles-wrap { margin-top:18px; }
  #dashVehicles { display:flex; flex-direction:column; gap:12px; }

  /* Vehicle card (mobile-first column) */
  .vehicle-card {
    background:var(--card);
    border-radius:12px;
    padding:12px;
    box-shadow:0 10px 30px rgba(0,0,0,0.04);
    border:1px solid rgba(0,0,0,0.03);
    display:flex;
    flex-direction:column;
    gap:10px;
  }
  .vehicle-top { display:flex; gap:12px; align-items:center; }
  .vehicle-avatar { width:56px;height:56px;border-radius:10px;background:rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;flex-shrink:0; }
  .vehicle-meta .plate { font-weight:900; font-size:16px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden }
  .vehicle-meta .model { color:var(--muted); font-size:13px }

  .vehicle-bottom { display:flex; gap:10px; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; }
  .vehicle-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .vehicle-actions .btn-ghost, .vehicle-actions .btn-primary, .vehicle-actions .btn-sm { min-width:96px; flex-shrink:0; }

  /* Ensure buttons wrap to full width on small screens */
  /* Ajustes móviles: botones en columna y descripción con más espacio */
@media (max-width:420px){
  /* Asegurarnos que la parte inferior se apila verticalmente */
  .vehicle-bottom {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  /* Forzamos que el contenedor de acciones ocupe todo el ancho */
  .vehicle-actions {
    width: 100%;
    display: flex !important;
    flex-direction: column !important;
    gap: 10px;
    align-items: stretch;
  }

  /* Botones a 100% ancho, sin min-width para que se ajusten */
  .vehicle-actions .btn-ghost,
  .vehicle-actions .btn-primary,
  .vehicle-actions .btn-sm {
    width: 100%;
    min-width: 0;
    padding: 12px 14px;
    border-radius: 12px;
    box-sizing: border-box;
    font-weight: 700;
  }

  /* Más espacio para la descripción/meta y mejor legibilidad */
  .vehicle-meta {
    margin-bottom: 6px;
    font-size: 15px;
    line-height: 1.45;
  }

  /* Aumentar separación entre avatar/título y la descripción en móvil */
  .vehicle-top { gap: 14px; align-items: flex-start; }

  /* Si tenías texto al final (ej. precio o fecha), lo ponemos alineado a la izquierda */
  .vehicle-bottom .text-muted,
  .vehicle-bottom .small {
    text-align: left;
  }
}


  /* Modals responsive: use almost-fullscreen on small screens */
  #profileModal > div,
  #ticketModal > div,
  #miPasarelaModal > div {
    width: 96% !important;
    max-width: 520px;
  }
  #miPasarelaModal .modal-sheet, #ticketModal .modal-sheet { max-height:86vh; overflow:auto; }

  /* Reserve bottom padding when mobile action bar exists (if you use it) */
  .has-mobile-action-bar .dash-container { padding-bottom:86px; }

  /* Small desktop adjustments */
  @media (min-width:900px){
    .dash-header { flex-direction:row; align-items:center; justify-content:space-between; }
    .dash-actions { width:auto; }
    .kpis { overflow:visible; }
    /* vehicle card becomes row on desktop */
    .vehicle-card { flex-direction:row; align-items:center; justify-content:space-between; gap:12px; }
    .vehicle-top { flex:1; }
    .vehicle-bottom { width:auto; gap:12px; justify-content:flex-end; }
  }
</style>

<div class="dash-container">
  <div class="dash-header">
    <div class="title-wrap">
      <div>
        <h2 class="dash-title">Vehículos registrados</h2>
      </div>

      <div class="dash-actions" aria-label="Acciones de búsqueda">
        <input id="dashSearch" placeholder="Buscar por placa..." aria-label="Buscar por placa" />
        <button id="refreshBtn" class="btn-ghost" title="Actualizar lista">⟳ Actualizar</button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="kpis" role="list" aria-label="Indicadores">
      <div class="stat" role="listitem">
        <div class="stat-label">Total vehículos</div>
        <div class="stat-val" id="dashTotal" aria-live="polite">0</div>
      </div>
      <div class="stat" role="listitem">
        <div class="stat-label">Carros</div>
        <div class="stat-val" id="dashCar" aria-live="polite">0</div>
      </div>
      <div class="stat" role="listitem">
        <div class="stat-label">Motos</div>
        <div class="stat-val" id="dashMoto" aria-live="polite">0</div>
      </div>
    </div>
  </div>

  <!-- Lista (solo lectura) -->
  <div class="vehicles-wrap">
    <h3 style="margin-bottom:12px">Lista de vehículos</h3>

    <div id="dashVehicles">
      <div id="dashPlaceholder" class="placeholder" style="padding:18px;background:var(--card);border-radius:10px;color:var(--muted)">
        Cargando vehículos...
      </div>
    </div>
  </div>
</div>

<!-- Modal Edicion Perfil (front-end) -->
<div id="profileModal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:50">
  <div style="width:480px;background:var(--card);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <h3>Editar perfil</h3>
    <form id="profileForm">
      <label style="font-weight:700">Nombre</label>
      <input name="nombre" id="profileNombre" style="width:100%;padding:8px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
      <label style="font-weight:700;margin-top:8px;display:block">Correo</label>
      <input name="correo" id="profileCorreo" style="width:100%;padding:8px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
        <button type="button" id="profileCancel" class="btn-ghost">Cancelar</button>
        <button type="submit" class="btn-action">Guardar (simulado)</button>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:8px">*Este formulario es de sólo interfaz; conecta con tu API para persistir cambios.</div>
    </form>
  </div>
</div>

<!-- Ticket Modal -->
<div id="ticketModal" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:120;">
  <div style="position:absolute; inset:0; background:rgba(0,0,0,0.45)"></div>

  <div style="position:relative; width:720px; max-width:95%; background:var(--card); border-radius:12px; padding:18px; box-shadow:0 30px 80px rgba(0,0,0,0.35); z-index:121;">
    <button id="ticketModalClose" style="position:absolute; right:12px; top:8px; border:none; background:transparent; font-size:22px; cursor:pointer">✕</button>

    <h3 id="ticketModalTitle" style="margin:0 0 8px">Detalle del tiquete</h3>
    <div id="ticketModalBody" style="max-height:60vh; overflow:auto; padding-top:8px;">
      <div style="padding:12px;color:var(--muted)">Cargando...</div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
      <button id="ticketModalCloseBtn" class="btn-ghost">Cerrar</button>
      <button id="ticketModalAction" class="btn-primary" style="display:none" data-tiquete-id="">Iniciar pago</button>
    </div>
  </div>
</div>

<!-- Mi Pasarela -->
<div id="miPasarelaModal" style="display:none;position:fixed;inset:0;z-index:140;align-items:center;justify-content:center;">
  <div style="position:absolute;inset:0;background:rgba(0,0,0,0.45)"></div>

  <div style="position:relative;width:520px;max-width:95%;background:var(--card);border-radius:12px;padding:18px;box-shadow:0 30px 80px rgba(0,0,0,0.35);z-index:141;">
    <button id="miPasarelaClose" style="position:absolute;right:12px;top:8px;border:none;background:transparent;font-size:20px;cursor:pointer">✕</button>
    <h3 style="margin:0 0 6px">Pagar tiquete</h3>
    <p class="muted" style="margin:0 0 12px">Revisa el monto y finaliza el pago</p>

    <div id="miPasarelaBody">
      <div style="display:flex;gap:14px;align-items:center;">
        <div style="width:88px;height:88px;border-radius:12px;background:rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:24px" id="miPasarelaPlaca">--</div>
        <div style="flex:1">
          <div style="font-weight:800" id="miPasarelaTitulo">Tiquete #—</div>
          <div class="muted small" id="miPasarelaVehInfo">—</div>
        </div>
        <div style="text-align:right">
          <div class="muted small">Total</div>
          <div style="font-weight:900;font-size:20px" id="miPasarelaMonto">$0.00</div>
        </div>
      </div>

      <hr style="margin:12px 0;border:none;border-top:1px solid rgba(0,0,0,0.06)">

      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <button id="miPasarelaPagar" class="btn-primary btn-sm">Pagar con Mercado Pago</button>
        <button id="miPasarelaAbrirMP" class="btn-ghost btn-sm">Abrir MP en nueva pestaña</button>
        <div style="margin-left:auto" class="text-muted small">Sandbox: usa tarjetas de prueba</div>
      </div>
    </div>
  </div>
</div>



<script src="{{ asset('js/dashboard_readonly.js') }}"></script>
@endsection
