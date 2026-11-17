<?php
// Barra superior: título, buscador, campana de notificaciones y cierre de sesión.
?>
<header class="top">
    <div>
        <div class="crumb">Poli Medic / <span id="crumb">Resumen general</span></div>
        <h1 id="title">Buen día, equipo.</h1>
    </div>
    <div class="topright"><input class="search" id="search" placeholder="Buscar paciente, turno…"><button class="iconbtn" id="notify" aria-label="Notificaciones" title="Notificaciones"><svg aria-hidden="true" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><span class="notice-count" id="noticeCount" hidden>0</span></button>
        <form method="post" action="admin.php" id="logoutForm" hidden><button class="logout-option" type="submit" name="salir" value="1">Cerrar sesión</button></form>
    </div>
</header>
