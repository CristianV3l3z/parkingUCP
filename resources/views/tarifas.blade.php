{{-- resources/views/tarifas.blade.php --}}
@extends('layout-vigilante')

@section('content')
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">

<div class="app-shell" style="min-height:100vh;display:flex;background:var(--bg)">
  {{-- Mostrar sidebar si vigilante o auth vigilante --}}
  @if(session('vigilante') || (auth()->check() && (auth()->user()->is_vigilante ?? false)))
    @include('sidebar')
  @endif

  <main class="app-main" style="flex:1;">
    <div class="container" style="padding:36px 20px;">
      <h1>Gestión de Tarifas</h1>
      <p class="muted">Crear, editar y activar/desactivar tarifas (solo carro / moto).</p>

      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:18px;">
        <div style="display:flex;gap:12px;align-items:center;">
          <button id="btnNew" class="btn btn-primary">Nueva tarifa</button>
        </div>

        <div>
          <input id="searchTarifa" placeholder="Buscar por tipo o descripción..." style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.06);min-width:260px" />
        </div>
      </div>

      <hr style="margin:18px 0;">

      <h3 style="margin-bottom:12px">Lista de tarifas</h3>
      <div id="tarifasList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:14px"></div>
    </div>
  </main>
</div>

{{-- Modal simple para crear/editar --}}
<div id="modalTarifa" class="modal" aria-hidden="true" role="dialog" style="display:none">
  <div class="modal-backdrop" id="modalBackdrop"></div>
  <div class="modal-panel" role="document" aria-labelledby="modalTitle" style="max-width:520px">
    <button class="modal-close" id="modalCloseBtn">×</button>
    <h3 id="modalTitle">Nueva tarifa</h3>

    <form id="formTarifa" autocomplete="off">
      <label for="tipo_vehiculo">Tipo de vehículo</label>
      <select id="tipo_vehiculo" name="tipo_vehiculo" required>
        <option value="">--Seleccione--</option>
        <option value="carro">Carro</option>
        <option value="moto">Moto</option>
      </select>

      <label for="valor">Valor (número)</label>
      <input id="valor" name="valor" type="number" step="0.01" min="0" required />

      <label for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" placeholder="Descripción (opcional)"></textarea>

      <label for="activo_check" style="display:flex;align-items:center;gap:8px;margin-top:8px">
        <input type="checkbox" id="activo_check" name="activo" /> Activo
      </label>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="btn btn-ghost" id="cancelTarifa">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="submitTarifa">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div id="toast" class="flash flash-success" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:120"></div>

<script>
  window.__CSRF = "{{ csrf_token() }}";
</script>

<!-- Incluye el script (ver más abajo) -->
@vite('resources/js/tarifas.js')

@endsection
