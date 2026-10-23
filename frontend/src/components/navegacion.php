<?php if ($esVistaPrivada): ?>
<?php
$rolMenu = $usuarioAutenticado['codigoRol'];
$esAdmin = $rolMenu === 'administrador';
$esRecepcion = $rolMenu === 'recepcionista';
$esPaciente = $rolMenu === 'paciente';
$pacientesActivo = str_starts_with($paginaActual, 'paciente');
$usuariosActivo = str_starts_with($paginaActual, 'usuario');
$medicosActivo = str_starts_with($paginaActual, 'medico');
$especialidadesActivo = str_starts_with($paginaActual, 'especialidad');
?>
<div class="fondo-navegacion" id="fondo-navegacion" hidden></div>
<aside class="navegacion-lateral" id="navegacion-lateral" aria-label="Menu principal">
    <div class="cabecera-lateral">
        <a class="marca" href="/panel" aria-label="Clinica, inicio">
            <span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span>
            <span>Clinica<small>Centro medico</small></span>
        </a>
        <button class="boton-icono cerrar-navegacion" id="cerrar-navegacion" type="button" aria-label="Cerrar menu"><?= mostrarIcono('cerrar') ?></button>
    </div>
    <p class="etiqueta-menu">PRINCIPAL</p>
    <nav class="menu-lateral" aria-label="Navegacion principal">
        <a href="/panel" <?= $paginaActual === 'panel' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('panel') ?><span>Panel principal</span></a>

        <details class="grupo-menu" <?= str_starts_with($paginaActual, 'cita') ? 'open' : '' ?>>
            <summary><?= mostrarIcono('calendario') ?><span><?= $esPaciente ? 'Mis citas' : ($rolMenu === 'medico' ? 'Mi agenda' : 'Citas medicas') ?></span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/citas" <?= in_array($paginaActual, ['citas', 'cita-detalle', 'cita-reprogramar'], true) ? 'aria-current="page"' : '' ?>>Consultar citas</a>
                <?php if ($rolMenu !== 'medico'): ?><a href="/citas/nueva" <?= $paginaActual === 'cita-nueva' ? 'aria-current="page"' : '' ?>>Reservar cita</a><?php endif; ?>
            </div>
        </details>
        <?php if ($esAdmin || $rolMenu === 'medico'): ?>
        <details class="grupo-menu" <?= str_starts_with($paginaActual, 'horario') ? 'open' : '' ?>>
            <summary><?= mostrarIcono('calendario') ?><span><?= $esAdmin ? 'Horarios de atencion' : 'Mis horarios' ?></span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/horarios" <?= $paginaActual === 'horarios' ? 'aria-current="page"' : '' ?>>Consultar horarios</a>
                <?php if ($esAdmin): ?><a href="/horarios/nuevo" <?= $paginaActual === 'horario-nuevo' ? 'aria-current="page"' : '' ?>>Crear horario</a><?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <?php if ($esAdmin || $esRecepcion): ?>
        <span class="etiqueta-menu">GESTION</span>
        <details class="grupo-menu" <?= $pacientesActivo ? 'open' : '' ?>>
            <summary><?= mostrarIcono('pacientes') ?><span>Pacientes</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/pacientes" <?= $paginaActual === 'pacientes' || $paginaActual === 'paciente-detalle' ? 'aria-current="page"' : '' ?>>Consultar pacientes</a>
                <a href="/pacientes/nuevo" <?= $paginaActual === 'paciente-nuevo' ? 'aria-current="page"' : '' ?>>Registrar paciente</a>
            </div>
        </details>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
        <details class="grupo-menu" <?= $usuariosActivo ? 'open' : '' ?>>
            <summary><?= mostrarIcono('usuarios') ?><span>Usuarios y autenticacion</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/usuarios" <?= $paginaActual === 'usuarios' || $paginaActual === 'usuario-editar' ? 'aria-current="page"' : '' ?>>Administrar cuentas</a>
                <a href="/usuarios/nuevo" <?= $paginaActual === 'usuario-nuevo' ? 'aria-current="page"' : '' ?>>Crear usuario</a>
                <span class="enlace-pendiente">Cambiar contrasena <small>Pendiente</small></span>
            </div>
        </details>
        <details class="grupo-menu" <?= ($medicosActivo || $especialidadesActivo) ? 'open' : '' ?>>
            <summary><?= mostrarIcono('medicos') ?><span>Medicos y especialidades</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/medicos" <?= $paginaActual === 'medicos' || $paginaActual === 'medico-editar' ? 'aria-current="page"' : '' ?>>Gestionar medicos</a>
                <a href="/medicos/nuevo" <?= $paginaActual === 'medico-nuevo' ? 'aria-current="page"' : '' ?>>Registrar medico</a>
                <a href="/especialidades" <?= $paginaActual === 'especialidades' || $paginaActual === 'especialidad-editar' ? 'aria-current="page"' : '' ?>>Gestionar especialidades</a>
                <a href="/especialidades/nueva" <?= $paginaActual === 'especialidad-nueva' ? 'aria-current="page"' : '' ?>>Crear especialidad</a>
            </div>
        </details>
        <a href="/reportes" <?= $paginaActual === 'reportes' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('panel') ?><span>Reportes administrativos</span></a>
        <span class="etiqueta-menu">PROXIMOS MODULOS</span>
        <details class="grupo-menu grupo-menu-pendiente">
            <summary><?= mostrarIcono('calendario') ?><span>Consultorios</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <span class="enlace-pendiente">Gestionar consultorios <small>Pendiente</small></span>
            </div>
        </details>
        <details class="grupo-menu grupo-menu-pendiente">
            <summary><?= mostrarIcono('informacion') ?><span>Bitacora del sistema</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <span class="enlace-pendiente">Consultar bitacora <small>Pendiente</small></span>
            </div>
        </details>
        <details class="grupo-menu grupo-menu-pendiente">
            <summary><?= mostrarIcono('panel') ?><span>Mas funcionalidades</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <span class="enlace-pendiente">Consultas e historial <small>Pendiente</small></span>
                <span class="enlace-pendiente">Laboratorio digital <small>Pendiente</small></span>
            </div>
        </details>
        <?php elseif ($esPaciente): ?>
        <span class="etiqueta-menu">MI ESPACIO</span>
        <a href="/mi-perfil" <?= $paginaActual === 'mi-perfil' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('perfil') ?><span>Mi perfil</span></a>
        <?php endif; ?>
    </nav>
    <div class="pie-lateral">
        <div class="usuario-lateral">
            <span class="avatar" aria-hidden="true"><?= escaparTextoHtml(mb_strtoupper(mb_substr($usuarioAutenticado['nombresUsuario'], 0, 1))) ?></span>
            <div><strong><?= escaparTextoHtml($usuarioAutenticado['nombresUsuario']) ?></strong><span><?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></span></div>
        </div>
        <button class="boton-salir" type="button" id="boton-cerrar-sesion"><?= mostrarIcono('salir') ?> Cerrar sesion</button>
    </div>
</aside>
<?php else: ?>
<header class="cabecera-publica">
    <div class="contenedor barra-navegacion">
        <a class="marca" href="/" aria-label="Clinica, inicio"><span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span><span>Clinica<small>Centro medico</small></span></a>
        <nav class="menu-publico" aria-label="Navegacion principal">
            <a href="/iniciar-sesion" <?= $paginaActual === 'iniciar-sesion' ? 'aria-current="page"' : '' ?>>Iniciar sesion</a>
            <a class="boton boton-secundario boton-pequeno" href="/registro-paciente" <?= $paginaActual === 'registro-paciente' ? 'aria-current="page"' : '' ?>>Crear cuenta</a>
        </nav>
    </div>
</header>
<?php endif; ?>
