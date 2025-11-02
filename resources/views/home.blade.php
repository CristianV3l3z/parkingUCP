@extends('layout-login-register')

@section('content')

{{-- Servicios --}}

<section id="servicios" class="hero" style="background-image: url('{{ asset('images/hero.jpg') }}')">
  <div class="hero-overlay container">
    <div class="hero-left">
      <h1>Paga rapido y facil en segundos</h1>
      <p class="lead">Pagos seguros, horarios flexibles y control en tiempo real de tu vehículo.</p>

      <div class="hero-cta">
        <a href="{{ url('/contacto') }}" class="btn btn-ghost">Contáctanos</a>
      </div>
    </div>

    <div class="hero-right stats">
      <div class="stat">
        <div class="stat-val">200</div>
        <div class="stat-label">Plazas disponibles</div>
      </div>
      <div class="stat">
        <div class="stat-val">Disponibilidad</div>
        <div class="stat-label">De Lunes a Sabados</div>
      </div>
      <div class="stat">
        <div class="stat-val">Pago digital</div>
        <div class="stat-label">Transacciones bancarias</div>
      </div>
    </div>
  </div>
</section>

<section class="container servicios-grid">
  <div class="servicios-card">
    <img src="{{ asset('images/parking-lot-above.jpg') }}" alt="Servicios">
  </div>
    
  <div class="servicios-info">
    <h2>Nuestros servicios</h2>
    <p>
      Optimiza tu tiempo: paga y accede al parqueadero sin filas. Implementamos control por matrículas,
      notificaciones en tiempo real y tarifas inteligentes según duración.
    </p>

    <ul class="feature-list">
      <li><strong>Sin Reservas</strong> — No tienes necesidad de reservar tu plaza.</li>
      <li><strong>Gestión de tiempos</strong> — Calculadora automática según horas solicitadas.</li>
      <li><strong>Seguridad</strong> — Registro y control por cámaras y constante vigilancia.</li>
    </ul>
  </div>
</section>


{{-- Acerca de nosotros --}}

<section id="acerca" class="container acerca-section">
  <div class="acerca-left">
    <h2>Quiénes somos</h2>
    <p class="muted">
      Somos una plataforma dedicada a mejorar la experiencia de parqueo: menos estrés, más control y pagos inmediatos.
      Nuestro equipo combina tecnología, diseño y logística para ofrecer soluciones que reduzcan tiempos de búsqueda y optimicen las salidas.
    </p>

    <div class="values">
      <div class="value">
        <h4>Transparencia</h4>
        <p>Tarifas claras y reportes en tiempo real.</p>
      </div>
      <div class="value">
        <h4>Confiabilidad</h4>
        <p>Monitoreo constante y backups de datos.</p>
      </div>
      <div class="value">
        <h4>Innovación</h4>
        <p>Mejoramos con data y experiencia de usuario.</p>
      </div>
    </div>
  </div>

  <div class="acerca-right">
    <img src="{{ asset('images/quienes-somos.png') }}" alt="Quiénes somos">
  </div>
</section>

{{-- Contactos --}}

<section id="contacto" class="container contacto-section">
  <div class="contact-left">
    <h2>Contáctanos</h2>
    <p>
      Escríbenos tus dudas sobre tarifas, facturación o integración para gestión corporativa.
      Respondemos en menos de 24 horas.
    </p>

    <ul class="contact-info">
      <li><strong>Email:</strong> soporte@parking.com</li>
      <li><strong>Tel:</strong> (555) 555-555</li>
      <li><strong>Dirección:</strong> Calle Ejemplo #123, Ciudad</li>
    </ul>
  </div>

  <div class="contact-right">
    <form action="{{ url('/enviar-contacto') }}" method="POST" class="contact-form">
      @csrf
      <div class="row">
        <div class="col">
          <label>Nombre</label>
          <input type="text" name="nombre" required>
        </div>
        <div class="col">
          <label>Apellido</label>
          <input type="text" name="apellido" required>
        </div>
      </div>

      <label>Correo electrónico</label>
      <input type="email" name="email" required>

      <label>Mensaje</label>
      <textarea name="mensaje" rows="5" required></textarea>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enviar</button>
      </div>
    </form>
  </div>
</section>
@endsection


