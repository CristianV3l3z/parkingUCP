// public/js/dashboard_readonly.js (versión responsive mejorada para móvil)
document.addEventListener('DOMContentLoaded', function () {
  const listEl = document.getElementById('dashVehicles');
  const placeholder = document.getElementById('dashPlaceholder');
  const searchInput = document.getElementById('dashSearch');
  const totalEl = document.getElementById('dashTotal');
  const carEl = document.getElementById('dashCar');
  const motoEl = document.getElementById('dashMoto');
  const refreshBtn = document.getElementById('refreshBtn');

  // Modal elements
  const ticketModal = document.getElementById('ticketModal');
  const ticketModalBody = document.getElementById('ticketModalBody');
  const ticketModalClose = document.getElementById('ticketModalClose');
  const ticketModalCloseBtn = document.getElementById('ticketModalCloseBtn');
  const ticketModalAction = document.getElementById('ticketModalAction');
  const ticketModalTitle = document.getElementById('ticketModalTitle');

  // Boton y modal de pasarela
  const modal = document.getElementById('miPasarelaModal');

  // helper: detectar móvil (puedes ajustar el ancho)
  const isMobile = () => window.innerWidth <= 640;

  // debounce util
  function debounce(fn, wait = 120) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  // cerrar modal pasarela
  document.getElementById('miPasarelaClose')?.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // escape helper
  function esc(s){ if(!s && s !== 0) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])); }

  // calcular adeudo (igual que en tiquete.js)
  function calcAdeudo(ticket) {
    try {
      const val = parseFloat(ticket.tarifa?.valor ?? 0) || 0;
      if (!ticket.hora_entrada || !val) return 0.00;
      const start = Date.parse(ticket.hora_entrada);
      const end = ticket.hora_salida ? Date.parse(ticket.hora_salida) : Date.now();
      if (isNaN(start) || isNaN(end)) return 0.00;
      const hours = Math.ceil((end - start) / (1000 * 60 * 60));
      return Number((hours * val).toFixed(2));
    } catch (e) {
      console.error('calcAdeudo error', e, ticket);
      return 0.00;
    }
  }

  // fetch all tickets then return the last one for a vehiculo id
  async function fetchLastTicketForVehicle(idVehiculo) {
    try {
      const res = await fetch('/api/tiquete');
      if (!res.ok) throw new Error('No fue posible obtener tiquetes');
      const data = await res.json();
      const arr = Array.isArray(data) ? data : [];
      const filtered = arr.filter(t => String(t.id_vehiculo) === String(idVehiculo));
      if (filtered.length === 0) return null;
      filtered.sort((a,b) => {
        const ta = a.hora_entrada ? Date.parse(a.hora_entrada) : (a.created_at ? Date.parse(a.created_at) : 0);
        const tb = b.hora_entrada ? Date.parse(b.hora_entrada) : (b.created_at ? Date.parse(b.created_at) : 0);
        return tb - ta;
      });
      return filtered[0];
    } catch (e) {
      console.error('fetchLastTicketForVehicle error', e);
      return null;
    }
  }

  // Render del contenido del modal con la información del tiquete (y vehículo)
  function renderTicketModal(ticket, vehiculo) {
    if (!ticket) {
      ticketModalBody.innerHTML = `<div style="padding:12px;color:var(--muted)">No hay tiquete para este vehículo.</div>`;
      ticketModalAction.style.display = 'none';
      ticketModalTitle.textContent = 'Detalle del tiquete';
      return;
    }

    const adeudo = calcAdeudo(ticket);
    const mobile = isMobile();

    // adaptaciones de tamaño para móvil
    const avatarSize = mobile ? 68 : 96;
    const gap = mobile ? 10 : 18;
    const gridCols = mobile ? '1fr' : '1fr 1fr';
    const containerFlexDir = mobile ? 'column' : 'row';
    const panelPadding = mobile ? '12px' : '18px';
    const fontSizeTitle = mobile ? '18px' : '20px';

    const html = `
      <div style="display:flex;flex-direction:${containerFlexDir};gap:${gap}px;align-items:flex-start;padding:${panelPadding};box-sizing:border-box;">
        <div style="min-width:${avatarSize}px;flex:0 0 ${avatarSize}px">
          <div style="width:${avatarSize}px;height:${avatarSize}px;border-radius:12px;background:rgba(0,0,0,0.03);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:${Math.round(avatarSize/3)}px">
            ${esc((vehiculo?.placa || '').slice(0,2) || (ticket.codigo_uuid || '').slice(0,2))}
          </div>
        </div>

        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;flex-wrap:wrap">
            <div style="min-width:0">
              <div style="font-weight:900;font-size:${fontSizeTitle};word-break:break-word">${esc(vehiculo?.placa ?? '—')} <span style="font-size:14px;color:var(--muted);font-weight:600"> — ${esc(vehiculo?.tipo_vehiculo ?? (ticket.vehiculo?.tipo_vehiculo ?? '—'))}</span></div>
              <div style="font-size:13px;color:var(--muted);margin-top:6px">Marca: ${esc(vehiculo?.marca ?? ticket.vehiculo?.marca ?? '—')}</div>
            </div>

            <div style="text-align:right;min-width:120px">
              <div style="font-size:12px;color:var(--muted)">ID Tiquete</div>
              <div style="font-weight:800;margin-top:6px">${esc(ticket.id_tiquete ?? '')}</div>
            </div>
          </div>

          <hr style="margin:12px 0;border:none;border-top:1px solid rgba(0,0,0,0.04)">

          <div style="display:grid;grid-template-columns:${gridCols};gap:10px">
            <div>
              <div style="font-size:13px;color:var(--muted)">Hora entrada</div>
              <div style="font-weight:700;margin-top:6px">${ticket.hora_entrada ? new Date(ticket.hora_entrada).toLocaleString() : '—'}</div>
            </div>
            <div>
              <div style="font-size:13px;color:var(--muted)">Hora salida</div>
              <div style="font-weight:700;margin-top:6px">${ticket.hora_salida ? new Date(ticket.hora_salida).toLocaleString() : '— (abierto)'}</div>
            </div>

          
            <div>
              <div style="font-size:13px;color:var(--muted)">Código</div>
              <div style="font-weight:700;margin-top:6px">${esc(ticket.codigo_uuid ?? '—')}</div>
            </div>

            <div>
              <div style="font-size:13px;color:var(--muted)">Tarifa</div>
              <div style="font-weight:700;margin-top:6px">${ticket.tarifa ? esc(ticket.tarifa.descripcion ?? '') + ' — $' + (parseFloat(ticket.tarifa.valor || 0).toFixed(2)) : '—'}</div>
            </div>

            <div>
              <div style="font-size:13px;color:var(--muted)">Estado</div>
              <div style="font-weight:700;margin-top:6px">${Number(ticket.estado) === 1 ? '<span style="color:var(--accent)">Activo</span>' : 'Cerrado'}</div>
            </div>
          </div>

          <div style="margin-top:12px">
            <div style="font-size:13px;color:var(--muted)">Observaciones</div>
            <div style="margin-top:6px">${esc(ticket.observaciones ?? '—')}</div>
          </div>

          <div style="margin-top:12px;display:flex;flex-direction:${mobile ? 'column' : 'row'};align-items:${mobile ? 'stretch' : 'center'};justify-content:space-between;gap:12px">
            <div style="flex:1 1 auto">
              <div style="font-size:13px;color:var(--muted)">Adeudo calculado</div>
              <div style="font-weight:900;font-size:18px;margin-top:6px">$${adeudo.toFixed(2)}</div>
            </div>

            <div style="text-align:${mobile ? 'left' : 'right'};flex:0 0 ${mobile ? '100%' : 'auto'}">
              <div style="font-size:12px;color:var(--muted)">Activo</div>
              <div style="font-weight:800;margin-top:6px">${ticket.activo ? 'Sí' : 'No'}</div>
            </div>
          </div>
        </div>
      </div>
    `;

    // Panel wrapper para controlar ancho en móvil (si no existe un panel fuera)
    // Se asume que ticketModalBody está dentro de un panel en el HTML. Si no, al menos el contenido tendrá maxWidth.
    ticketModalBody.innerHTML = `<div style="box-sizing:border-box;max-width:${mobile ? '100%' : '720px'};width:100%;">${html}</div>`;

    // Preparar botón de acción
    if (!ticket.hora_salida) {
      ticketModalAction.style.display = 'inline-flex';
      ticketModalAction.dataset.tiqueteId = ticket.id_tiquete;
      ticketModalAction.textContent = 'Iniciar pago (pendiente)';

      ticketModalAction.onclick = async function () {
        const el = this;
        const id = ticket.id_tiquete;
        try {
          el.disabled = true;
          el.dataset.origText = el.dataset.origText || el.innerText;
          el.innerText = 'Generando pago...';

          const result = await iniciarPago(id, el);

          if (result && result.success) {
            alert('Pago aprobado. Detalle: ' + JSON.stringify(result.pago || {}));
            if (window.loadAndRender) window.loadAndRender();
            closeTicketModal();
          } else {
            alert('Pago no aprobado o pendiente. Revisa la tabla de pagos.');
          }
        } catch (err) {
          console.error('Error al iniciar pago desde modal:', err);
          alert('Error al iniciar pago: ' + (err.message || err));
        } finally {
          el.disabled = false;
          el.innerText = el.dataset.origText || 'Iniciar pago (pendiente)';
        }
      };

    } else {
      ticketModalAction.style.display = 'none';
      ticketModalAction.onclick = null;
    }

    ticketModalTitle.textContent = `Tiquete #${esc(ticket.id_tiquete ?? '')} — ${esc(vehiculo?.placa ?? '')}`;

    // si estamos en móvil, ajustar modal panel (si existe panel externo)
    if (ticketModal) {
      // centrar abajo en móvil o al centro en desktop
      ticketModal.style.alignItems = isMobile() ? 'flex-end' : 'center';
    }
  }

  // show / hide modal helpers
  function openTicketModal() { ticketModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
  function closeTicketModal(){ ticketModal.style.display = 'none'; ticketModalBody.innerHTML = ''; document.body.style.overflow = ''; }

  // Attach close handlers
  ticketModalClose && ticketModalClose.addEventListener('click', closeTicketModal);
  ticketModalCloseBtn && ticketModalCloseBtn.addEventListener('click', closeTicketModal);
  ticketModal && ticketModal.addEventListener('click', function(e){
    if (e.target === ticketModal) closeTicketModal();
  });

  // fetchVehiculos remains same as before
  async function fetchVehiculos(){
    try {
      const res = await fetch('/api/vehiculo');
      if(!res.ok) throw new Error('Error al obtener vehículos');
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    } catch (e) {
      console.error(e);
      return [];
    }
  }

  // Helper para estilizar inputs y botones para móvil al cargar
  function applyGlobalResponsiveTweaks() {
    const mobile = isMobile();
    if (searchInput) {
      searchInput.style.padding = mobile ? '10px 12px' : '8px 10px';
      searchInput.style.fontSize = mobile ? '15px' : '14px';
      searchInput.style.boxSizing = 'border-box';
    }
    if (refreshBtn) {
      refreshBtn.style.padding = mobile ? '10px 12px' : '8px 10px';
      refreshBtn.style.fontSize = mobile ? '15px' : '13px';
    }
    // modal pasarela: panel ancho
    if (modal) {
      modal.style.alignItems = mobile ? 'flex-end' : 'center';
    }
  }

  // renderRows: ahora aplica estilos responsivos inline
  function renderRows(items){
    // stats
    totalEl && (totalEl.textContent = items.length);
    carEl && (carEl.textContent = items.filter(v => (v.tipo_vehiculo||'').toLowerCase() === 'carro').length);
    motoEl && (motoEl.textContent = items.filter(v => (v.tipo_vehiculo||'').toLowerCase() === 'moto').length);

    // clear & render
    if(placeholder) placeholder.style.display = 'none';
    listEl.innerHTML = '';

    if(items.length === 0){
      listEl.innerHTML = '<div style="padding:18px;background:var(--card);border-radius:10px;color:var(--muted)">No hay vehículos registrados.</div>';
      return;
    }

    const mobile = isMobile();

    items.forEach(v => {
      const placa = esc((v.placa || '').toUpperCase());
      const tipo = (v.tipo_vehiculo || '').toLowerCase();
      const tipoLabel = tipo === 'carro' ? 'Carro' : (tipo === 'moto' ? 'Moto' : esc(v.tipo_vehiculo || ''));
      const fecha = v.created_at ? new Date(v.created_at).toLocaleString() : '';
      const marca = v.marca ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">Marca: ${esc(v.marca)}</div>` : '';
      const descripcion = v.descripcion ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">${esc(v.descripcion)}</div>` : '';

      const row = document.createElement('div');
      row.className = 'dash-row';
      row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px;background:var(--card);border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.04);justify-content:space-between;box-sizing:border-box;';
      // responsive per-row
      if (mobile) {
        row.style.flexDirection = 'column';
        row.style.alignItems = 'stretch';
        row.style.gap = '10px';
        row.style.padding = '12px';
      } else {
        row.style.flexDirection = 'row';
      }

      const left = document.createElement('div');
      left.style.cssText = 'display:flex;align-items:center;gap:12px;min-width:0;flex:1;box-sizing:border-box';
      left.innerHTML = `
        <div style="width:56px;height:56px;border-radius:8px;background:rgba(0,0,0,0.02);display:flex;align-items:center;justify-content:center;font-weight:800">${esc(placa.slice(0,2))}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <div style="font-weight:800;min-width:0;word-break:break-word">${placa}</div>
            <div style="font-size:12px">
              <span style="padding:6px 8px;border-radius:999px;background:${tipo==='carro' ? 'rgba(255,204,0,0.12)' : 'rgba(80,170,255,0.08)'};color:${tipo==='carro' ? 'var(--accent)' : '#52a8ff'};font-weight:700">
                ${esc(tipoLabel)}
              </span>
            </div>
          </div>
          <div style="font-size:13px;color:var(--muted);margin-top:6px">Añadido: ${esc(fecha)}</div>
          ${marca}
          ${descripcion}
        </div>`;

      const right = document.createElement('div');
      right.style.cssText = 'display:flex;gap:8px;align-items:center;box-sizing:border-box';
      if (mobile) {
        right.style.flexDirection = 'column';
        right.style.alignItems = 'stretch';
      }

      // Botón acción
      const actionBtn = document.createElement('button');
      actionBtn.className = 'btn-action';
      actionBtn.textContent = 'Pagar tiquete';
      actionBtn.setAttribute('data-id-vehiculo', v.id_vehiculo || '');
      actionBtn.style.cssText = 'padding:8px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700';
      if (mobile) {
        actionBtn.style.width = '100%';
        actionBtn.style.padding = '12px';
      }

      actionBtn.addEventListener('click', async () => {
        if (actionBtn.disabled) return;
        actionBtn.disabled = true;
        const origText = actionBtn.textContent;
        actionBtn.textContent = 'Buscando tiquete...';

        try {
          const last = await fetchLastTicketForVehicle(v.id_vehiculo);
          if (!last) {
            alert('No se encontró un tiquete para este vehículo.');
            return;
          }
          if (last.hora_salida) {
            alert('El tiquete ya está cerrado. No se puede pagar.');
            return;
          }
          iniciarPago(last.id_tiquete, actionBtn);
        } catch (err) {
          console.error('Error al iniciar pago desde botón:', err);
          alert('Error al iniciar el pago. Revisa consola.');
        } finally {
          actionBtn.disabled = false;
          actionBtn.textContent = origText;
        }
      });

      // Detalles: abrir modal con info del ultimo tiquete de este vehiculo
      const detailsBtn = document.createElement('button');
      detailsBtn.className = 'btn-ghost';
      detailsBtn.textContent = 'Detalles';
      detailsBtn.style.cursor = 'pointer';
      detailsBtn.style.cssText += ';padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);background:transparent';
      if (mobile) {
        detailsBtn.style.width = '100%';
        detailsBtn.style.marginTop = '6px';
      }
      detailsBtn.addEventListener('click', async () => {
        ticketModalBody.innerHTML = `<div style="padding:12px;color:var(--muted)">Cargando...</div>`;
        openTicketModal();
        const last = await fetchLastTicketForVehicle(v.id_vehiculo);
        renderTicketModal(last, v);
      });

      right.appendChild(actionBtn);
      right.appendChild(detailsBtn);

      row.appendChild(left);
      row.appendChild(right);

      listEl.appendChild(row);
    });
  }

  // initial load + wire search
  (async function init(){
    applyGlobalResponsiveTweaks();

    let items = await fetchVehiculos();
    renderRows(items);

    // live filter
    searchInput.addEventListener('input', function(){
      const q = (this.value || '').trim().toLowerCase();
      const filtered = items.filter(v => (v.placa || '').toLowerCase().includes(q));
      renderRows(filtered);
    });

    // refresh button
    refreshBtn && refreshBtn.addEventListener('click', async function(){
      this.disabled = true;
      this.textContent = 'Actualizando...';
      items = await fetchVehiculos();
      renderRows(items);
      this.disabled = false;
      this.textContent = '⟳ Actualizar';
    });

    // ajustar al resize (debounced)
    window.addEventListener('resize', debounce(() => {
      applyGlobalResponsiveTweaks();
      // re-render filas para aplicar el layout mobile/desktop
      // Si quieres evitar refetch, reutilizamos items
      renderRows(items);
    }, 150));
  })();

  // Exponer helpers globalmente (si necesitas reutilizar)
  window.dashboardUtils = { renderRows, fetchLastTicketForVehicle };

  // código para la pasarela (modal miPasarelaModal) - mantenido igual, con pequeños tweaks de tamaño
  (function(){
    const modal = document.getElementById('miPasarelaModal');
    const closeBtn = document.getElementById('miPasarelaClose');
    const pagarBtn = document.getElementById('miPasarelaPagar');
    const abrirMPBtn = document.getElementById('miPasarelaAbrirMP');
    const placaEl = document.getElementById('miPasarelaPlaca');
    const tituloEl = document.getElementById('miPasarelaTitulo');
    const vehInfoEl = document.getElementById('miPasarelaVehInfo');
    const montoEl = document.getElementById('miPasarelaMonto');

    window.openMiPasarela = function({ id_tiquete, placa, tipo, detalle, monto }) {
      placaEl.textContent = (placa || '--').slice(0,2);
      tituloEl.textContent = `Tiquete #${id_tiquete}`;
      vehInfoEl.textContent = `${tipo || '--'} • ${detalle || ''}`;
      montoEl.textContent = '$' + (Number(monto || 0).toFixed(2));
      pagarBtn.dataset.tiquete = id_tiquete;
      pagarBtn.dataset.monto = monto;
      abrirMPBtn.dataset.tiquete = id_tiquete;
      abrirMPBtn.dataset.monto = monto;
      // ajustar estilo del modal según tamaño
      if (modal) {
        modal.style.display = 'flex';
        modal.style.alignItems = isMobile() ? 'flex-end' : 'center';
      }
      document.body.style.overflow = 'hidden';
    };

    function closeModal(){
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
    closeBtn && closeBtn.addEventListener('click', closeModal);

    pagarBtn && pagarBtn.addEventListener('click', async function(){
      const id = this.dataset.tiquete;
      const monto = Number(this.dataset.monto || 0);
      try {
        this.disabled = true; this.textContent = isMobile() ? 'Abriendo...' : 'Abriendo pago...';
        const result = await window.startCheckoutTiquete(id, `Pago Tiquete #${id}`, monto, 'COP');
        if (result && result.success) {
          alert('Pago aprobado ✅');
          if (window.loadAndRender) window.loadAndRender();
          closeModal();
        } else {
          alert('Pago no aprobado o pendiente. Revisa la tabla de pagos.');
        }
      } catch (e) {
        console.error(e);
        alert('Error en el proceso de pago: ' + (e.message || e));
      } finally {
        this.disabled = false; this.textContent = 'Pagar con Mercado Pago';
      }
    });

    abrirMPBtn && abrirMPBtn.addEventListener('click', async function(){
      const id = this.dataset.tiquete;
      if (!id) { alert('Tiquete no definido'); return; }
      if (window._mpLocks && window._mpLocks[id]) {
        alert('Pago ya en progreso para este tiquete. Espera o cierra la ventana de pago.');
        return;
      }
      try {
        await iniciarPago(id, null);
      } catch (e) {
        console.error('Error al abrir MP desde AbrirMPBtn:', e);
        alert('Error al iniciar pago: ' + (e.message || e));
      }
    });

    modal && modal.addEventListener('click', function(e){
      if (e.target === modal) closeModal();
    });
  })();

  // Map para bloquear por tiquete (evita doble requests)
  window._mpLocks = window._mpLocks || {};

  // iniciarPago: idéntica a la tuya, mantenida aquí
  async function iniciarPago(idTiquete, botonEl = null) {
    if (window._mpLocks[idTiquete]) {
      console.debug('Pago ya en progreso para tiquete', idTiquete);
      return { success: false, message: 'Pago en progreso' };
    }
    window._mpLocks[idTiquete] = true;

    if (botonEl) {
      botonEl.disabled = true;
      botonEl.dataset.origText = botonEl.dataset.origText || botonEl.innerText;
      botonEl.innerText = 'Generando pago...';
    }

    try {
      const res = await fetch('/api/checkout/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
        body: JSON.stringify({ id_tiquete: idTiquete })
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok) {
        console.error('Error creando preferencia:', json);
        throw new Error(json.message || 'Error creando preferencia');
      }

      const init = json.init_point;
      if (!init) {
        throw new Error('init_point no disponible desde el servidor');
      }

      window.open(init, '_blank');

      const start = Date.now();
      const timeoutMs = 90 * 1000; // 90s
      const intervalMs = 3000;

      while (true) {
        try {
          const r = await fetch(`/api/checkout/status/${idTiquete}`, { headers: { 'Accept': 'application/json' } });
          if (r.ok) {
            const body = await r.json();
            const pago = body.pago;
            if (pago && pago.estado_pago && pago.estado_pago !== 'pendiente') {
              window._mpLocks[idTiquete] = false;
              if (botonEl) {
                botonEl.disabled = false;
                botonEl.innerText = botonEl.dataset.origText || 'Pagar';
              }
              const success = String(pago.estado_pago).toLowerCase() === 'aprobado' || String(pago.estado_pago).toLowerCase() === 'approved';
              return { success, pago };
            }
          }
        } catch (errPoll) {
          console.debug('poll error', errPoll);
        }

        if (Date.now() - start > timeoutMs) {
          window._mpLocks[idTiquete] = false;
          if (botonEl) {
            botonEl.disabled = false;
            botonEl.innerText = botonEl.dataset.origText || 'Pagar';
          }
          throw new Error('Timeout esperando confirmación de pago. Puedes intentar de nuevo.');
        }

        await new Promise(res => setTimeout(res, intervalMs));
      }

    } catch (e) {
      console.error('Error al iniciar el pago.', e);
      window._mpLocks[idTiquete] = false;
      if (botonEl) {
        botonEl.disabled = false;
        botonEl.innerText = botonEl.dataset.origText || 'Pagar';
      }
      throw e;
    }
  }

}); // DOMContentLoaded end
