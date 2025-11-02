// public/js/ui-utils.js
document.addEventListener('DOMContentLoaded', function(){
  // 1) actualizar --header-height (para scroll-snap)
  function setHeaderHeight(){
    const header = document.querySelector('.site-header');
    if(!header) return;
    const h = header.getBoundingClientRect().height;
    document.documentElement.style.setProperty('--header-height', `${Math.round(h)}px`);
  }
  setHeaderHeight();
  window.addEventListener('resize', setHeaderHeight);

  // 2) small helper to show skeleton during fetch operations
  window.showSkeleton = function(containerSelector, rows = 3){
    const c = document.querySelector(containerSelector);
    if(!c) return;
    c.innerHTML = '';
    for(let i=0;i<rows;i++){
      const s = document.createElement('div');
      s.className = 'skeleton';
      s.style.cssText = 'height:64px;border-radius:12px;margin-bottom:12px';
      c.appendChild(s);
    }
  };

  // 3) simple tooltip initializer for elements with data-tip (optional)
  //    CSS hover tooltip covers simple cases; here we support focus keyboard show/hide
  document.querySelectorAll('[data-tip]').forEach(el=>{
    el.addEventListener('focus', ()=>el.classList.add('tooltip-focus'));
    el.addEventListener('blur', ()=>el.classList.remove('tooltip-focus'));
  });

  // 4) simple badge update helper (simulate server-side notification count)
  window.setNotifCount = function(n){
    const b = document.getElementById('notifBadge');
    if(!b) return;
    if(!n || Number(n) <= 0){ b.style.display = 'none'; }
    else { b.style.display = 'inline-block'; b.textContent = String(n); }
  };

  // Expose small utilities for future use
  window.uiUtils = { setHeaderHeight, showSkeleton, setNotifCount };

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
    // Este botón abre directamente la URL de MP en nueva pestaña (si ya existe preferencia en backend)
    const id = this.dataset.tiquete;
    const monto = Number(this.dataset.monto || 0);
    try {
      // crear preferencia pero no esperar el polling (simple open)
      const res = await fetch('/api/checkout/create', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
        body: JSON.stringify({ id_tiquete: id, title: `Pago Tiquete #${id}`, amount: monto, currency: 'COP' })
      });
      const j = await res.json();
      if (j.init_point) window.open(j.init_point, '_blank');
      else alert('No se pudo abrir Mercado Pago (init_point faltante).');
    } catch (e) {
      console.error(e);
      alert('Error abriendo Mercado Pago: ' + e.message);
    }
  });

  // cerrar si clic fuera del panel (opcional)
  modal && modal.addEventListener('click', function(e){
    if (e.target === modal) closeModal();
  });
})();


});
