// public/js/register.js
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  const btn = form.querySelector('button[type="submit"]');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Deshabilitar botón para evitar envíos múltiples
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    // Construir FormData
    const fd = new FormData(form);

    try {
      // Enviar con fetch (si prefieres submit normal, comentar este bloque y usar form.submit())
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: fd,
      });

      // Si el controlador devuelve JSON con errores
      if (!response.ok) {
        // Si es JSON con errores -> mostrar mensajes
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
          const data = await response.json();
          // Si vienen errores de validación (Laravel)
          if (data.errors) {
            alert(Object.values(data.errors).flat().join('\n'));
          } else if (data.message) {
            alert(data.message);
          } else {
            alert('Error al registrar. Intenta de nuevo.');
          }
        } else {
          // Fallback: recargar para que aparezcan errores en blade
          window.location.reload();
        }
        btn.disabled = false;
        btn.textContent = 'Register';
        return;
      }

      // Si ok: si devuelve JSON con redirect, seguirlo; si es redirect real, fetch seguirá pero no redirige
      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const data = await response.json();
        if (data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        // Si nos devuelven usuario:
        alert('Registro exitoso');
        window.location.href = '/dashboard';
      } else {
        // si el controller responde con un redirect tradicional, forzamos la navegación
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
