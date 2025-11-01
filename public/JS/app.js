document.addEventListener('DOMContentLoaded', function () {

  /* =====================
    1) Ajustar variable CSS --header-height dinámicamente
     ===================== */
  const root = document.documentElement;
  const header = document.querySelector('.site-header');

  function updateHeaderHeightVar() {
    const h = header ? header.offsetHeight : 80;
    root.style.setProperty('--header-height', h + 'px');
  }
  // Inicial y on resize
  updateHeaderHeightVar();
  window.addEventListener('resize', updateHeaderHeightVar);

  /* =====================
    2) Scroll suave con offset (toma en cuenta header)
     ===================== */
  function scrollToHash(hash) {
    if (!hash) return;
    const id = hash.replace('#','');
    const el = document.getElementById(id);
    if (!el) return;
    // Calcula top considerando header variable
    const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || (header ? header.offsetHeight : 80);
    const top = el.getBoundingClientRect().top + window.pageYOffset - headerHeight - 12;
    window.scrollTo({ top, behavior: 'smooth' });
  }

  // Interceptar clicks de enlaces que apuntan a hashes
  document.querySelectorAll('a[href*="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const href = a.getAttribute('href');
      if (!href.includes('#')) return;
      const parts = href.split('#');
      const hash = '#' + parts[1];
      // Si apunta a otra ruta con hash (/ #servicios), dejar que el navegador redirija,
      // pero si estamos en la misma página, prevenir y hacer scroll suave
      if (href.startsWith('#')) {
        e.preventDefault();
        scrollToHash(hash);
      } else {
        // Si apunta a /#id y estamos en otra ruta, permitir navegación normal;
        // cuando la página cargue, el siguiente bloque con window.location.hash hará scroll.
      }
    });
  });

  // Si la URL tiene hash al cargar (p.ej viniste desde /servicios que redirigió a /#servicios)
  if (window.location.hash) {
    // pequeño timeout para esperar paint y que se calcule la variable header
    setTimeout(()=> scrollToHash(window.location.hash), 70);
  }

  /* =====================
     3) Modal reserva / flash messages (tu código anterior)
     ===================== */
  const modal = document.getElementById('reservaModal');
  const reservarBtn = document.getElementById('reservarBtn');
  const heroReservar = document.getElementById('heroReservar');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalBackdrop = document.getElementById('modalClose');

  function openModal(){ if(modal) modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ if(modal) modal.setAttribute('aria-hidden','true'); }

  if (reservarBtn) reservarBtn.addEventListener('click', openModal);
  if (heroReservar) heroReservar.addEventListener('click', openModal);
  if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
  if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  // Auto hide flashes
  setTimeout(() => {
    document.querySelectorAll('.flash').forEach(f => {
      f.style.transition = 'opacity .35s';
      f.style.opacity = '0';
      setTimeout(()=> f.style.display = 'none', 400);
    });
  }, 5000);

});
