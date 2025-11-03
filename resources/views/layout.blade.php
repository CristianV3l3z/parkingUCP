<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Parking</title>

  {{-- css principal --}}
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

  

  {{-- estilos extras (puedes moverlos a styles.css) --}}
  <style>
    /* Variables basicas (ajusta en styles.css si quieres) */
    :root {
      --bg: #ffffff;
      --card: #fff;
      --muted: #7a7a7a;
      --accent: #ffb300;
      --accent-2: #52a8ff;
      --shadow: 0 8px 30px rgba(0,0,0,0.05);
    }
    .site-header { background: var(--card); border-bottom: 1px solid rgba(0,0,0,0.04); position: sticky; top:0; z-index:20; }
    .header-inner { display:flex; align-items:center; gap:16px; padding:12px 20px; }
    .logo { display:flex; align-items:center; gap:10px; font-weight:800 }
    .logo-icon { font-size:20px; display:inline-block; }
    .profile-area { margin-left:auto; display:flex; gap:12px; align-items:center; position:relative; }
    .icon-btn { background:transparent; border:none; padding:8px; border-radius:8px; cursor:pointer; position:relative; }
    .icon-badge { position:absolute; top:6px; right:6px; background:#ff3b30; color:#fff; font-size:11px; padding:2px 6px; border-radius:999px; }
    .avatar { width:36px; height:36px; border-radius:999px; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.06); cursor:pointer; font-weight:700 }
    
    /* ----- AQUÍ ESTÁN LOS CAMBIOS ----- */
    /* Usamos el ID #profileDropdown para evitar el conflicto con Bootstrap */
    
    #profileDropdown { 
      position:absolute; 
      right:0; 
      top:48px; 
      width:220px; 
      background:var(--card); 
      border-radius:10px; 
      box-shadow:var(--shadow); 
      padding:8px; 
      display:none; 
    }
    
    #profileDropdown.show { 
      display:block; 
    }
    
    #profileDropdown a, #profileDropdown button { 
      display:block; 
      width:100%; 
      text-align:left; 
      padding:8px 10px; 
      border-radius:8px; 
      background:transparent; 
      border:none; 
      cursor:pointer; 
      color:#111;
    }
    
    #profileDropdown a:hover, #profileDropdown button:hover { 
      background:rgba(0,0,0,0.03); 
    }
    
    /* ----- FIN DE LOS CAMBIOS ----- */

    .notif-list { max-height:300px; overflow:auto; }
    
    /* boton en fila de vehiculo */
    .btn-action { background: linear-gradient(90deg,var(--accent), #ff9f00); color:#111; border-radius:8px; padding:8px 10px; border:none; cursor:pointer; font-weight:700; }
    .btn-ghost { background:transparent; border:1px solid rgba(0,0,0,0.06); padding:8px 12px; border-radius:8px; cursor:pointer; }
</style>
  <!-- Bootstrap CSS (CDN) - opcional si ya usas Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS (defer) al final de la página: lo añadiremos más abajo en este mismo blade -->

</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <div class="logo">
        <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
          <span class="logo-icon">🅿️</span>
          <span class="logo-text">Parking</span>
        </a>
      </div>

      {{-- Puedes dejar espacio para un buscador global si deseas más adelante --}}
      <div style="margin-left:12px;">
        {{-- espacio para buscar global (opcional) --}}
      </div>

      {{-- Perfil y notificaciones --}}
      <div class="profile-area" id="profileArea">
        {{-- icono notificaciones --}}
        <button class="icon-btn" id="btnNotifications" aria-label="Notificaciones">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path d="M12 22c1.1046 0 2-.8954 2-2H10c0 1.1046.8954 2 2 2z" fill="#111"/>
            <path d="M18 16v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 10-3 0v.68C7.63 5.36 6 7.92 6 11v5l-1.5 1.5v.5h15v-.5L18 16z" fill="#111" opacity="0.8"/>
          </svg>
          <span class="icon-badge" id="notifBadge" style="display:none">0</span>
        </button>

        {{-- avatar / dropdown --}}
        <div style="position:relative">
          <div class="avatar" id="avatarBtn" tabindex="0">
            {{ strtoupper(substr(auth()->user()->nombre ?? (auth()->user()->email ?? 'U'), 0, 1)) }}
          </div>
@auth
          <div class="dropdown" id="profileDropdown" aria-hidden="true">
            <div style="padding:8px;border-bottom:1px solid rgba(0,0,0,0.04);">
              <div style="font-weight:800">{{ auth()->user()->nombre ?? auth()->user()->email }}</div>
              <div style="font-size:13px;color:var(--muted)">{{ auth()->user()->correo ?? auth()->user()->email }}</div>
            </div>

            <a href="{{ route('usuario.edit') }}" id="editProfileBtn">Editar perfil.</a>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button class="btn btn-primary" type="submit">Cerrar sesión</button>
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

  {{-- JS desde public/ --}}
  <script src="{{ asset('js/app.js') }}" defer></script>
  <script src="{{ asset('js/login.js')}}" defer></script>
  <script src="{{ asset('JS/register.js')}}" defer></script>
  <script src="{{ asset('js/dashboard_vehiculos.js') }}" defer></script>
  <script src="{{ asset('js/ui-utils.js') }}" defer></script>

  <!-- add in <head> o justo antes de cierre body, pero IMPORTANTE: cargar checkout_mp.js antes de los scripts que lo usan -->
<script src="{{ asset('js/checkout_mp.js') }}" defer></script>

<!-- luego tus otros scripts que pueden usar startCheckoutTiquete -->
<script src="{{ asset('js/dashboard_readonly.js') }}" defer></script>
<script src="{{ asset('js/datos.js') }}" defer></script>


  {{-- script pequeño para dropdowns y notifs (puedes moverlo a public/js/ui.js) --}}
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const avatar = document.getElementById('avatarBtn');
      const dropdown = document.getElementById('profileDropdown');
      const btnNotif = document.getElementById('btnNotifications');
      const notifBadge = document.getElementById('notifBadge');

      // Toggle dropdown
      function toggleDropdown(e){
        dropdown.classList.toggle('show');
        dropdown.setAttribute('aria-hidden', !dropdown.classList.contains('show'));
      }
      avatar && avatar.addEventListener('click', toggleDropdown);
      avatar && avatar.addEventListener('keydown', (e)=>{ if(e.key === 'Enter' || e.key === ' ') toggleDropdown(); });

      // close on outside click
      document.addEventListener('click', function(ev){
        if (!avatar.contains(ev.target) && !dropdown.contains(ev.target)) {
          dropdown.classList.remove('show');
          dropdown.setAttribute('aria-hidden', 'true');
        }
      });

      // notifications placeholder: mostrar/ocultar lista y badge (simulado)
      btnNotif && btnNotif.addEventListener('click', function(){
        // por ahora solo alternamos un badge simulado
        if (notifBadge.style.display === 'none' || notifBadge.style.display === '') {
          notifBadge.style.display = 'inline-block';
          notifBadge.textContent = '3'; // ejemplo
        } else {
          notifBadge.style.display = 'none';
        }
        // más adelante abrirás un panel con notificaciones reales
      });
    });



    
  </script>
</body>
</html>
