<?php
// Profesionales activos y sus agendas.
?>
<section class="page" id="profesionales">
    <div class="cols">
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Equipo profesional</h2>
                    <p>Profesionales registrados en el sistema</p>
                </div><button class="textbtn" id="newPro">＋ Agregar</button>
            </div>
            <div class="activity" id="professionalsList">
                <div class="activityrow">
                    <div class="mini">…</div>
                    <div><b>Cargando profesionales</b>
                        <p>Consultando la base de datos.</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Agenda y disponibilidad</h2>
                    <p>Configuración por profesional</p>
                </div>
            </div>
            <div class="simple-note">Cada profesional puede asociarse a varias especialidades. La agenda se controla a nivel del profesional para evitar turnos superpuestos.<br><br><button class="btn secondary" id="agendaInfo">Ver lógica de agenda</button></div>
        </section>
    </div>
</section>
