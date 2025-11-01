{{-- resources/views/dashboard.blade.php --}}
@extends('layout')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<!-- NUEVA SECCIÓN: Vehículos (lectura) -->
<div class="container" style="padding:20px 20px 80px 20px;">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;">
    <div>
      <h2 style="margin:0 0 8px 0">Vehículos registrados</h2>
    </div>

    <div style="display:flex;gap:12px;align-items:center;">
      <input id="dashSearch" placeholder="Buscar por placa..." aria-label="Buscar por placa"
             style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.06);background:transparent;min-width:220px;" />
      <button id="refreshBtn" class="btn-ghost" title="Actualizar lista">⟳ Actualizar</button>
    </div>
  </div>

  <!-- KPIs -->
  <div style="display:flex;gap:16px;margin-top:18px;flex-wrap:wrap;">
    <div class="stat" style="flex:0 0 180px;">
      <div class="stat-label">Total vehículos</div>
      <div class="stat-val" id="dashTotal" style="font-size:22px;margin-top:8px;font-weight:800">0</div>
    </div>
    <div class="stat" style="flex:0 0 180px;">
      <div class="stat-label">Carros</div>
      <div class="stat-val" id="dashCar" style="font-size:22px;margin-top:8px;font-weight:800">0</div>
    </div>
    <div class="stat" style="flex:0 0 180px;">
      <div class="stat-label">Motos</div>
      <div class="stat-val" id="dashMoto" style="font-size:22px;margin-top:8px;font-weight:800">0</div>
    </div>
  </div>

  <!-- Lista (solo lectura) -->
  <div style="margin-top:22px;">
    <h3 style="margin-bottom:12px">Lista de vehículos</h3>

    <div id="dashVehicles" style="display:flex;flex-direction:column;gap:12px;">
      <div id="dashPlaceholder" style="padding:18px;background:var(--card);border-radius:10px;color:var(--muted)">
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

<!-- Modal: Detalles del tiquete (reutilizable) -->
<div id="ticketModal" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:120;">
  <div style="position:absolute; inset:0; background:rgba(0,0,0,0.45)"></div>

  <div style="position:relative; width:720px; max-width:95%; background:var(--card); border-radius:12px; padding:18px; box-shadow:0 30px 80px rgba(0,0,0,0.35); z-index:121;">
    <button id="ticketModalClose" style="position:absolute; right:12px; top:8px; border:none; background:transparent; font-size:22px; cursor:pointer">✕</button>

    <h3 id="ticketModalTitle" style="margin:0 0 8px">Detalle del tiquete</h3>
    <div id="ticketModalBody" style="max-height:60vh; overflow:auto; padding-top:8px;">
      <!-- Aquí inyectamos contenido con JS -->
      <div style="padding:12px;color:var(--muted)">Cargando...</div>
    </div>

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
      <button id="ticketModalCloseBtn" class="btn-ghost">Cerrar</button>
      <!-- Botón de acción futuro (checkout) — actualmente no hace nada -->
      <button id="ticketModalAction" class="btn-primary" style="display:none" data-tiquete-id="">Iniciar pago</button>
    </div>
  </div>
</div>

<!-- Modal personalizado: Mi Pasarela -->
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
