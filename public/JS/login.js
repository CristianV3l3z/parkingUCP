document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email) {
      e.preventDefault();
      alert('Por favor ingresa tu correo.');
      form.email.focus();
      return;
    }

    if (!validateEmail(email)) {
      e.preventDefault();
      alert('El correo no tiene un formato válido.');
      form.email.focus();
      return;
    }

    if (!password || password.length < 8) {
      e.preventDefault();
      alert('La contraseña debe tener al menos 8 caracteres.');
      form.password.focus();
      return;
    }

    // Permitir el envío al backend Laravel; aquí puedes mostrar un loader si quieres.
  });
});

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
