<?php
// Cabecera del portal: logotipo, menú de navegación, botón de tema y botón "Reservar turno".
// Ids que usa cliente.js: brandHome, navMiTurno, themeToggle, navBook.
?>
<header class="nav">
    <a class="brand" href="#inicio" id="brandHome"><span class="brandmark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="M12 3v18M3 12h18"/></svg></span><span>Poli Medic<small>POLICONSULTORIOS MÉDICOS</small></span></a>
    <nav class="links">
        <a href="#especialidades" data-home-link>Especialidades</a>
        <a href="#equipo" data-home-link>Nuestro equipo</a>
        <a href="#nosotros" data-home-link>El centro</a>
        <a href="#mi-turno" id="navMiTurno">Mi Turno</a>
    </nav>
    <div style="display:flex;gap:8px;align-items:center">
        <button class="btn secondary" id="themeToggle" type="button" aria-label="Cambiar tema" title="Cambiar modo claro u oscuro">◐</button>
        <button class="btn primary" id="navBook">Reservar turno <span aria-hidden="true">↗</span></button>
    </div>
</header>
