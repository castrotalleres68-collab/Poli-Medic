<?php
// Alta, edición y baja de pacientes.
?>
<section class="page" id="pacientes">
    <section class="panel">
        <div class="ph">
            <div>
                <h2>Pacientes</h2>
                <p>Pacientes registrados en MySQL</p>
            </div><button class="btn primary" id="newPatient">＋ Agregar paciente</button>
        </div>
        <div class="tablewrap">
            <table>
                <thead>
                    <tr><th>Paciente</th><th>Documento</th><th>Contacto</th><th>Obra social</th><th>Último turno</th><th>Estado</th></tr>
                </thead>
                <tbody id="patientsBody">
                    <tr><td colspan="5">Cargando pacientes…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</section>
