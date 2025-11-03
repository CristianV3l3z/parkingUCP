// public/js/datos.js
document.addEventListener('DOMContentLoaded', function () {
  // elementos KPI
  const kpi_total = document.getElementById('kpi_total');
  const kpi_carros = document.getElementById('kpi_carros');
  const kpi_motos = document.getElementById('kpi_motos');
  const kpi_adeudo = document.getElementById('kpi_adeudo');

  const rangeSelect = document.getElementById('rangeSelect');
  const chartEl = document.getElementById('chart');
  const chartLabels = document.getElementById('chartLabels');
  const historyTbody = document.getElementById('historyTbody');

  // selector extra para adeudo por tipo si lo tienes; si no, se reutiliza rangeSelect
  const adeudoRange = document.getElementById('adeudoRange');

  // Util: formatea una Date local a YYYY-MM-DD (sin pasar por UTC)
  function formatLocalDate(d) {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }

  // helper: extrae "YYYY-MM-DD HH:MM" de varias formas de timestamp que venga el backend
  function extractServerDateTime(str) {
    if (!str || typeof str !== 'string') return null;
    // Busca "YYYY-MM-DD HH:MM" o "YYYY-MM-DDTHH:MM"
    let m = str.match(/(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
    if (m) return m[1] + ' ' + m[2];
    // fallback: extraer fecha y hora por separado
    m = str.match(/(\d{4}-\d{2}-\d{2})/);
    const date = m ? m[1] : null;
    m = str.match(/(\d{2}:\d{2})/);
    const time = m ? m[1] : null;
    if (date && time) return date + ' ' + time;
    // si no encontramos nada devolvemos la cadena original truncada a 16 chars (graceful)
    return (typeof str === 'string' && str.length > 16) ? str.substring(0, 16) : str;
  }

  // Fetch helper con credentials y headers JSON
  async function fetchJson(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return await r.json();
  }

  /**
   * computeRange:
   * Recibe el value del select y devuelve { from: 'YYYY-MM-DD', to: 'YYYY-MM-DD' }
   * Todo en FECHA LOCAL para evitar desfases por timezone.
   */
  function computeRange(value) {
    const now = new Date();
    let from = new Date(now);
    let to = new Date(now);

    if (value === 'month') {
      from = new Date(now.getFullYear(), now.getMonth(), 1); // inicio mes local
      to = new Date(now.getFullYear(), now.getMonth() + 1, 0); // ultimo dia del mes local
    } else if (value === 'lastmonth') {
      const d = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      from = new Date(d.getFullYear(), d.getMonth(), 1);
      to = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    } else if (value === 'today') {
      from = new Date(now.getFullYear(), now.getMonth(), now.getDate()); // 00:00 local
      to = new Date(now.getFullYear(), now.getMonth(), now.getDate());   // 23:59 local (we return date only)
    } else if (value === 'yesterday') {
      const y = new Date(now);
      y.setDate(y.getDate() - 1);
      from = new Date(y.getFullYear(), y.getMonth(), y.getDate());
      to = new Date(y.getFullYear(), y.getMonth(), y.getDate());
    } else {
      // valor numérico -> últimos N días (por ejemplo "7" => últimos 7 días incluyendo hoy)
      const days = parseInt(value, 10) || 7;
      to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      from = new Date(to);
      from.setDate(to.getDate() - (days - 1));
    }

    // devolvemos solo YYYY-MM-DD (el backend convierte a startOfDay/endOfDay con Carbon)
    return { from: formatLocalDate(from), to: formatLocalDate(to) };
  }

  // Cargar KPIs (summary)
  async function loadSummary(from, to) {
    try {
      const url = `/api/datos/summary?from=${from}&to=${to}`;
      const s = await fetchJson(url);
      kpi_total.textContent = s.total ?? 0;
      kpi_carros.textContent = s.carros ?? 0;
      kpi_motos.textContent = s.motos ?? 0;
      kpi_adeudo.textContent = '$' + (s.total_adeudo ?? '0.00');
    } catch (e) {
      console.error('summary error', e);
    }
  }

  // Cargar ingresos por día (ingresosByDay)
  async function loadIngresos(from, to) {
    try {
      const obj = await fetchJson(`/api/datos/ingresos?from=${from}&to=${to}`);
      renderChart(obj);
    } catch (e) {
      console.error('ingresos error', e);
    }
  }

  // Render simple de barras (estilizado inline)
  function renderChart(obj) {
    chartEl.innerHTML = '';
    chartLabels.innerHTML = '';
    const keys = Object.keys(obj || {});
    if (keys.length === 0) return;
    const vals = keys.map(k => obj[k]);
    const max = Math.max(...vals, 1);
    keys.forEach(k => {
      const v = obj[k];
      const bar = document.createElement('div');
      const h = Math.round((v / max) * 100);
      bar.style.width = '100%';
      bar.style.height = (h > 6 ? h : 6) + '%';
      bar.style.background = 'linear-gradient(180deg, rgba(0,0,0,0.08), rgba(0,0,0,0.02))';
      bar.style.borderRadius = '6px';
      bar.style.flex = '1';
      bar.style.display = 'flex';
      bar.style.alignItems = 'flex-end';
      bar.style.justifyContent = 'center';
      bar.style.padding = '6px 2px';
      bar.title = `${k}: ${v}`;
      const label = document.createElement('div');
      label.style.fontSize = '12px';
      label.style.color = 'var(--muted)';
      label.textContent = v;
      bar.appendChild(label);
      chartEl.appendChild(bar);

      const lbl = document.createElement('div');
      lbl.style.flex = '1';
      lbl.style.textAlign = 'center';
      lbl.style.fontSize = '12px';
      lbl.style.color = 'var(--muted)';
      // Mostrar MM-DD para ahorrar espacio
      lbl.textContent = k.slice(5);
      chartLabels.appendChild(lbl);
    });
  }

  // Cargar historial de vehículos filtrado por from/to (ahora acepta parámetros)
  // Además: calcula totales (global, por carro y por moto), actualiza KPIs y añade footer en la tabla.
  async function loadHistory(from, to) {
    try {
      // Llamada al endpoint con el rango — el backend filtra vehiculos por v.created_at entre from/to
      const rows = await fetchJson(`/api/datos/history?from=${from}&to=${to}`);
      const tbody = historyTbody;
      const tfoot = document.getElementById('historyTfoot');

      // Limpiar cuerpo y footer
      tbody.innerHTML = '';
      if (tfoot) tfoot.innerHTML = '';

      // Si no hay filas, mostrar mensaje y poner ceros en totales
      if (!rows || rows.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td colspan="9" style="padding:12px;color:var(--muted)">No hay vehículos en este rango.</td>`;
        tbody.appendChild(tr);

        // Actualizar KPIs y footer a 0
        updateAdeudoKPIsAndFooter(0, 0, 0);
        return;
      }

      // Inicializar acumuladores
      let totalAdeudo = 0;
      let totalCarro = 0;
      let totalMoto = 0;
      let filas = 0;

      // Rellenar filas
      rows.forEach(r => {
        filas++;
        const tr = document.createElement('tr');

        // Formateo local de fechas si existen
        // Usamos extractServerDateTime para tomar la representación textual enviada por el backend
        const fechaIngreso = r.fecha_ingreso ? extractServerDateTime(r.fecha_ingreso) : '—';
        const horaSalida = r.hora_salida ? extractServerDateTime(r.hora_salida) : '—';

        const tarifaDesc = r.tarifa && r.tarifa.descripcion ? r.tarifa.descripcion : '';
        const tarifaVal  = r.tarifa && r.tarifa.valor ? r.tarifa.valor : '0.00';

        // Adeudo viene como string "1234.56" desde backend -> parseFloat para sumar
        const adeudoNum = parseFloat((r.adeudo ?? '0').toString().replace(',', '')) || 0.0;

        // Acumular totales por tipo
        const tipo = (r.tipo_vehiculo || '').toString().trim().toLowerCase();
        if (tipo === 'carro') totalCarro += adeudoNum;
        else if (tipo === 'moto') totalMoto += adeudoNum;
        // si existen otros tipos, no los añadimos aquí pero sí sumamos al total general:
        totalAdeudo += adeudoNum;

        tr.innerHTML = `
          <td style="padding:8px;border-top:1px solid rgba(0,0,0,0.04)">${r.id_tiquete ?? '—'}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${r.id_vehiculo ?? '—'}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${r.placa ?? '—'}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${r.tipo_vehiculo ?? '—'}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${fechaIngreso}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${r.vigilante || '—'}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${horaSalida}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04)">${tarifaDesc} — $${tarifaVal}</td>
          <td style="border-top:1px solid rgba(0,0,0,0.04);font-weight:700">$${formatNumber(adeudoNum)}</td>
        `;
        tbody.appendChild(tr);
      });

      // Después de rellenar filas, actualizar KPIs y footer con los totales calculados
      updateAdeudoKPIsAndFooter(totalAdeudo, totalCarro, totalMoto, filas);

    } catch (e) {
      console.error('history error', e);
      historyTbody.innerHTML = `<tr><td colspan="9" style="padding:12px;color:crimson">Error cargando historial</td></tr>`;
      // En caso de error, también limpiar KPIs de adeudo para evitar info inconsistente
      updateAdeudoKPIsAndFooter(0, 0, 0);
    }
  }

  /**
   * Helper: formatea número a "1,234.56" (dos decimales) según locale ES (puedes cambiar).
   */
  function formatNumber(n) {
    // Usamos toLocaleString para miles y decimales
    return Number(n).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /**
   * Actualiza los KPIs visibles (Total adeudo y adeudo por tipo) y llena el footer de la tabla.
   * - total: número (float) total adeudo
   * - carro: número (float) total adeudo carros
   * - moto: número (float) total adeudo motos
   * - filas: cantidad de filas (opcional)
   */
  function updateAdeudoKPIsAndFooter(total, carro, moto, filas = null) {
    // Actualizar KPI general (al lado del selector)
    if (kpi_adeudo) {
      kpi_adeudo.textContent = '$' + formatNumber(total);
    }

    // Actualizar bloque de adeudo por tipo
    const adeudoCarrosEl = document.getElementById('adeudoCarros');
    const adeudoMotosEl  = document.getElementById('adeudoMotos');
    if (adeudoCarrosEl) adeudoCarrosEl.textContent = '$' + formatNumber(carro);
    if (adeudoMotosEl)  adeudoMotosEl.textContent  = '$' + formatNumber(moto);

    // Construir/actualizar fila footer en la tabla
    const tfoot = document.getElementById('historyTfoot');
    if (!tfoot) return;

    // Crear HTML de la fila de totales; colocamos los totales en las columnas adecuadas
    // Usamos colspan para ajustarlo visualmente.
    const filaTotales = `
      <tr style="border-top:2px solid rgba(0,0,0,0.06);font-weight:800">
        <td colspan="6" style="padding:10px">Totales${filas !== null ? ' — filas: ' + filas : ''}</td>
        <td style="padding:10px;text-align:left">—</td>
        <td style="padding:10px">Adeudos por tipo:</td>
        <td style="padding:10px;white-space:nowrap">$${formatNumber(total)}</td>
      </tr>
      <tr style="font-weight:700;color:var(--muted);font-size:13px">
        <td colspan="6" style="padding:6px;color:var(--muted)">Desglose</td>
        <td></td>
        <td style="padding:6px">Carro — $${formatNumber(carro)}</td>
        <td style="padding:6px">Moto — $${formatNumber(moto)}</td>
      </tr>
    `;

    tfoot.innerHTML = filaTotales;
  }

  async function loadAll() {
    const r = computeRange(rangeSelect.value || '7');
    await loadSummary(r.from, r.to);          // opcional (mantiene otros KPIs)
    await loadIngresos(r.from, r.to);
    const adeudoR = adeudoRange ? computeRange(adeudoRange.value || rangeSelect.value) : r;
    await loadHistory(r.from, r.to); // <-- aquí es donde calculamos y "reemplazamos" los KPIs de adeudo
  }

  // Bind: cuando cambia el select se recarga todo
  rangeSelect.addEventListener('change', function () { loadAll().catch(console.error); });
  if (adeudoRange) adeudoRange.addEventListener('change', function () { loadAll().catch(console.error); });

  // Init
  loadAll().catch(e => console.error(e));
});
