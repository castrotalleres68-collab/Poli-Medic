<?php
// Pantalla de inicio del portal (visible cuando no se está reservando ni consultando "Mi Turno").
// Secciones: hero, especialidades, banda, equipo y ubicación.
?>
<main class="home-view" id="homeView">
    <!-- ===== HERO — portada con el botón principal de reserva ===== -->
    <section class="hero" id="inicio">
        <div>
            <div class="eyebrow">Cerca tuyo, en cada etapa</div>
            <h1>Cuidarte bien<br>empieza por <em>escucharte.</em></h1>
            <p class="lead">Un espacio de salud pensado para vos y tu familia. Atención profesional, trato cercano y el tiempo que necesitás para sentirte acompañado.</p>
            <div class="actions"><button class="btn primary" id="heroBook">Encontrá tu especialista <span>↗</span></button><a class="btn secondary" href="#especialidades">Conocé las especialidades <span>↓</span></a></div>
            <div class="trust">
                <div class="avatars" aria-hidden="true"><span class="avatar">✓</span><span class="avatar">+</span><span class="avatar">♥</span></div>
                <span>Un equipo interdisciplinario<br><strong style="color:var(--ink)">para acompañar tu bienestar</strong></span>
            </div>
        </div>
        <div class="hero-photo-wrap"><img class="hero-photo" src="assets/img/fachada-poli-medic.png" alt="Fachada del policonsultorio Poli Medic"></div>
    </section>
    <!-- ===== ESPECIALIDADES — se reemplaza con los datos reales desde la API (cliente.js › renderHomeCatalogs) ===== -->
    <section class="section" id="especialidades">
        <div class="section-head">
            <div>
                <div class="kicker">Cómo podemos ayudarte</div>
                <h2>Especialidades para<br>cada momento de tu vida.</h2>
            </div>
            <p class="sub">Encontrá el área que necesitás y conocé a los profesionales que pueden acompañarte.</p>
        </div>
        <div class="specialties">
            <article class="spec">
                <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s-8-4.5-8-11a4 4 0 0 1 8-1 4 4 0 0 1 8 1c0 6.5-8 11-8 11Z"/><path d="M12 7v8M8 11h8"/></svg></div>
                <h3>Clínica médica</h3>
                <p>Prevención, orientación y cuidado general de tu salud.</p>
            </article>
            <article class="spec">
                <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2M9 13l3 3 3-3"/></svg></div>
                <h3>Ginecología</h3>
                <p>Prevención y acompañamiento en cada etapa de la vida.</p>
            </article>
            <article class="spec">
                <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 14c0-5 3-9 8-9s8 4 8 9M7 14v4a2 2 0 0 0 2 2h2M17 14v4a2 2 0 0 1-2 2h-2M12 5V2"/></svg></div>
                <h3>Kinesiología</h3>
                <p>Movimiento y recuperación acompañados por profesionales.</p>
            </article>
            <article class="spec">
                <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 3v5a5 5 0 0 0 10 0V3M12 13v8M8 21h8M5 3h4M15 3h4"/></svg></div>
                <h3>Laboratorio</h3>
                <p>Análisis clínicos para acompañar el cuidado médico.</p>
            </article>
            <article class="spec">
                <div class="spec-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H12v19H7.5A3.5 3.5 0 0 1 4 17.5zM20 5.5A3.5 3.5 0 0 0 16.5 2H12v19h4.5a3.5 3.5 0 0 0 3.5-3.5z"/></svg></div>
                <h3>Psicopedagogía</h3>
                <p>Apoyo para los procesos de aprendizaje y desarrollo.</p>
            </article>
        </div>
    </section>
    <!-- ===== BANDA — llamado a la acción ===== -->
    <section class="section" id="nosotros">
        <div class="band">
            <div>
                <div class="kicker" style="color:#e9c16d">Tu salud, con cercanía</div>
                <h2>Un buen cuidado también<br>se siente en el trato.</h2>
                <p>Te ayudamos a encontrar la especialidad y el profesional que necesitás.<br>La reserva de turnos se realiza en un espacio simple y separado.</p>
            </div><button class="btn" id="bandBook">Quiero reservar un turno ↗</button>
        </div>
    </section>
    <!-- ===== EQUIPO — se reemplaza con los profesionales reales desde la API ===== -->
    <section class="section" id="equipo">
        <div class="section-head">
            <div>
                <div class="kicker">Personas que cuidan</div>
                <h2>Conocé a nuestro equipo.</h2>
            </div>
            <p class="sub">Profesionales que ponen sus conocimientos al servicio de una atención humana y responsable.</p>
        </div>
        <div class="professionals">
            <article class="pro">
                <div class="prophoto">GO</div>
                <div>
                    <h3>Dra. Gloria Ojeda</h3>
                    <p>Clínica médica · Dirección médica</p>
                    <span>CONOCER SU ESPECIALIDAD ↗</span>
                </div>
            </article>
            <article class="pro">
                <div class="prophoto" style="background:#f0e2ca;color:#785a2d">LL</div>
                <div>
                    <h3>Lic. Lucrecia Lencina Artigas</h3>
                    <p>Nutrición funcional e integrativa</p>
                    <span>CONOCER SU ESPECIALIDAD ↗</span>
                </div>
            </article>
        </div>
        <p style="color:var(--muted);font-size:11px;margin-top:15px">* Presentación de muestra basada en los profesionales identificados. La disponibilidad de turnos debe confirmarse con la agenda real.</p>
    </section>
    <!-- ===== UBICACIÓN — enlace a Google Maps ===== -->
    <section class="section location-section" id="ubicacion" aria-labelledby="locationTitle">
        <div class="location-card">
            <div class="location-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
            <div class="location-copy">
                <div class="kicker">Estamos para acompañarte</div>
                <h2 id="locationTitle">Cómo llegar a Poli Medic</h2>
                <p>Consultá nuestra ubicación y obtené indicaciones para llegar al policonsultorio.</p>
            </div><a class="btn primary location-link" href="https://maps.app.goo.gl/ujoPtHRosForYnvr5" target="_blank" rel="noopener noreferrer"><svg aria-hidden="true" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>Ver ubicación en Google Maps <span aria-hidden="true">↗</span></a>
        </div>
    </section>
</main>
