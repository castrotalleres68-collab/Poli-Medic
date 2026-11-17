<?php
// Panel deslizable de notificaciones (admin.js › openNotifications).
?>
<div class="notification-backdrop" id="notificationBackdrop"></div>
<aside class="notification-panel" id="notificationPanel" aria-label="Notificaciones" aria-hidden="true">
    <div class="notification-head">
        <div>
            <h2>Notificaciones</h2>
            <small class="muted" id="notificationSummary">Actividad de turnos</small>
        </div>
        <div style="display:flex;gap:7px"><button class="btn secondary" id="markAllRead" type="button">Marcar todas leídas</button><button class="iconbtn" id="closeNotifications" type="button" aria-label="Cerrar">×</button></div>
    </div>
    <div class="notification-list" id="notificationList">
        <div class="simple-note">Cargando notificaciones…</div>
    </div>
</aside>
