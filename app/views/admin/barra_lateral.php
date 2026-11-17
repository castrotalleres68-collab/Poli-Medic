<?php
// Menú lateral. Los botones con data-page cambian de página (admin.js › go).
?>
<aside class="side">
    <div class="brand">
        <div class="mark">+</div>
        <div><b>Poli Medic</b><small>POLICONSULTORIOS MÉDICOS</small></div>
    </div>
    <nav>
        <div class="label">Espacio de trabajo</div>
        <button class="nav active" data-page="dashboard"><span>⌂</span>Resumen general</button><button class="nav" data-page="turnos"><span>▦</span>Turnos</button><button class="nav" data-page="pacientes"><span>♧</span>Pacientes</button><button class="nav" data-page="profesionales"><span>✳</span>Profesionales</button><button class="nav" data-page="especialidades"><span>◇</span>Especialidades</button>
        <div class="label">Sistema</div>
        <button class="nav" data-page="actividad"><span>◷</span>Registro de actividad</button><button class="nav" id="settings"><span>⚙</span>Cuenta</button>
    </nav>
    <div class="sidebottom">
        <div class="help"><b>Un espacio para organizar mejor</b>
            <p>Agendas, solicitudes y seguimiento desde un mismo lugar.</p>
        </div><button class="user" id="sidebarAccount" type="button" aria-label="Ver cuenta del administrador"><span class="avatar">AD</span><span><b>Administración</b><small>Consultar cuenta</small></span></button>
    </div>
</aside>
