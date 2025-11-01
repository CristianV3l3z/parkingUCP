// public/js/dashboard_vehiculos.js
// Versión corregida: evita dobles listeners, confirma una sola vez, no muestra alerts extra al crear
document.addEventListener('DOMContentLoaded', function () {

  // Evitar doble inicialización si el script se carga más de una vez por error
  if (window.__VEHICULOS_INIT_DONE) return;
  window.__VEHICULOS_INIT_DONE = true;

  // Elements
  const modal = document.getElementById('vehicleModal');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const openFormBtn = document.getElementById('openFormBtn');
  const cancelBtn = document.getElementById('cancelBtn');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const vehicleForm = document.getElementById('vehicleForm');
  const vehiclesList = document.getElementById('vehiclesList');
  const toast = document.getElementById('toast');
  const totalCount = document.getElementById('totalCount');
  const countCar = document.getElementById('countCar');
  const countMoto = document.getElementById('countMoto');
  const searchInput = document.getElementById('searchInput');
  const tarifaSelect = document.getElementById('id_tarifa');
  const tarifaValueSpan = document.getElementById('tarifaValue');
  const submitBtn = document.getElementById('submitBtn');

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  let tarifasCache = [];
  let vehiclesCache = [];
  let editingId = null;

  // Helpers
  function esc(s){ if(!s && s !== 0) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])); }
  function showToast(msg='¡Listo!'){ if(!toast) { console.log('toast:', msg); return; } toast.textContent = msg; toast.style.display='block'; setTimeout(()=> toast.style.display='none',2000); }
  function showModal(edit=false){ if(!modal) return; modal.setAttribute('aria-hidden','false'); modal.style.display='flex'; submitBtn && (submitBtn.textContent = edit ? 'Actualizar vehículo' : 'Agregar vehículo'); (document.getElementById('placa') || {}).focus && document.getElementById('placa').focus(); }
  function hideModal(){ if(!modal) return; modal.setAttribute('aria-hidden','true'); modal.style.display='none'; vehicleForm.reset(); editingId = null; tarifaValueSpan && (tarifaValueSpan.textContent = '-'); submitBtn && (submitBtn.textContent = 'Agregar vehículo'); }

  // Guard: evitar múltiples clicks rápidos en botones (deshabilita temporalmente)
  function disableTemporarily(el, ms = 1200){
    if(!el) return;
    el.disabled = true;
    setTimeout(()=> el.disabled = false, ms);
  }

  // Fetch tarifas
  async function fetchTarifas(){
    try{
      const res = await fetch('/api/tarifa');
      if(!res.ok) throw new Error('No se pudo obtener tarifas');
      tarifasCache = await res.json();
      if(!tarifaSelect) return;
      tarifaSelect.innerHTML = '<option value=\"\">-- Seleccione tarifa --</option>';
      tarifasCache.forEach(t => {
        const label = t.descripcion ?? t.tipo_vehiculo ?? (`Tarifa #${t.id_tarifa}`);
        const opt = document.createElement('option');
        opt.value = t.id_tarifa;
        opt.textContent = label + (t.valor ? ` — ${t.valor}` : '');
        tarifaSelect.appendChild(opt);
      });
    }catch(err){ console.error(err); tarifasCache = []; if(tarifaSelect) tarifaSelect.innerHTML = '<option value=\"\">No hay tarifas</option>'; }
  }

  function findTarifaIdForTipo(tipo){
    const found = tarifasCache.find(t => (t.tipo_vehiculo || '').toLowerCase() === (tipo || '').toLowerCase());
    if(found) return found.id_tarifa;
    if((tipo||'').toLowerCase() === 'carro') return 1;
    if((tipo||'').toLowerCase() === 'moto') return 2;
    return '';
  }

  function updateTarifaValueById(id){
    const t = tarifasCache.find(x => String(x.id_tarifa) === String(id));
    tarifaValueSpan && (tarifaValueSpan.textContent = t ? (t.valor ?? t.descripcion ?? `Tarifa #${id}`) : '-');
  }

  // Fetch vehiculos
  async function fetchVehiculos(){
    try{
      const res = await fetch('/api/vehiculo');
      if(!res.ok) throw new Error('Error al obtener vehículos');
      const data = await res.json();
      vehiclesCache = Array.isArray(data) ? data : [];
      return vehiclesCache;
    }catch(err){ console.error(err); vehiclesCache = []; return []; }
  }

