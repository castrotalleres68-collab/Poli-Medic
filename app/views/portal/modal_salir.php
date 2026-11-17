<?php
// Ventana de confirmación al abandonar una reserva en curso (cliente.js › requestExit).
?>
<div class="exit-modal-backdrop" id="exitModal" aria-hidden="true">
    <section class="exit-modal" role="dialog" aria-modal="true" aria-labelledby="exitModalTitle" aria-describedby="exitModalText" tabindex="-1">
        <h2 id="exitModalTitle">¿Querés salir de esta consulta?</h2>
        <p id="exitModalText">Si volvés al inicio, podrías perder los datos que ingresaste hasta el momento. ¿Estás seguro de que querés salir?</p>
        <div class="exit-modal-actions"><button class="btn secondary" id="cancelExit" type="button">Cancelar</button><button class="btn confirm-exit" id="confirmExit" type="button">Salir y volver al inicio</button></div>
    </section>
</div>
