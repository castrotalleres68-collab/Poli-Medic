<?php
// Resumen general: métricas y últimos movimientos (datos de api.php?action=admin_data).
?>
<section class="page active" id="dashboard">
    <div class="welcome">
        <div>
            <div class="eyebrow">Organización diaria</div>
            <p>Revisá los turnos, las solicitudes y las tareas pendientes del centro.</p>
        </div><button class="btn primary" data-go="turnos">＋ Gestionar turnos</button>
    </div>
    <div class="metrics">
        <article class="metric">
            <div class="metrichead">Turnos de hoy <span class="mi">▦</span></div>
            <strong id="metricToday">0</strong>
            <div class="trend muted">Turnos no cancelados</div>
        </article>
        <article class="metric">
            <div class="metrichead">Turnos confirmados <span class="mi">◷</span></div>
            <strong id="metricPending">0</strong>
            <div class="trend muted">Próximos turnos reservados</div>
        </article>
        <article class="metric">
            <div class="metrichead">Pacientes registrados <span class="mi">♧</span></div>
            <strong id="metricPatients">0</strong>
            <div class="trend muted">Registros en la base de datos</div>
        </article>
        <article class="metric">
            <div class="metrichead">Profesionales activos <span class="mi">✳</span></div>
            <strong id="metricProfessionals">0</strong>
            <div class="trend muted">Agendas por especialidad</div>
        </article>
    </div>
    <div class="cols">
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Últimos turnos</h2>
                    <p>Seguimiento de las solicitudes recientes</p>
                </div><button class="textbtn" data-go="turnos">Ver todos ↗</button>
            </div>
            <div class="tablewrap">
                <table id="recent">
                    <thead>
                        <tr><th>Paciente</th><th>Especialidad</th><th>Fecha y hora</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4">Consultando los turnos guardados en la base de datos…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Agenda de hoy</h2>
                    <p>Una vista rápida de la jornada</p>
                </div><button class="textbtn" data-go="turnos">Agenda ↗</button>
            </div>
            <div class="agenda" id="todayAgenda">
                <div class="simple-note">La agenda se completará con los turnos registrados.</div>
            </div>
        </section>
    </div>
    <div class="lower">
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Accesos rápidos</h2>
                    <p>Las tareas más usadas, a mano</p>
                </div>
            </div>
            <div class="quickgrid"><button class="quick" data-go="turnos"><span>＋</span><b>Gestionar turnos</b><small>Confirmar, cancelar o reprogramar</small></button><button class="quick" data-go="pacientes"><span>♧</span><b>Ver pacientes</b><small>Consultar datos de contacto</small></button><button class="quick" data-go="profesionales"><span>✳</span><b>Equipo profesional</b><small>Perfiles y disponibilidad</small></button></div>
        </section>
        <section class="panel">
            <div class="ph">
                <div>
                    <h2>Actividad reciente</h2>
                    <p>Movimientos de muestra</p>
                </div><button class="textbtn" data-go="actividad">Ver registro ↗</button>
            </div>
            <div class="activity">
                <div class="simple-note">La actividad real aparecerá aquí cuando se registren operaciones.</div>
            </div>
        </section>
    </div>
</section>
