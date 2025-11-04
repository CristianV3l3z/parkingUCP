<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parking</title>

  {{-- css principal --}}
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

  {{-- estilos pequeños locales (mover a styles.css recomendado) --}}
  <style>
    :root{
      --bg: #ffffff;
      --card: #fff;
      --muted: #6f6f6f;
      --accent: #ffb300;
      --accent-2: #52a8ff;
      --shadow: 0 8px 30px rgba(0,0,0,0.05);
    }

    /* Header */
    .site-header{ background:var(--card); border-bottom:1px solid rgba(0,0,0,0.04); position:sticky; top:0; z-index:1030; }
    .header-inner{ display:flex; align-items:center; gap:16px; padding:10px 18px; max-width:1200px; margin:0 auto; }
    .logo{ display:flex; align-items:center; gap:10px; font-weight:800; }
    .logo-icon{ font-size:20px; }

    /* Profile area */
    .profile-area{ margin-left:auto; display:flex; gap:12px; align-items:center; position:relative; }
    .icon-btn{ background:transparent; border:0; padding:8px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
    .icon-badge{ position:absolute; top:6px; right:6px; background:#ff3b30; color:#fff; font-size:11px; padding:2px 6px; border-radius:999px; }
    .avatar{ width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.06); cursor:pointer; font-weight:700; }

    /* Dropdown usando ID para evitar colisiones con Bootstrap */
    #profileDropdown{ position:absolute; right:0; top:48px; width:220px; background:var(--card); border-radius:10px; box-shadow:var(--shadow); padding:6px; display:none; }
    /* Mostrar el dropdown si tiene la clase .show (controlado por JS) */
    #profileDropdown.show{ display:block; }

    /* Accesibilidad: permitir mostrar con focus-within cuando JS no está disponible */
    .profile-wrapper:focus-within #profileDropdown{ display:block; }

    #profileDropdown [role="menuitem"]{ display:block; width:100%; text-align:left; padding:8px 10px; border-radius:8px; background:transparent; border:0; cursor:pointer; color:inherit; text-decoration:none; }
    #profileDropdown [role="menuitem"]:hover, #profileDropdown [role="menuitem"]:focus{ background:rgba(0,0,0,0.03); outline:none; }

    /* small utility buttons */
    .btn-action{ background:linear-gradient(90deg,var(--accent), #ff9f00); color:#111; border-radius:8px; padding:8px 10px; border:none; cursor:pointer; font-weight:700; }
    .btn-ghost{ background:transparent; border:1px solid rgba(0,0,0,0.06); padding:8px 12px; border-radius:8px; cursor:pointer; }

    /* Responsive tweaks */
    @media (max-width:576px){
      .header-inner{ padding:8px 12px; }
      #profileDropdown{ right:6px; left:auto; top:46px; width:200px; }

    }

    :root{
  --bg: #ffffff;
  --card: #fff;
  --muted: #6f6f6f;
  --accent: #ffb300;
  --accent-2: #52a8ff;
  --shadow: 0 8px 30px rgba(0,0,0,0.05);

  /* color primario del texto */
  --text: #111111;
}

/* regla base — asegúrate que quede al final de tus estilos */
html, body {
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* mantener enlaces legibles (si usas color:inherit en HTML) */
a, a:link, a:visited, button, .logo-text, .avatar {
  color: inherit;
}

/* si algún componente sigue blanco, forzamos color localmente */
.site-header, .header-inner, .profile-area, #profileDropdown, main, .btn-ghost, .btn-action {
  color: var(--text);
}

  </style>

  <!-- Bootstrap CSS (CDN) - opcional si ya usas Bootstrap local -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <header class="site-header">
    <div class="header-inner">
      <div class="logo">
        <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
          <span class="logo-icon" aria-hidden>🅿️</span>
          <span class="logo-text">Parking</span>
        </a>
      </div>

      {{-- espacio para buscador si se desea --}}
      <div aria-hidden style="margin-left:12px;"></div>

      <div class="profile-area" id="profileArea">
        {{-- notificaciones --}}
        <button class="icon-btn" id="btnNotifications" aria-label="Mostrar notificaciones" aria-expanded="false" aria-controls="notificationsPanel">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path d="M12 22c1.1046 0 2-.8954 2-2H10c0 1.1046.8954 2 2 2z" fill="#111"/>
            <path d="M18 16v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 10-3 0v.68C7.63 5.36 6 7.92 6 11v5l-1.5 1.5v.5h15v-.5L18 16z" fill="#111" opacity="0.8"/>
          </svg>
          <span class="icon-badge" id="notifBadge" style="display:none">0</span>
        </button>

        {{-- avatar + dropdown --}}
        <div class="profile-wrapper" style="position:relative">
          <div class="avatar" id="avatarBtn" tabindex="0" aria-haspopup="true" aria-controls="profileDropdown" aria-expanded="false">
            {{ strtoupper(substr(auth()->user()->nombre ?? (auth()->user()->email ?? 'U'), 0, 1)) }}
          </div>

          @auth
          <div id="profileDropdown" role="menu" aria-hidden="true" aria-labelledby="avatarBtn">
            <div style="padding:8px;border-bottom:1px solid rgba(0,0,0,0.04);">
              <div style="font-weight:800">{{ auth()->user()->nombre ?? auth()->user()->email }}</div>
              <div style="font-size:13px;color:var(--muted)">{{ auth()->user()->correo ?? auth()->user()->email }}</div>
            </div>

            <a href="{{ route('usuario.edit') }}" role="menuitem" id="editProfileBtn">Editar perfil</a>

            <form action="{{ route('logout') }}" method="POST" style="display:flex;padding:8px; gap:8px; align-items:center;">
              @csrf
              <button class="btn btn-outline-secondary w-100" type="submit" role="menuitem">Cerrar sesión</button>
            </form>
          </div>
          @endauth
        </div>
      </div>
    </div>
  </header>

  <main id="main" class="main-scroll">
    @yield('content')
  </main>

  {{-- Scripts: carga ordenada --}}
  <!-- Bootstrap bundle (incluye Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  {{-- tus scripts (usa defer) --}}
  <script src="{{ asset('js/app.js') }}" defer></script>
  <script src="{{ asset('js/ui-utils.js') }}" defer></script>
  <script src="{{ asset('js/dashboard_readonly.js') }}" defer></script>

  <!-- Script pequeño para comportamiento accesible del dropdown y notificaciones -->
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const avatar = document.getElementById('avatarBtn');
      const dropdown = document.getElementById('profileDropdown');
      const btnNotif = document.getElementById('btnNotifications');
      const notifBadge = document.getElementById('notifBadge');

      // Helper para cerrar el dropdown
      function closeDropdown(){
        if(!dropdown) return;
        dropdown.classList.remove('show');
        dropdown.setAttribute('aria-hidden','true');
        avatar && avatar.setAttribute('aria-expanded','false');
      }

      function openDropdown(){
        if(!dropdown) return;
        dropdown.classList.add('show');
        dropdown.setAttribute('aria-hidden','false');
        avatar && avatar.setAttribute('aria-expanded','true');
        // mover foco al primer elemento del menu
        const first = dropdown.querySelector('[role="menuitem"]');
        first && first.focus();
      }

      // Toggle con click o teclado
      function toggleDropdown(e){
        if(!dropdown) return;
        if(dropdown.classList.contains('show')) closeDropdown();
        else openDropdown();
      }

      avatar && avatar.addEventListener('click', function(e){
        e.stopPropagation();
        toggleDropdown();
      });
      avatar && avatar.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar'){
          e.preventDefault();
          toggleDropdown();
        }
        if(e.key === 'ArrowDown'){
          e.preventDefault();
          openDropdown();
        }
      });

      // Cerrar con click fuera o Escape
      document.addEventListener('click', function(ev){
        if(!avatar || !dropdown) return;
        if (!avatar.contains(ev.target) && !dropdown.contains(ev.target)) closeDropdown();
      });
      document.addEventListener('keydown', function(ev){
        if(ev.key === 'Escape') closeDropdown();
      });

      // Notificaciones: ejemplo simple
      btnNotif && btnNotif.addEventListener('click', function(e){
        e.stopPropagation();
        const isHidden = !(notifBadge && notifBadge.style.display !== 'none');
        if(isHidden){
          notifBadge.style.display = 'inline-block';
          notifBadge.textContent = '3';
          btnNotif.setAttribute('aria-expanded','true');
        } else {
          notifBadge.style.display = 'none';
          btnNotif.setAttribute('aria-expanded','false');
        }
      });
    });
  </script>
</body>
</html>