// dentro de tu archivo dashboard_vehiculos.js reemplaza la función renderList por esta:

function renderList(vehiculos){
  // stats
  totalCount && (totalCount.textContent = vehiculos.length);
  countCar && (countCar.textContent = vehiculos.filter(v => (v.tipo_vehiculo||'').toLowerCase() === 'carro').length);
  countMoto && (countMoto.textContent = vehiculos.filter(v => (v.tipo_vehiculo||'').toLowerCase() === 'moto').length);

  const q = (searchInput?.value || '').trim().toLowerCase();
  const filtered = vehiculos.filter(v => !q || (v.placa || '').toLowerCase().includes(q) || (v.codigo_uuid||'').toLowerCase().includes(q) );

  if(!vehiclesList) return;
  vehiclesList.innerHTML = '';
  if(filtered.length === 0){
    vehiclesList.innerHTML = '<div style="padding:18px;background:var(--card);border-radius:10px;color:var(--muted)">No hay vehículos.</div>';
    return;
  }

  filtered.forEach(v => {
    const placa = (v.placa || '').toUpperCase();
    const tipo = (v.tipo_vehiculo || '').toLowerCase();
    const tipoLabel = tipo === 'carro' ? 'Carro' : (tipo === 'moto' ? 'Moto' : v.tipo_vehiculo || '');
    // preferimos usar created_local devuelto por el backend (evita desfase)
    const fecha = v.created_local || (v.created_at ? new Date(v.created_at).toLocaleString() : '');
    const marcaHtml = v.marca ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">Marca: ${esc(v.marca)}</div>` : '';
    const descHtml = v.descripcion ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">${esc(v.descripcion)}</div>` : '';
    const tarifaHtml = v.valor_tarifa ? `<div style="font-size:13px;color:var(--muted);margin-top:6px">Valor tarifa: $${esc(v.valor_tarifa)}</div>` : '';

    // calcular nombre del vigilante: múltiples fuentes posibles
    const vigilanteName = (v.vigilante_nombre && String(v.vigilante_nombre).trim())
                          || (v.vigilante && (v.vigilante.nombre_completo || [v.vigilante.nombre, v.vigilante.apellido].filter(Boolean).join(' ').trim() || v.vigilante.nombre))
                          || (v.vigilante && v.vigilante.nombre)
                          || (v.vigilante_nombre === null ? null : undefined);

    // debug: si no encontramos vigilante, mostrar advertencia con objeto completo
    if (!vigilanteName) {
      console.debug('[dashboard_vehiculos] vehiculo sin vigilante:', v);
    }

    const card = document.createElement('div');
  card.className = 'vehicle-card';
  // add data attributes to help other tabs locate the card when a tiquete is deleted
  card.setAttribute('data-vehiculo-id', String(v.id_vehiculo || v.id || ''));
  card.setAttribute('data-placa', String((v.placa || '').toLowerCase()));
  card.style.cssText = 'display:flex;align-items:flex-start;gap:12px;padding:12px;background:var(--card);border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.04);';

    // NOTE: Eliminé botón de "Eliminar" (según solicitaste). Dejé "Editar" (opcional).
    card.innerHTML = `
      <div style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.02);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px">
        ${esc(placa.slice(0,2))}
      </div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <div style="font-weight:800">${esc(placa)}</div>
          <div style="font-size:12px">
            <span style="padding:6px 8px;border-radius:999px;background:${tipo==='carro' ? 'rgba(255,204,0,0.12)' : 'rgba(80,170,255,0.08)'};color:${tipo==='carro' ? 'var(--accent)' : '#52a8ff'};font-weight:700">${esc(tipoLabel)}</span>
          </div>
        </div>

  <div style="font-size:13px;color:var(--muted);margin-top:6px">Añadido: ${esc(fecha)}</div>
  ${marcaHtml}
  ${descHtml}
  ${tarifaHtml}
  <div style="font-size:13px;color:var(--muted);margin-top:6px">Vigilante: ${esc(v.vigilante_nombre ?? (v.vigilante?.nombre ?? '—'))}</div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px;margin-left:12px">
        <button class="btn btn-ghost btn-edit" data-id="${esc(v.id_vehiculo ?? v.id ?? '')}" style="padding:8px 10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);background:transparent;cursor:pointer">✏️ Editar</button>
      </div>
    `;

    vehiclesList.appendChild(card);
  });
}


  // Load and render
  async function loadAndRender(){
    await fetchTarifas();
    const list = await fetchVehiculos();
    renderList(list);
  }

  // Create / Update
  vehicleForm && vehicleForm.addEventListener('submit', async function(e){

    // al inicio del submit handler (antes de preparar payload)
if (submitBtn.disabled) return;    // evita reenvío
submitBtn.disabled = true;
try {
  // ... el resto de la lógica ...
} finally {
  submitBtn.disabled = false;
}

    e.preventDefault();
    disableTemporarily(submitBtn, 1200);

    const placa = (document.getElementById('placa')?.value || '').trim();
    const tipo_vehiculo = (document.getElementById('tipo_vehiculo')?.value || '').trim();
    const marca = (document.getElementById('marca')?.value || '').trim() || null;
    const id_tarifa = (document.getElementById('id_tarifa')?.value || '').trim() || null;
    const descripcion = (document.getElementById('descripcion')?.value || '').trim() || null;

    if(!placa || placa.length < 3){ showToast('Placa inválida'); return; }
    if(!tipo_vehiculo){ showToast('Selecciona tipo de vehículo'); return; }

    const payload = { placa: placa.toUpperCase(), tipo_vehiculo, marca, descripcion };
    if (id_tarifa) payload.id_tarifa = id_tarifa;
    if (window.__CURRENT_USER_ID) payload.id_usuario = window.__CURRENT_USER_ID;

    try{
      const method = editingId ? 'PUT' : 'POST';
      const url = editingId ? `/api/vehiculo/${editingId}` : '/api/vehiculo';
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrf },
        body: JSON.stringify(payload)
      });

      const text = await res.text();
      let json = null; try{ json = text ? JSON.parse(text) : null; }catch(e){ json = null; }

      if(res.status === 422){
        // Validación: mostrar toast + console
        const errores = json?.errors ? Object.values(json.errors).flat().join(' — ') : (json?.message || 'Error de validación');
        showToast(errores);
        console.error('422 validation', json, text);
        return;
      }
      if(!res.ok){
        // Mostrar mensaje recibido del servidor si lo hay pero sin alert modal
        const msg = (json && (json.message || json.error)) ? (json.message || json.error) : ('Error: ' + res.status);
        showToast(msg);
        console.error('Error response', res.status, json, text);
        return;
      }

      // OK: mostrar toast simple, cerrar modal y recargar lista
      showToast(editingId ? 'Vehículo actualizado' : 'Vehículo agregado');
      hideModal();
      await loadAndRender();

    }catch(err){
      console.error('Fetch error', err);
      showToast('No se pudo guardar el vehículo (ver consola)');
    }
  });

