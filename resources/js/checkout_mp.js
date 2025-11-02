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

    // abrir checkout en nueva ventana (init_point)
    const initPoint = json.init_point;
    const prefId = json.preference_id;
    if (!initPoint) throw new Error('init_point no disponible');

    const win = window.open(initPoint, '_blank', 'width=1024,height=700');

    // ahora hacemos polling local para comprobar estado de pago en tabla "pago"
    // se puede hacer polling cada 2.5s hasta timeout (por ejemplo 2 minutos)
    const start = Date.now();
    const timeoutMs = 2 * 60 * 1000; // 2 minutos
    const intervalMs = 2500;

    return new Promise((resolve, reject) => {
      const interval = setInterval(async () => {
        try {
          const stRes = await fetch(`/api/checkout/status/${idTiquete}`, { headers: { 'Accept':'application/json' }});
          if (!stRes.ok) {
            // si no hay pagos aún, seguir
            // if 404 => no pagos, continuar
            // console.debug('checkout status not ready', await stRes.text());
          } else {
            const body = await stRes.json();
            const pago = body.pago;
            if (pago && pago.estado_pago && String(pago.estado_pago).toLowerCase().includes('approved')) {
              clearInterval(interval);
              resolve({ success: true, pago });
              if (win && !win.closed) {
                // opcional: cerrar la ventana de MP
                try { win.close(); } catch(e) {}
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
