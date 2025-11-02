// public/JS/tiquetes.js
document.addEventListener('DOMContentLoaded', function () {
  const ticketsList = document.getElementById('ticketsList');
  const totalTicketsEl = document.getElementById('totalTickets');
  const activeTicketsEl = document.getElementById('activeTickets');
  const totalDebtEl = document.getElementById('totalDebt');
  const searchInput = document.getElementById('searchTickets');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  let ticketsCache = [];

  function esc(s) { if (s === null || s === undefined) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])); }

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

  function renderList(list) {
    if (!ticketsList) return;
    ticketsList.innerHTML = '';

    const q = (searchInput?.value || '').trim().toLowerCase();

    const filtered = list.filter(t => {
      if (!q) return true;
      const placa = (t.vehiculo?.placa || '').toLowerCase();
      const code = (t.codigo_uuid || '').toLowerCase();
      return placa.includes(q) || code.includes(q);
    });

    // stats
    totalTicketsEl && (totalTicketsEl.textContent = list.length);
    const activeCount = list.filter(t => Number(t.estado) === 1).length;
    activeTicketsEl && (activeTicketsEl.textContent = activeCount);

    const totalDebt = list.reduce((sum, t) => sum + calcAdeudo(t), 0);
    totalDebtEl && (totalDebtEl.textContent = '$' + totalDebt.toFixed(2));

    if (filtered.length === 0) {
      ticketsList.innerHTML = '<div style="padding:18px;background:var(--card);border-radius:10px;color:var(--muted)">No hay tiquetes.</div>';
      return;
    }

    filtered.forEach(t => {
      const placa = esc(t.vehiculo?.placa ?? '---');
      const tipo = (t.vehiculo?.tipo_vehiculo || '').toLowerCase();
      const tipoLabel = tipo === 'carro' ? 'Carro' : (tipo === 'moto' ? 'Moto' : (t.vehiculo?.tipo_vehiculo || ''));
      const entrada = t.hora_entrada ? new Date(t.hora_entrada).toLocaleString() : '';
      const marca = t.vehiculo?.marca ?? '—';
      const tarifaLabel = t.tarifa ? `${t.tarifa.descripcion ?? t.tarifa.tipo_vehiculo} — ${t.tarifa.valor ? ('$' + parseFloat(t.tarifa.valor).toFixed(2)) : ''}` : '—';
      const adeudo = calcAdeudo(t);

      // === vigilante: múltiples fallbacks ===
      // 1) t.vigilante_nombre (campo denormalizado en tiquete)
      // 2) t.vigilante.nombre_completo
      // 3) t.vigilante.nombre + ' ' + t.vigilante.apellido
      // 4) t.vigilante.nombre
      // 5) fallback '—'
      const vigilanteNombre = (
        (t.vigilante_nombre && String(t.vigilante_nombre).trim()) ||
        (t.vigilante && (
           (t.vigilante.nombre && String(t.vigilante.nombre).trim()) ||
           (t.vigilante.nombre ? `${t.vigilante.nombre}` : (t.vigilante.nombre || null))
         )) ||
        (t.id_vigilante ? `Vigilante #${t.id_vigilante}` : null) ||
        '—'
      );

      const isActive = Number(t.estado) === 1;
      const badge = isActive ? `<span style="background:#ffe9a8;color:#6a5200;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px">Activo</span>`
                             : `<span style="background:#d9d9d9;color:#444;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px">Cerrado</span>`;

      const card = document.createElement('div');
      card.className = 'ticket-card';
      card.style.cssText = 'background:var(--card);padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.04);display:flex;gap:12px;align-items:flex-start;';

      card.innerHTML = `
        <div style="width:72px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px">
          ${esc((placa || '—').slice(0,2))}
        </div>

        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:10px;justify-content:space-between">
            <div>
              <div style="font-weight:800;font-size:18px">${placa}</div>
              <div style="font-size:13px;color:var(--muted);margin-top:6px">Entrada: ${esc(entrada)}</div>
            </div>
            <div style="text-align:right">
              ${badge}
              <div style="font-size:12px;color:var(--muted);margin-top:6px">${esc(tipoLabel)}</div>
            </div>
          </div>

          <div style="margin-top:10px;font-size:14px;color:var(--muted)">
            <div>Vigilante: ${esc(vigilanteNombre)}</div>
            <div>Marca: ${esc(marca)}</div>
            <div>Tarifa: ${esc(tarifaLabel)}</div>
          </div>

          <div style="margin-top:12px;font-weight:700;font-size:16px">Adeudo: $${adeudo.toFixed(2)}</div>

          <div style="margin-top:8px;color:var(--muted);font-size:13px">
            <div>Código: ${esc(t.codigo_uuid || '—')}</div>
            <div>Observaciones: ${esc(t.observaciones || '—')}</div>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:8px;margin-left:12px;align-items:flex-end">
          ${ isActive ? `
            <button class="btn-action btn-close" data-id="${esc(t.id_tiquete ?? t.id ?? '')}" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);background:#eaf9ed;cursor:pointer">✅ Cerrar</button>
          ` : `
            <button class="btn-action btn-activate" data-id="${esc(t.id_tiquete ?? t.id ?? '')}" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);background:#e8f0ff;cursor:pointer">🔁 Activar</button>
            <button class="btn-action btn-delete" data-id="${esc(t.id_tiquete ?? t.id ?? '')}" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);background:#fff0f0;cursor:pointer">🗑️ Eliminar</button>
          `}
        </div>
      `;

      ticketsList.appendChild(card);
    });

    // handlers (si usas delegación en lugar de re-attach, mejor)
    // Aquí mantenemos el attach que ya tenías:
    ticketsList.querySelectorAll('.btn-close').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!confirm('¿Cerrar este tiquete?')) return;
        await toggleTicketState(id, 0);
      });
    });

    ticketsList.querySelectorAll('.btn-activate').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!confirm('¿Reactivar este tiquete?')) return;
        await toggleTicketState(id, 1);
      });
    });

    ticketsList.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar este tiquete? Esta acción es irreversible.')) return;
        await deleteTicket(id);
      });
    });
  }

  async function fetchTickets() {
    try {
      const res = await fetch('/api/tiquete');
      if (!res.ok) throw new Error('No fue posible obtener tiquetes');
      const data = await res.json();
      console.debug('[tiquetes] /api/tiquete response:', data);
      ticketsCache = Array.isArray(data) ? data : [];
      return ticketsCache;
    } catch (e) {
      console.error(e);
      ticketsCache = [];
      return [];
    }
  }

  async function loadAndRender() {
    await fetchTickets();
    renderList(ticketsCache);
  }

  async function toggleTicketState(id, newState) {
    try {
      const payload = { estado: newState };
      const res = await fetch(`/api/tiquete/${id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const txt = await res.text();
        let json = null;
        try { json = JSON.parse(txt); } catch(e){ json = null; }
        alert(json?.message || 'Error actualizando tiquete');
        return;
      }

      await loadAndRender();
    } catch (err) {
      console.error(err);
      alert('Error en la petición. Revisa consola.');
    }
  }

// delete ticket (now deactivates ticket + hides vehicle)
async function deleteTicket(id) {
  try {
    const res = await fetch(`/api/tiquete/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    });
    if (!res.ok) {
      const txt = await res.text();
      let json = null;
      try { json = JSON.parse(txt); } catch(e){ json = null; }
      alert(json?.message || 'No se pudo desactivar el tiquete.');
      return;
    }
    // refrescar la UI de tiquetes
    await loadAndRender();

    // opcional: notificar a ingresos para que actualice si está abierta.
    // Si usas websockets o Broadcast/Livewire sería ideal.
    // Como workaround pedimos al servidor la nueva lista (no actualiza otra pestaña automáticamente)
    try {
      await fetch('/api/vehiculo'); // simple ping / actualiza cache servidor si aplica
    } catch(e) { /* ignore */ }

  } catch (e) {
    console.error(e);
    alert('Error al desactivar el tiquete.');
  }
}


async function activateTicket(id) {
  try {
    const res = await fetch(`/api/tiquete/${id}/activate`, {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
    });
    if (!res.ok) {
      const txt = await res.text();
      let json = null;
      try { json = JSON.parse(txt); } catch(e){ json = null; }
      alert(json?.message || 'No se pudo reactivar el tiquete.');
      return;
    }
    await loadAndRender();
  } catch (e) {
    console.error(e);
    alert('Error al reactivar.');
  }
}



  searchInput && searchInput.addEventListener('input', () => renderList(ticketsCache));

  (async function init(){
    await loadAndRender();
  })();

    // al final del DOMContentLoaded—exponer loadAndRender para que otros scripts puedan llamarlo
  window.loadAndRender = loadAndRender;

});


