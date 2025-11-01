{{-- resources/views/datos.blade.php --}}
@extends('layout-vigilante')

@section('content')
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">

<div class="app-shell" style="min-height:100vh;display:flex;background:var(--bg)">
  @if(session('vigilante') || (auth()->check() && (auth()->user()->is_vigilante ?? false)))
    @include('sidebar')
  @endif

  <main class="app-main" style="flex:1;">
    <div class="container" style="padding:36px 20px;">
      <h1>Datos / Estadísticas</h1>
      <p class="muted">Panel de estadísticas (solo para vigilantes).</p>

      <!-- KPIs -->
      <div style="display:flex;gap:18px;align-items:center;margin-top:12px;flex-wrap:wrap">
        <div class="stat" style="flex:0 0 180px">
          <div class="stat-label">Total vehículos</div>
          <div class="stat-val" id="kpi_total" style="font-size:22px;font-weight:800">0</div>
        </div>
        <div class="stat" style="flex:0 0 180px">
          <div class="stat-label">Carros</div>
          <div class="stat-val" id="kpi_carros" style="font-size:22px;font-weight:800">0</div>
        </div>
        <div class="stat" style="flex:0 0 180px">
          <div class="stat-label">Motos</div>
          <div class="stat-val" id="kpi_motos" style="font-size:22px;font-weight:800">0</div>
        </div>
        <div class="stat" style="flex:0 0 220px">
          <div class="stat-label">Total adeudo</div>
          <div class="stat-val" id="kpi_adeudo" style="font-size:20px;font-weight:800">$0.00</div>
        </div>

        <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
          <label for="rangeSelect" class="muted">Rango:</label>
          <select id="rangeSelect" style="padding:8px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06)">
            <option value="7">Últimos 7 días</option>
            <option value="15">Últimos 15 días</option>
            <option value="30">Últimos 30 días</option>
            <option value="month">Mes actual</option>
            <option value="lastmonth">Mes anterior</option>
            <option value="today">Hoy</option>
            <option value="yesterday">Ayer</option>
          </select>
        </div>
      </div>

      <hr style="margin:18px 0;">

      <h3 style="margin-bottom:8px">Ingresos por día</h3>
      <div id="chartWrapper" style="background:var(--card);padding:12px;border-radius:12px;min-height:120px">
        <div id="chart" style="display:flex;align-items:flex-end;gap:6px;height:120px"></div>
        <div id="chartLabels" style="display:flex;gap:6px;margin-top:8px"></div>
      </div>

      <hr style="margin:18px 0;">

      <!-- ADEUDO POR TIPO -->
      <h3 style="margin-bottom:8px">Adeudo por tipo de vehículo</h3>

      <div style="display:flex;gap:16px;margin-top:12px;">
        <div class="stat" style="flex:0 0 260px;">
          <div class="stat-label">Adeudo Carros</div>
          <div class="stat-val" id="adeudoCarros" style="font-size:20px;font-weight:800">$0.00</div>
        </div>
        <div class="stat" style="flex:0 0 260px;">
          <div class="stat-label">Adeudo Motos</div>
          <div class="stat-val" id="adeudoMotos" style="font-size:20px;font-weight:800">$0.00</div>
        </div>
      </div>

      <hr style="margin:18px 0;">

      <h3 style="margin-bottom:8px">Historial de vehículos (solo lectura)</h3>
      <div style="background:var(--card);padding:12px;border-radius:12px;">
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="text-align:left;color:var(--muted)">
              <th style="padding:8px">ID Tiquete</th>
              <th>ID Vehículo</th>
              <th>Placa</th>
              <th>Tipo</th>
              <th>Fecha ingreso</th>
              <th>Vigilante</th>
              <th>Fecha salida</th>
              <th>Tarifa (desc - valor)</th>
              <th>Adeudo</th>
            </tr>
          </thead>

          <!-- body donde JS inyecta filas -->
          <tbody id="historyTbody"></tbody>

          <!-- footer con totales (JS lo rellenará) -->
          <tfoot id="historyTfoot">
            <!-- inicialmente vacío; JS agregará la fila de totales -->
          </tfoot>
        </table>
      </div>

    </div>
  </main>
</div>

<script>
  window.__CURRENT_USER_ID = "{{ auth()->id() ?? session('vigilante.id_usuario') ?? '' }}";
</script>

<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/datos.js') }}"></script>
@endsection
