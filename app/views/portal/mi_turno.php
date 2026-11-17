<?php
// Vista "Mi Turno": el paciente consulta y cancela sus turnos con el DNI (cliente.js › miTurnoForm).
?>
<section class="mi-turno-view" id="miTurnoView">
    <div class="mi-turno-card">
        <div class="eyebrow">Tus consultas</div>
        <h1>Mi Turno</h1>
        <p class="intro">Ingresá tu DNI para consultar tus turnos registrados.</p>
        <form class="mi-turno-form" id="miTurnoForm">
            <div class="field"><label for="miTurnoDni">DNI del paciente</label><input id="miTurnoDni" name="dni" inputmode="numeric" autocomplete="off" pattern="[0-9]{7,8}" minlength="7" maxlength="8" required aria-describedby="miTurnoHelp"><small id="miTurnoHelp" class="hint">Solo números, entre 7 y 8 dígitos.</small></div>
            <button class="btn primary" type="submit">Consultar turno</button>
        </form>
        <div id="miTurnoMessage" class="mi-turno-message" role="status" aria-live="polite"></div>
        <div id="miTurnoResults" class="mi-turno-results"></div>
        <div style="margin-top:22px"><button class="btn secondary" type="button" id="miTurnoBook">Reservar turno</button> <button class="btn secondary" type="button" id="miTurnoHome">Volver al inicio</button></div>
    </div>
</section>
