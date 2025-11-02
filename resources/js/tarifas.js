// public/js/tarifas.js
document.addEventListener('DOMContentLoaded', function(){
  const csrf = window.__CSRF || '';
  const listEl = document.getElementById('tarifasList');
  const btnNew = document.getElementById('btnNew');
  const modal = document.getElementById('modalTarifa');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const cancelTarifa = document.getElementById('cancelTarifa');
  const form = document.getElementById('formTarifa');
  const submitBtn = document.getElementById('submitTarifa');
  const searchInput = document.getElementById('searchTarifa');

  let tarifas = [];
  let editingId = null;

  function esc(s){ if(!s && s !== 0) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])); }
  function showToast(msg){ const t = document.getElementById('toast'); t.textContent = msg; t.style.display='block'; setTimeout(()=> t.style.display='none',2000); }

  function openModal(edit=false){
    modal.style.display='flex';
    modal.setAttribute('aria-hidden','false');
    submitBtn.textContent = edit ? 'Actualizar' : 'Crear';
  }
  function closeModal(){
    modal.style.display='none';
    modal.setAttribute('aria-hidden','true');
    form.reset();
    editingId = null;
  }

  async function fetchTarifas(){
    try{
      const res = await fetch('/api/tarifa');
      if(!res.ok) throw new Error('Error al obtener tarifas');
      tarifas = await res.json();
      renderList();
    }catch(e){
      console.error(e);
      tarifas = [];
      listEl.innerHTML = '<div style="padding:12px;background:var(--card);border-radius:10px;color:var(--muted)">No se pudieron cargar tarifas.</div>';
    }
  }

  function renderList(){
    const q = (searchInput?.value || '').trim().toLowerCase();
    const filtered = tarifas.filter(t => !q || (t.tipo_vehiculo||'').toLowerCase().includes(q) || (t.descripcion||'').toLowerCase().includes(q));
    if(!filtered.length){
      listEl.innerHTML = '<div style="padding:12px;background:var(--card);border-radius:10px;color:var(--muted)">No hay tarifas.</div>';
      return;
    }
    listEl.innerHTML = '';
    filtered.forEach(t => {
      const card = document.createElement('div');
      card.style.cssText = 'background:var(--card);padding:14px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.04);display:flex;justify-content:space-between;align-items:flex-start;gap:12px';
      card.innerHTML = `
        <div style="min-width:0">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="font-weight:800">${esc((t.tipo_vehiculo||'').toUpperCase())}</div>
            <div style="font-size:14px;color:var(--muted)">${esc(t.descripcion || '')}</div>
          </div>
          <div style="margin-top:8px;color:var(--muted)">Valor: $${(Number(t.valor)||0).toFixed(2)}</div>
          <div style="margin-top:8px;color:var(--muted)">Estado: ${t.activo ? '<strong style=\"color:var(--accent)\">Activo</strong>' : '<span style=\"color:#999\">Inactivo</span>'}</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
          <div style="display:flex;gap:8px">
            <button class="btn btn-ghost btn-edit" data-id="${t.id_tarifa}">✏️ Editar</button>
            <button class="btn btn-ghost btn-toggle" data-id="${t.id_tarifa}">${t.activo ? 'Desactivar' : 'Activar'}</button>
            <button class="btn btn-ghost btn-delete" data-id="${t.id_tarifa}">🗑️ Eliminar</button>
          </div>
        </div>
      `;
      listEl.appendChild(card);
    });
  }

  // Create / Update
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    const tipo = form.tipo_vehiculo.value;
    const valor = parseFloat(form.valor.value || 0);
    const descripcion = form.descripcion.value || null;
    const activo = !!form.activo.checked;

    if(!tipo) { alert('Selecciona tipo'); return; }
    if(isNaN(valor) || valor < 0){ alert('Valor inválido'); return; }

    const payload = { tipo_vehiculo: tipo, valor, descripcion, activo };

    try {
      let res;
      if(editingId){
        res = await fetch(`/api/tarifa/${editingId}`, {
          method: 'PUT',
          headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload)
        });
      } else {
        res = await fetch('/api/tarifa', {
          method: 'POST',
          headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrf },
          body: JSON.stringify(payload)
        });
      }

      const text = await res.text();
      let json = null;
      try{ json = text ? JSON.parse(text) : null; }catch(e){ json = null; }

      if(!res.ok){
        alert(json?.message || 'Error guardando');
        return;
      }
      showToast(editingId ? 'Tarifa actualizada' : 'Tarifa creada');
      closeModal();
      await fetchTarifas();
    } catch(err){ console.error(err); alert('Error en la petición'); }
  });

  // Delegated click
  listEl.addEventListener('click', async function(e){
    const btn = e.target.closest('button');
    if(!btn) return;
    const id = btn.getAttribute('data-id');
    if(!id) return;

    // Edit
    if(btn.classList.contains('btn-edit')){
      const rec = tarifas.find(x => String(x.id_tarifa) === String(id));
      if(!rec) return alert('Tarifa no encontrada');
      editingId = id;
      form.tipo_vehiculo.value = rec.tipo_vehiculo || '';
      form.valor.value = rec.valor || 0;
      form.descripcion.value = rec.descripcion || '';
      form.activo.checked = !!rec.activo;
      openModal(true);
      return;
    }

    // Toggle active
    if(btn.classList.contains('btn-toggle')){
      const rec = tarifas.find(x => String(x.id_tarifa) === String(id));
      if(!rec) return alert('Tarifa no encontrada');
      const nowActivo = !rec.activo;
      try {
        const res = await fetch(`/api/tarifa/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrf },
          body: JSON.stringify({ activo: nowActivo })
        });
        const txt = await res.text(); let json = null; try{ json = txt ? JSON.parse(txt) : null }catch(e){ json = null; }
        if(!res.ok) { alert(json?.message || 'No se pudo actualizar'); return; }
        showToast(nowActivo ? 'Activada' : 'Desactivada');
        await fetchTarifas();
      } catch(e){ console.error(e); alert('Error'); }
      return;
    }

    // Delete (si quieres mantener log, aquí lo eliminamos físicamente; si prefieres "desactivar" en vez de borrar, lo cambiamos)
    if(btn.classList.contains('btn-delete')){
      if(!confirm('Eliminar esta tarifa?')) return;
      try {
        const res = await fetch(`/api/tarifa/${id}`, {
          method: 'DELETE',
          headers: { 'Accept':'application/json','X-CSRF-TOKEN': csrf }
        });
        const txt = await res.text(); let json = null; try{ json = txt ? JSON.parse(txt) : null }catch(e){ json = null; }
        if(!res.ok){ alert(json?.message || 'No se pudo eliminar'); return; }
        showToast('Tarifa eliminada');
        await fetchTarifas();
      } catch(e){ console.error(e); alert('Error al eliminar'); }
      return;
    }
  });

  // UI bindings
  btnNew && btnNew.addEventListener('click', function(){
    editingId = null; form.reset(); openModal(false);
  });
  modalCloseBtn && modalCloseBtn.addEventListener('click', closeModal);
  cancelTarifa && cancelTarifa.addEventListener('click', closeModal);
  modalBackdrop && modalBackdrop.addEventListener('click', closeModal);
  searchInput && searchInput.addEventListener('input', renderList);

  // Init
  fetchTarifas();
});
