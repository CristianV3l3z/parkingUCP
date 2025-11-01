// public/js/dashboard_readonly.js (versión actualizada con modal de "Detalles")
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

  // Boton y modal
  const modal = document.getElementById('miPasarelaModal');


  // Cerrar modal
  document.getElementById('miPasarelaClose').addEventListener('click', () => {
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
      // Ceil a horas completas
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
      // Llamamos al endpoint existente /api/tiquete y filtramos en frontend
      const res = await fetch('/api/tiquete');
      if (!res.ok) throw new Error('No fue posible obtener tiquetes');
      const data = await res.json();
      const arr = Array.isArray(data) ? data : [];

      // Filtrar por id_vehiculo y ordenar por hora_entrada desc (o created_at) y devolver el primero
      const filtered = arr.filter(t => {
        // modelo puede usar id_vehiculo o id_vehiculo numeric
        return String(t.id_vehiculo) === String(idVehiculo);
      });

      if (filtered.length === 0) return null;

      // ordenar por hora_entrada (desc). Si no existe, por created_at
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

    // calcular adeudo (adeudo parcial si abierto)
    const adeudo = calcAdeudo(ticket);

    // construir HTML con todos los campos relevantes (adaptar según tu modelo)
    const html = `
      <div style="display:flex;gap:18px;align-items:flex-start;">
        <div style="min-width:120px;">
          <div style="width:96px;height:96px;border-radius:12px;background:rgba(0,0,0,0.03);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:28px">
            ${esc((vehiculo?.placa || '').slice(0,2) || (ticket.codigo_uuid || '').slice(0,2))}
          </div>
        </div>

        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;align-items:start;gap:12px">
            <div>
              <div style="font-weight:900;font-size:20px">${esc(vehiculo?.placa ?? '—')} <span style="font-size:14px;color:var(--muted);font-weight:600"> — ${esc(vehiculo?.tipo_vehiculo ?? (ticket.vehiculo?.tipo_vehiculo ?? '—'))}</span></div>
              <div style="font-size:13px;color:var(--muted);margin-top:6px">Marca: ${esc(vehiculo?.marca ?? ticket.vehiculo?.marca ?? '—')}</div>
            </div>

            <div style="text-align:right">
              <div style="font-size:12px;color:var(--muted)">ID Tiquete</div>
              <div style="font-weight:800;margin-top:6px">${esc(ticket.id_tiquete ?? '')}</div>
            </div>
          </div>

          <hr style="margin:12px 0;border:none;border-top:1px solid rgba(0,0,0,0.04)">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
              <div style="font-size:13px;color:var(--muted)">Hora entrada</div>
              <div style="font-weight:700;margin-top:6px">${ticket.hora_entrada ? new Date(ticket.hora_entrada).toLocaleString() : '—'}</div>
            </div>
            <div>
              <div style="font-size:13px;color:var(--muted)">Hora salida</div>
              <div style="font-weight:700;margin-top:6px">${ticket.hora_salida ? new Date(ticket.hora_salida).toLocaleString() : '— (abierto)'}</div>
            </div>

            <div>
              <div style="font-size:13px;color:var(--muted)">Vigilante</div>
              <div style="font-weight:700;margin-top:6px">${esc(ticket.vigilante_nombre ?? (ticket.vigilante?.nombre ?? '—'))}</div>
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

          <div style="margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div>
              <div style="font-size:13px;color:var(--muted)">Adeudo calculado</div>
              <div style="font-weight:900;font-size:18px;margin-top:6px">$${adeudo.toFixed(2)}</div>
            </div>

            <div style="text-align:right">
              <div style="font-size:12px;color:var(--muted)">Activo</div>
              <div style="font-weight:800;margin-top:6px">${ticket.activo ? 'Sí' : 'No'}</div>
            </div>
          </div>
        </div>
      </div>
    `;

    ticketModalBody.innerHTML = html;

/* --- Dentro de dashboard_readonly.js --- */
/* Reemplaza cualquier handler duplicado de ticketModalAction por este bloque dentro de renderTicketModal() */

    // Preparar botón de acción (por si más adelante integras checkout)
    // Mostramos el botón sólo si el tiquete está activo / abierto
    if (!ticket.hora_salida) {
      ticketModalAction.style.display = 'inline-flex';
      ticketModalAction.dataset.tiqueteId = ticket.id_tiquete;
      ticketModalAction.textContent = 'Iniciar pago (pendiente)';

      // Use onclick (asignación única) para evitar múltiples listeners
      ticketModalAction.onclick = async function () {
        const el = this;
        const id = ticket.id_tiquete;
        try {
          el.disabled = true;
          el.dataset.origText = el.dataset.origText || el.innerText;
          el.innerText = 'Generando pago...';

          // llamar al flujo central que abre init_point y hace polling
          const result = await iniciarPago(id, el);

          if (result && result.success) {
            alert('Pago aprobado. Detalle: ' + JSON.stringify(result.pago || {}));
            if (window.loadAndRender) window.loadAndRender();
            closeTicketModal();
          } else {
            // result puede ser { success:false, message: '...' } o undefined
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
  }

  // show / hide modal helpers
  function openTicketModal() { ticketModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
  function closeTicketModal(){ ticketModal.style.display = 'none'; ticketModalBody.innerHTML = ''; document.body.style.overflow = ''; }

  // Attach close handlers
  ticketModalClose && ticketModalClose.addEventListener('click', closeTicketModal);
  ticketModalCloseBtn && ticketModalCloseBtn.addEventListener('click', closeTicketModal);
  // Also close when clicking backdrop (but not when clicking modal panel)
  ticketModal && ticketModal.addEventListener('click', function(e){
    if (e.target === ticketModal) closeTicketModal();
  });

//Punto de retorno

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

    items.forEach(v => {
      const placa = esc((v.placa || '').toUpperCase());
      const tipo = (v.tipo_vehiculo || '').toLowerCase();
      const tipoLabel = tipo === 'carro' ? 'Carro' : (tipo === 'moto' ? 'Moto' : esc(v.tipo_vehiculo || ''));
      const fecha = v.created_at ? new Date(v.created_at).toLocaleString() : '';
      const marca = v.marca ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">Marca: ${esc(v.marca)}</div>` : '';
      const descripcion = v.descripcion ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">${esc(v.descripcion)}</div>` : '';

      const row = document.createElement('div');
      row.className = 'dash-row';
      row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px;background:var(--card);border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.04);justify-content:space-between';

      const left = document.createElement('div');
      left.style.cssText = 'display:flex;align-items:center;gap:12px;min-width:0;flex:1';
      left.innerHTML = `
        <div style="width:56px;height:56px;border-radius:8px;background:rgba(0,0,0,0.02);display:flex;align-items:center;justify-content:center;font-weight:800">${esc(placa.slice(0,2))}</div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <div style="font-weight:800">${placa}</div>
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
      right.style.cssText = 'display:flex;gap:8px;align-items:center';

      // Botón acción: al hacer click busca el último tiquete de este vehículo y llama a iniciarPago
      const actionBtn = document.createElement('button');
      actionBtn.className = 'btn-action';
      actionBtn.textContent = 'Pagar tiquete';
      actionBtn.setAttribute('data-id-vehiculo', v.id_vehiculo || '');

      actionBtn.addEventListener('click', async () => {
        // prevenir doble click por UI (deshabilitar mientras busca)
        if (actionBtn.disabled) return;
        actionBtn.disabled = true;
        actionBtn.textContent = 'Buscando tiquete...';

        try {
          // usar la función que ya tienes para buscar el último tiquete
          const last = await fetchLastTicketForVehicle(v.id_vehiculo);
          if (!last) {
            alert('No se encontró un tiquete para este vehículo.');
            return;
          }

          // si el tiquete ya está cerrado -> avisar
          if (last.hora_salida) {
            alert('El tiquete ya está cerrado. No se puede pagar.');
            return;
          }

          // Llamar a la función iniciarPago (ya está definida al final del archivo)
          // Pasamos el id del tiquete (no el id del vehículo)
          iniciarPago(last.id_tiquete, actionBtn);
        } catch (err) {
          console.error('Error al iniciar pago desde botón:', err);
          alert('Error al iniciar el pago. Revisa consola.');
        } finally {
          // reactivar el botón (la función iniciarPago se encarga de bloquear internamente mientras hace su trabajo)
          actionBtn.disabled = false;
          actionBtn.textContent = 'Pagar tiquete';
        }
      });


      // Detalles: abrir modal con info del ultimo tiquete de este vehiculo
      const detailsBtn = document.createElement('button');
      detailsBtn.className = 'btn-ghost';
      detailsBtn.textContent = 'Detalles';
      detailsBtn.style.cursor = 'pointer';
      detailsBtn.addEventListener('click', async () => {
        // mostrar loading inmediato
        ticketModalBody.innerHTML = `<div style="padding:12px;color:var(--muted)">Cargando...</div>`;
        openTicketModal();

        // buscamos el último tiquete para este vehículo
        const last = await fetchLastTicketForVehicle(v.id_vehiculo);
        // si existe lo mostramos con el vehiculo (podemos pasar v para más info)
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
  })();

  // Exponer helpers globalmente (si necesitas reutilizar)
  window.dashboardUtils = { renderRows, fetchLastTicketForVehicle };


// código dentro de DOMContentLoaded (puedes pegar al final de dashboard_readonly.js)
(function(){
  const modal = document.getElementById('miPasarelaModal');
  const closeBtn = document.getElementById('miPasarelaClose');
  const pagarBtn = document.getElementById('miPasarelaPagar');
  const abrirMPBtn = document.getElementById('miPasarelaAbrirMP');
  const placaEl = document.getElementById('miPasarelaPlaca');
  const tituloEl = document.getElementById('miPasarelaTitulo');
  const vehInfoEl = document.getElementById('miPasarelaVehInfo');
  const montoEl = document.getElementById('miPasarelaMonto');

  // abrir pasarela con datos (llamar desde tu código cuando quieras)
  window.openMiPasarela = function({ id_tiquete, placa, tipo, detalle, monto }) {
    // rellenar UI
    placaEl.textContent = (placa || '--').slice(0,2);
    tituloEl.textContent = `Tiquete #${id_tiquete}`;
    vehInfoEl.textContent = `${tipo || '--'} • ${detalle || ''}`;
    montoEl.textContent = '$' + (Number(monto || 0).toFixed(2));
    // guardar info en dataset del botón
    pagarBtn.dataset.tiquete = id_tiquete;
    pagarBtn.dataset.monto = monto;
    abrirMPBtn.dataset.tiquete = id_tiquete;
    abrirMPBtn.dataset.monto = monto;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  function closeModal(){
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
  closeBtn && closeBtn.addEventListener('click', closeModal);

  // botón principal: usa startCheckoutTiquete (de checkout_mp.js)
  pagarBtn && pagarBtn.addEventListener('click', async function(){
    const id = this.dataset.tiquete;
    const monto = Number(this.dataset.monto || 0);
    try {
      this.disabled = true; this.textContent = 'Abriendo pago...';
      // startCheckoutTiquete está expuesta globalmente por checkout_mp.js
      const result = await window.startCheckoutTiquete(id, `Pago Tiquete #${id}`, monto, 'COP');
      if (result && result.success) {
        alert('Pago aprobado ✅');
        // opcional: refrescar UI
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

    // Si ya hay un pago en progreso, evitamos duplicar
    if (window._mpLocks && window._mpLocks[id]) {
      alert('Pago ya en progreso para este tiquete. Espera o cierra la ventana de pago.');
      return;
    }

    // Llamar al flujo central que crea preferencia, abre ventana y hace polling
    try {
      await iniciarPago(id, null); // pasar null como boton porque no hay boton UI aquí
    } catch (e) {
      console.error('Error al abrir MP desde AbrirMPBtn:', e);
      alert('Error al iniciar pago: ' + (e.message || e));
    }
  });

  // cerrar si clic fuera del panel (opcional)
  modal && modal.addEventListener('click', function(e){
    if (e.target === modal) closeModal();
  });
})();


  // Map para bloquear por tiquete (evita doble requests)
  window._mpLocks = window._mpLocks || {};

  /**
   * iniciarPago - inicia el flujo de Mercado Pago para un tiquete
   * @param {Number|String} idTiquete
   * @param {HTMLElement} botonEl (opcional) para deshabilitar durante la petición
   */
/**
 * Reemplazar la función iniciarPago por esta versión que:
 * - abre init_point en una nueva pestaña/window (target _blank)
 * - no intenta cerrar la pestaña desde el padre
 * - hace polling al backend para comprobar estado del pago
 * - usa window._mpLocks para evitar duplicados y se asegura de liberar lock
 */
async function iniciarPago(idTiquete, botonEl = null) {
  // bloqueo por tiquete (evita doble-click / doble petición)
  if (window._mpLocks[idTiquete]) {
    console.debug('Pago ya en progreso para tiquete', idTiquete);
    return { success: false, message: 'Pago en progreso' };
  }
  window._mpLocks[idTiquete] = true;

  if (botonEl) {
    botonEl.disabled = true;
    botonEl.dataset.origText = botonEl.innerText || botonEl.textContent;
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

    // Abrir en nueva pestaña/nueva ventana del navegador (sin características) -> _blank
    // Esto evita el comportamiento de "popup" que muchos navegadores bloquean cuando no es un user gesture directo.
    // Asegúrate de que iniciarPago se invoque desde un click de usuario (para evitar bloqueadores).
    window.open(init, '_blank');

    // Polling para confirmar estado (no dependemos de child.closed)
    const start = Date.now();
    const timeoutMs = 90 * 1000; // 90s
    const intervalMs = 3000;

    while (true) {
      // consultar status en backend
      try {
        const r = await fetch(`/api/checkout/status/${idTiquete}`, { headers: { 'Accept': 'application/json' } });
        if (r.ok) {
          const body = await r.json();
          const pago = body.pago;
          if (pago && pago.estado_pago && pago.estado_pago !== 'pendiente') {
            // pago cambiado: desbloquear y devolver resultado
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
        // no romper por pequeños fallos de red, solo continuar polling
      }

      // timeout check
      if (Date.now() - start > timeoutMs) {
        window._mpLocks[idTiquete] = false;
        if (botonEl) {
          botonEl.disabled = false;
          botonEl.innerText = botonEl.dataset.origText || 'Pagar';
        }
        // informar timeout para que el usuario pueda reintentar
        throw new Error('Timeout esperando confirmación de pago. Puedes intentar de nuevo.');
      }

      // esperar interval
      await new Promise(res => setTimeout(res, intervalMs));
    }

  } catch (e) {
    console.error('Error al iniciar el pago.', e);
    // asegurar liberar lock en caso de excepción
    window._mpLocks[idTiquete] = false;
    if (botonEl) {
      botonEl.disabled = false;
      botonEl.innerText = botonEl.dataset.origText || 'Pagar';
    }
    // propagar error para que el caller lo muestre
    throw e;
  }
}

});
