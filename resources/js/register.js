// resources/js/register.js
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  if (!form) return;

  const btn = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    btn.disabled = true;
    btn.textContent = 'Registrando...';

    const fd = new FormData(form);

    try {
      // 🚀 Forzar uso de HTTPS o dominio actual
      const targetUrl = new URL(form.getAttribute('action'), window.location.origin).href;

      const response = await fetch(targetUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        },
        body: fd,
      });

      if (!response.ok) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
          const data = await response.json();
          if (data.errors) {
            alert(Object.values(data.errors).flat().join('\n'));
          } else if (data.message) {
            alert(data.message);
          } else {
            alert('Error al registrar. Intenta de nuevo.');
          }
        } else {
          window.location.reload();
        }
        btn.disabled = false;
        btn.textContent = 'Register';
        return;
      }

      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const data = await response.json();
        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        alert('Registro exitoso');
        window.location.href = '/dashboard';
      } else {
        window.location.href = '/dashboard';
      }
    } catch (err) {
      console.error(err);
      alert('Error de conexión. Intenta de nuevo.');
      btn.disabled = false;
      btn.textContent = 'Register';
    }
  });
});
