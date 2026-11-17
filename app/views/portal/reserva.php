<?php
// Vista de reserva en 6 pasos. El contenido de #bookcard lo dibuja cliente.js › renderStep().
?>
<section class="booking-view" id="bookingView">
    <div class="booking-top"><button class="btn danger" id="backHome" type="button">← Volver al inicio</button></div>
    <div class="steps" id="steps">
        <div class="step active">01 · Especialidad</div>
        <div class="step">02 · Profesional</div>
        <div class="step">03 · Fecha</div>
        <div class="step">04 · Horario</div>
        <div class="step">05 · Tus datos</div>
        <div class="step">06 · Confirmación</div>
    </div>
    <div class="bookcard" id="bookcard"></div>
</section>