// handler único (delegado)
vehiclesList && vehiclesList.addEventListener('click', async function(e){
  const btn = e.target.closest('button');
  if(!btn) return;
  const id = btn.getAttribute('data-id');
  if(!id) return;

  if(btn.classList.contains('btn-delete')){    // si en algún lugar existe
    if(!confirm('¿Eliminar este vehículo?')) return;
    try{
      const res = await fetch(`/api/vehiculo/${id}`, { method:'DELETE', headers: {'Accept':'application/json','X-CSRF-TOKEN': csrf } });
      if(res.status === 409){
        const json = await res.json().catch(()=>null);
        alert(json?.message || 'No se puede eliminar este vehículo porque tiene tiquetes relacionados.');
        return;
      }
      if(!res.ok){ const js = await res.json().catch(()=>null); alert(js?.message || 'No se pudo eliminar'); return; }
      showToast('Vehículo eliminado');
      await loadAndRender();
    }catch(err){ console.error(err); alert('Error al eliminar'); }
    return;
  }

    // EDITAR: cargar registro y abrir modal
    if(btn.classList.contains('btn-edit')){
      try{
        const res = await fetch(`/api/vehiculo/${id}`);
        if(!res.ok) throw new Error('No se pudo obtener el registro');
        const rec = await res.json();
        document.getElementById('placa') && (document.getElementById('placa').value = rec.placa ?? '');
        const tipoEl = document.getElementById('tipo_vehiculo') || document.getElementById('tipo');
        if(tipoEl) tipoEl.value = rec.tipo_vehiculo ?? '';
        document.getElementById('marca') && (document.getElementById('marca').value = rec.marca ?? '');
        document.getElementById('descripcion') && (document.getElementById('descripcion').value = rec.descripcion ?? '');
        if(tarifaSelect) { tarifaSelect.value = rec.id_tarifa ?? ''; updateTarifaValueById(rec.id_tarifa ?? ''); }
        editingId = id;
        showModal(true);
      }catch(err){
        console.error(err);
        showToast('No se pudo cargar el vehículo para editar');
      }
      return;
    }
  });

  // auto-assign tarifa on tipo change
  const tipoSelect = document.getElementById('tipo_vehiculo') || document.getElementById('tipo');
  if(tipoSelect){
    tipoSelect.addEventListener('change', function(e){
      const tipo = e.target.value;
      const tarifaId = findTarifaIdForTipo(tipo);
      if(tarifaId){
        if(tarifaSelect) tarifaSelect.value = tarifaId;
        updateTarifaValueById(tarifaId);
      } else {
        if(tarifaSelect) tarifaSelect.value = '';
        tarifaValueSpan && (tarifaValueSpan.textContent = '-');
      }
    });
  }

  // UI bindings
  openFormBtn && openFormBtn.addEventListener('click', function(){ editingId = null; vehicleForm.reset(); showModal(false); });
  cancelBtn && cancelBtn.addEventListener('click', hideModal);
  modalCloseBtn && modalCloseBtn.addEventListener('click', hideModal);
  modalBackdrop && modalBackdrop.addEventListener('click', hideModal);
  searchInput && searchInput.addEventListener('input', function(){ renderList(vehiclesCache); });

  // Init
  (async function init(){
    await fetchTarifas();
    await loadAndRender();
  })();


  // ESCUCHAR eventos publicados por otras pestañas (delete de tiquete/vehiculo)
