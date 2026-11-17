<?php
// Listado de turnos con filtro y cambio de estado.
?>
<section class="page" id="turnos">
    <div class="welcome">
        <div>
            <div class="eyebrow">Agenda y seguimiento</div>
            <p>Revisá solicitudes y cambiá el estado de cada turno.</p>
        </div>
    </div>
    <section class="panel">
        <div class="ph">
            <div>
                <h2>Todos los turnos</h2>
                <p>Información registrada en MySQL</p>
            </div><select class="search" id="filter"><option value="">Todos los estados</option><option>Confirmado</option><option>Cancelado</option><option>Atendido</option></select>
        </div>
        <div class="tablewrap">
            <table id="allturns">
                <thead>
                    <tr><th>Paciente</th><th>Especialidad</th><th>Profesional</th><th>Fecha y hora</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody id="appointmentsBody">
                    <tr><td colspan="6">Cargando turnos…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
