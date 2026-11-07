<?php if ($esVistaPrivada): ?>
<?php
$rolMenu = $usuarioAutenticado['codigoRol'];
$esAdmin = $rolMenu === 'administrador';
$esRecepcion = $rolMenu === 'recepcionista';
$esPaciente = $rolMenu === 'paciente';
$pacientesActivo = str_starts_with($paginaActual, 'paciente');
$usuariosActivo = str_starts_with($paginaActual, 'usuario') || $paginaActual === 'cambiar-contrasena';
$cambioObligatorio = $usuarioAutenticado['requiereCambioContrasena'] ?? false;
$medicosActivo = str_starts_with($paginaActual, 'medico');
$especialidadesActivo = str_starts_with($paginaActual, 'especialidad');
?>
<div class="fondo-navegacion" id="fondo-navegacion" hidden></div>
<aside class="navegacion-lateral" id="navegacion-lateral" aria-label="Menú principal">
    <div class="cabecera-lateral">
        <a class="marca" href="/panel" aria-label="Clínica, inicio">
            <span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span>
            <span>Clínica<small>Centro médico</small></span>
        </a>
        <button class="boton-icono cerrar-navegacion" id="cerrar-navegacion" type="button" aria-label="Cerrar menú"><?= mostrarIcono('cerrar') ?></button>
    </div>
    <p class="etiqueta-menu">PRINCIPAL</p>
    <nav class="menu-lateral" aria-label="Navegación principal">
        <?php if ($cambioObligatorio): ?>
        <a href="/cambiar-contrasena" aria-current="page"><?= mostrarIcono('candado') ?><span>Cambiar contraseña</span></a>
        <?php else: ?>
        <a href="/panel" <?= $paginaActual === 'panel' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('panel') ?><span>Panel principal</span></a>

        <details class="grupo-menu" <?= str_starts_with($paginaActual, 'cita') ? 'open' : '' ?>>
            <summary><?= mostrarIcono('calendario') ?><span><?= $esPaciente ? 'Mis citas' : ($rolMenu === 'medico' ? 'Mi agenda' : 'Citas médicas') ?></span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/citas" <?= in_array($paginaActual, ['citas', 'cita-detalle', 'cita-reprogramar'], true) ? 'aria-current="page"' : '' ?>>Consultar citas</a>
                <?php if ($rolMenu !== 'medico'): ?><a href="/citas/nueva" <?= $paginaActual === 'cita-nueva' ? 'aria-current="page"' : '' ?>>Reservar cita</a><?php endif; ?>
            </div>
        </details>
        <?php if ($esAdmin || $rolMenu === 'medico'): ?>
        <details class="grupo-menu" <?= str_starts_with($paginaActual, 'horario') ? 'open' : '' ?>>
            <summary><?= mostrarIcono('calendario') ?><span><?= $esAdmin ? 'Horarios de atención' : 'Mis horarios' ?></span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/horarios" <?= $paginaActual === 'horarios' ? 'aria-current="page"' : '' ?>>Consultar horarios</a>
                <?php if ($esAdmin): ?><a href="/horarios/nuevo" <?= $paginaActual === 'horario-nuevo' ? 'aria-current="page"' : '' ?>>Programar horarios</a><?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <?php if ($esAdmin || $esRecepcion): ?>
        <span class="etiqueta-menu">GESTIÓN</span>
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
            <summary><?= mostrarIcono('usuarios') ?><span>Usuarios y autenticación</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/usuarios" <?= $paginaActual === 'usuarios' || $paginaActual === 'usuario-editar' ? 'aria-current="page"' : '' ?>>Administrar cuentas</a>
                <a href="/usuarios/nuevo" <?= $paginaActual === 'usuario-nuevo' ? 'aria-current="page"' : '' ?>>Crear usuario</a>
                <a href="/cambiar-contrasena" <?= $paginaActual === 'cambiar-contrasena' ? 'aria-current="page"' : '' ?>>Cambiar contraseña</a>
            </div>
        </details>
        <details class="grupo-menu" <?= ($medicosActivo || $especialidadesActivo) ? 'open' : '' ?>>
            <summary><?= mostrarIcono('medicos') ?><span>Médicos y especialidades</span><span class="chevron-menu" aria-hidden="true"></span></summary>
            <div class="grupo-opciones">
                <a href="/medicos" <?= $paginaActual === 'medicos' || $paginaActual === 'medico-editar' ? 'aria-current="page"' : '' ?>>Gestionar médicos</a>
                <a href="/medicos/nuevo" <?= $paginaActual === 'medico-nuevo' ? 'aria-current="page"' : '' ?>>Registrar médico</a>
                <a href="/especialidades" <?= $paginaActual === 'especialidades' || $paginaActual === 'especialidad-editar' ? 'aria-current="page"' : '' ?>>Gestionar especialidades</a>
                <a href="/especialidades/nueva" <?= $paginaActual === 'especialidad-nueva' ? 'aria-current="page"' : '' ?>>Crear especialidad</a>
            </div>
        </details>
        <a href="/reportes" <?= $paginaActual === 'reportes' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('panel') ?><span>Reportes administrativos</span></a>
        <a href="/bitacora" <?= $paginaActual==='bitacora'?'aria-current="page"':'' ?>><?= mostrarIcono('informacion') ?><span>Bitácora del sistema</span></a>
        <?php elseif ($esPaciente): ?>
        <span class="etiqueta-menu">MI ESPACIO</span>
        <a href="/mi-perfil" <?= $paginaActual === 'mi-perfil' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('perfil') ?><span>Mi perfil</span></a>
        <?php endif; ?>
        <?php if($rolMenu==='medico'): ?><a href="/consultas" <?= str_starts_with($paginaActual,'consulta')?'aria-current="page"':'' ?>><?= mostrarIcono('medicos') ?><span>Consultas e historial</span></a>
        <?php elseif($esPaciente): ?><a href="/historial-clinico" <?= $paginaActual==='historial-clinico'?'aria-current="page"':'' ?>><?= mostrarIcono('informacion') ?><span>Mi historial clínico</span></a><?php endif; ?>
        <?php if ($rolMenu==='medico' || $esPaciente): ?><a href="/recetas" <?= str_starts_with($paginaActual,'receta')?'aria-current="page"':'' ?>><?= mostrarIcono('informacion') ?><span>Mis recetas</span></a><?php endif; ?>
        <?php if (!$esPaciente): ?><a href="/sala-espera" <?= $paginaActual==='sala-espera'?'aria-current="page"':'' ?>><?= mostrarIcono('pacientes') ?><span>Sala de espera</span></a><?php endif; ?>
        <?php if ($rolMenu!=='medico'): ?><a href="/pagos-demo" <?= str_starts_with($paginaActual,'pago')?'aria-current="page"':'' ?>><?= mostrarIcono('informacion') ?><span>Pagos · Demostración</span></a><?php endif; ?>
        <?php if (!$esAdmin): ?>
        <span class="etiqueta-menu">MI CUENTA</span>
        <a href="/cambiar-contrasena" <?= $paginaActual === 'cambiar-contrasena' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('candado') ?><span>Cambiar contraseña</span></a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
    <div class="pie-lateral">
        <div class="usuario-lateral">
            <span class="avatar" aria-hidden="true"><?= escaparTextoHtml(mb_strtoupper(mb_substr($usuarioAutenticado['nombresUsuario'], 0, 1))) ?></span>
            <div><strong><?= escaparTextoHtml($usuarioAutenticado['nombresUsuario']) ?></strong><span><?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></span></div>
        </div>
        <button class="boton-salir" type="button" id="boton-cerrar-sesion"><?= mostrarIcono('salir') ?> Cerrar sesión</button>
    </div>
</aside>
<?php else: ?>
<header class="cabecera-publica">
    <div class="contenedor barra-navegacion">
        <a class="marca" href="/" aria-label="Clínica, inicio"><span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span><span>Clínica<small>Centro médico</small></span></a>
        <nav class="menu-publico" aria-label="Navegación principal">
            <a href="/iniciar-sesion" <?= $paginaActual === 'iniciar-sesion' ? 'aria-current="page"' : '' ?>>Iniciar sesión</a>
            <a class="boton boton-secundario boton-pequeno" href="/registro-paciente" <?= $paginaActual === 'registro-paciente' ? 'aria-current="page"' : '' ?>>Crear cuenta</a>
        </nav>
    </div>
</header>
<?php endif; ?>
