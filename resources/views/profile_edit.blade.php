{{-- resources/views/profile_edit.blade.php --}}
@extends('layout')

@section('content')
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<main style="padding:20px 20px 80px 20px; max-width:900px; margin:0 auto;">
  <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h2 style="margin:0">Editar perfil</h2>
    <a href="{{ url('/dashboard') }}" class="btn-ghost" style="height:36px;display:inline-flex;align-items:center;padding:0 12px;">← Volver</a>
  </header>

  <section style="margin-top:18px;background:var(--card);padding:18px;border-radius:12px;box-shadow:var(--shadow);">
    {{-- Mostrar mensajes de éxito/error --}}
    @if(session('success'))
      <div style="padding:10px;border-radius:8px;background:#ECFDF5;color:#065F46;margin-bottom:12px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div style="padding:10px;border-radius:8px;background:#FEF3F2;color:#7F1D1D;margin-bottom:12px;">
        <ul style="margin:0;padding-left:18px;">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="profileEditForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" novalidate>
      @csrf
      @method('PUT')

      <div style="display:grid;grid-template-columns:120px 1fr;gap:14px;align-items:start;">
        {{-- Avatar --}}
        <div style="display:flex;flex-direction:column;gap:8px;align-items:center;">
          <div id="avatarPreview" style="width:110px;height:110px;border-radius:12px;background:rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;overflow:hidden;">
            @if(old('avatar_preview') ?? ($user->avatar ?? false))
              <img src="{{ old('avatar_preview') ?? asset('storage/avatars/'.$user->avatar) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
            @else
              {{ strtoupper(substr($user->nombre ?? auth()->user()->name ?? 'U',0,1)) }}
            @endif
          </div>

          <label for="avatar" class="btn-ghost btn-sm" style="cursor:pointer;padding:6px 10px;border-radius:8px;">Cambiar avatar</label>
          <input id="avatar" name="avatar" type="file" accept="image/*" style="display:none" />
          <div class="muted small" style="text-align:center;">PNG/JPG — max 2MB</div>
        </div>

        {{-- Campos --}}
        <div>
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px;">
              <label for="nombre" style="font-weight:700;display:block">Nombre</label>
              <input id="nombre" name="nombre" value="{{ old('nombre', $user->nombre ?? auth()->user()->name ?? '') }}" required
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
            </div>

            <div style="flex:1;min-width:220px;">
              <label for="correo" style="font-weight:700;display:block">Correo</label>
              <input id="correo" name="correo" type="email" value="{{ old('correo', $user->correo ?? auth()->user()->email ?? '') }}" required
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
            </div>
          </div>

          <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px;">
              <label for="telefono" style="font-weight:700;display:block">Teléfono</label>
              <input id="telefono" name="telefono" value="{{ old('telefono', $user->telefono ?? '') }}"
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
            </div>

            <div style="flex:1;min-width:220px;">
              <label for="rol" style="font-weight:700;display:block">Rol</label>
              <input id="rol" name="rol" value="{{ $user->rol ?? 'Usuario' }}" disabled
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px;background:rgba(0,0,0,0.02)" />
            </div>
          </div>

          <hr style="margin:16px 0;border:none;border-top:1px solid rgba(0,0,0,0.06)">

          {{-- Cambio de contraseña (opcional) --}}
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label for="password" style="font-weight:700;display:block">Nueva contraseña</label>
              <input id="password" name="password" type="password" placeholder="(opcional)"
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
            </div>

            <div>
              <label for="password_confirmation" style="font-weight:700;display:block">Confirmar contraseña</label>
              <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la nueva contraseña"
                     style="width:100%;padding:10px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:6px" />
            </div>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
            <a href="{{ url('/dashboard') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" id="saveProfileBtn" class="btn-primary">Guardar cambios</button>
          </div>

          <div class="muted small" style="margin-top:10px;">*Los cambios de contraseña son opcionales. Si no rellenas password no se actualizará.</div>
        </div>
      </div>
    </form>
  </section>
</main>

{{-- JS: previsualizar avatar y validación simple --}}
<script>
  (function(){
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const form = document.getElementById('profileEditForm');
    const saveBtn = document.getElementById('saveProfileBtn');

    // abrir selector de archivo cuando se clickee la etiqueta (ya enlazada por for/id)
    avatarInput.addEventListener('change', e => {
      const file = e.target.files[0];
      if(!file) return;
      if(file.size > 2 * 1024 * 1024){ // 2MB
        alert('El archivo es demasiado grande. Máx 2MB.');
        avatarInput.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = function(ev){
        // reemplaza contenido con la imagen
        avatarPreview.innerHTML = '';
        const img = document.createElement('img');
        img.src = ev.target.result;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        avatarPreview.appendChild(img);
      }
      reader.readAsDataURL(file);
    });

    // Validación simple antes de enviar
    form.addEventListener('submit', function(ev){
      // prevent double submit
      saveBtn.disabled = true;
      saveBtn.textContent = 'Guardando...';
      // Validaciones sencillas (cliente)
      const nombre = document.getElementById('nombre').value.trim();
      const correo = document.getElementById('correo').value.trim();
      const password = document.getElementById('password').value;
      const passwordConf = document.getElementById('password_confirmation').value;

      if(nombre.length < 2){
        ev.preventDefault();
        alert('El nombre debe tener al menos 2 caracteres.');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Guardar cambios';
        return;
      }
      // email básico
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if(!emailRegex.test(correo)){
        ev.preventDefault();
        alert('Por favor ingresa un correo válido.');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Guardar cambios';
        return;
      }
      // password match
      if(password || passwordConf){
        if(password.length < 6){
          ev.preventDefault();
          alert('La contraseña debe tener al menos 6 caracteres.');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Guardar cambios';
          return;
        }
        if(password !== passwordConf){
          ev.preventDefault();
          alert('Las contraseñas no coinciden.');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Guardar cambios';
          return;
        }
      }
      // let the form submit to server
    });
  })();
</script>
@endsection
