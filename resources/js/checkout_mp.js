// public/js/checkout_mp.js
// Utilities: startCheckoutTiquete and pollStatus
async function startCheckoutTiquete(idTiquete, title, amount, currency = 'COP') {
  try {
    // crear preferencia en backend
    const payload = { id_tiquete: idTiquete, title, amount, currency };
    const res = await fetch('/api/checkout/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(payload)
    });
    if (!res.ok) {
      const txt = await res.text();
      throw new Error('Error creando preferencia: ' + txt);
    }
    const json = await res.json();

    // abrir checkout en nueva ventana (preferir init_point, fallback sandbox_init_point)
    const initPoint = json.init_point || json.sandbox_init_point || null;
    const prefId = json.preference_id || json.id || null;
    if (!initPoint) throw new Error('init_point no disponible');

    // abrir en nueva ventana; si popup bloqueado, redirigir en la misma pestaña
    let win = null;
    try {
      win = window.open(initPoint, '_blank', 'width=1024,height=700,noopener');
      if (!win) {
        // popup bloqueado -> navegar
        window.location.href = initPoint;
      }
    } catch (e) {
      window.location.href = initPoint;
    }

    // ahora hacemos polling local para comprobar estado de pago en tabla "pago"
    const start = Date.now();
    const timeoutMs = 2 * 60 * 1000; // 2 minutos
    const intervalMs = 2500;

    return new Promise((resolve, reject) => {
      const interval = setInterval(async () => {
        try {
          const stRes = await fetch(`/api/checkout/status/${idTiquete}`, { headers: { 'Accept':'application/json' }});
          if (stRes.ok) {
            const body = await stRes.json();
            const pago = body.pago;
            if (pago && pago.estado_pago) {
              const st = String(pago.estado_pago).toLowerCase();
              if (st.includes('aprobado') || st.includes('approved')) {
                clearInterval(interval);
                resolve({ success: true, pago });
                if (win && !win.closed) {
                  try { win.close(); } catch(e) {}
                }
                return;
              }
              // si está rechazado -> resolver con fallo
              if (st.includes('rechazado') || st.includes('rejected')) {
                clearInterval(interval);
                resolve({ success: false, pago });
                if (win && !win.closed) {
                  try { win.close(); } catch(e) {}
                }
                return;
              }
            }
          }

          if (Date.now() - start > timeoutMs) {
            clearInterval(interval);
            reject(new Error('Timeout esperando confirmación de pago'));
          }
        } catch (e) {
          clearInterval(interval);
          reject(e);
        }
      }, intervalMs);
    });

  } catch (e) {
    console.error('startCheckoutTiquete error', e);
    throw e;
  }
}

// Exponer globalmente si quieres
window.startCheckoutTiquete = startCheckoutTiquete;
