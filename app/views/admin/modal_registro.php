<?php
// Ventana modal reutilizable para alta/edición de pacientes, especialidades y profesionales.
// Sus campos los genera admin.js › openModal().
?>
<div class="modal-backdrop" id="modalBackdrop" role="presentation">
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-head">
            <h2 id="modalTitle">Editar registro</h2>
            <button class="iconbtn" type="button" id="modalClose" aria-label="Cerrar">×</button>
        </div>
        <form id="recordForm">
            <div class="modal-body">
                <div class="modal-error" id="modalError" role="alert"></div>
                <div id="modalFields"></div>
                <div class="modal-actions"><button class="btn secondary" type="button" id="modalCancel">Cancelar</button><button class="btn primary" type="submit" id="modalSubmit">Guardar cambios</button></div>
            </div>
        </form>
    </section>
</div>