window.addEventListener('storage', function(e) {
  if (!e.key) return;
  if (e.key !== 'app_event') return;

  let payload = null;
  try { payload = JSON.parse(e.newValue); } catch(err) { payload = null; }
  if (!payload) return;

  // Solo nos interesan eventos de tiquete eliminado
  if (payload.type !== 'tiquete_deleted') return;

  // Si el backend borró también el vehículo, eliminar la tarjeta por id o placa
  if (payload.vehiculo_deleted && payload.id_vehiculo) {
    // intentar eliminar card por data-id (id_vehiculo)
    const card = document.querySelector(`[data-vehiculo-id="${payload.id_vehiculo}"]`);
    if (card) {
      card.remove();
      // actualizar contadores: si tienes una función que recarga la lista completa:
      if (typeof loadAndRender === 'function') {
        // opcional: recargar todo para recalcular contadores
        loadAndRender();
      }
      return;
    }
  }

  // fallback: si no hay id, intentar por placa
  if (payload.vehiculo_deleted && payload.placa) {
    const placa = (payload.placa || '').toLowerCase();
    // supongamos que cada tarjeta tiene atributo data-placa
    const card = document.querySelector(`[data-placa="${placa}"], [data-placa="${placa.toUpperCase()}"]`);
    if (card) {
      card.remove();
      if (typeof loadAndRender === 'function') loadAndRender();
      return;
    }
  }

  // Si no borramos por DOM, recargamos la lista (opcional)
  if (payload.vehiculo_deleted && typeof loadAndRender === 'function') {
    loadAndRender();
  }
});



});
