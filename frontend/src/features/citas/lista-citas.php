<?php
$esPaciente = $usuarioAutenticado['codigoRol'] === 'paciente';
$esMedico = $usuarioAutenticado['codigoRol'] === 'medico';
?>
<section class="titulo-vista">
    <div><p class="sobretitulo"><?= $esPaciente ? 'MI ATENCION' : 'AGENDA' ?></p><h1><?= $esPaciente ? 'Mis citas' : ($esMedico ? 'Mi agenda' : 'Citas medicas') ?></h1><p><?= $esPaciente ? 'Consulta tus reservas o anula una cita antes de su inicio.' : ($esMedico ? 'Consulta las citas asignadas a tu atencion.' : 'Consulta las reservas, reprograma y registra la asistencia.') ?></p></div>
    <?php if (!$esMedico): ?><a class="boton" href="/citas/nueva"><?= mostrarIcono('mas') ?> <?= $esPaciente ? 'Reservar cita' : 'Agendar cita' ?></a><?php endif; ?>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de citas">
    <div class="cabecera-listado"><h2><?= $esPaciente ? 'Tus reservas' : 'Citas registradas' ?></h2><span class="texto-secundario">Turnos de 15 minutos</span></div>
    <form id="formulario-busqueda" class="barra-busqueda filtros-agenda" method="get">
        <label class="crecer" for="busqueda-listado"><span>Buscar</span><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="<?= $esPaciente ? 'Medico o especialidad' : 'Paciente, cedula o medico' ?>"></label>
        <label>Fecha<input name="fecha" type="date"></label>
        <label>Estado<select name="estado"><option value="">Todos</option><option value="confirmada">Confirmada</option><option value="pendiente">Pendiente</option><option value="cancelada">Anulada</option><option value="reprogramada">Reprogramada</option><option value="no_asistio">No asistio</option><option value="atendida">Atendida</option></select></label>
        <button class="boton boton-secundario" type="submit">Filtrar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Citas. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Citas disponibles para tu cuenta</caption><thead><tr><th scope="col">Fecha y hora</th><th scope="col">Paciente</th><th scope="col">Medico</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-citas"><tr><td colspan="5" class="sin-resultados">Cargando citas...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Los horarios se muestran en la zona de la clinica: <?= escaparTextoHtml(date_default_timezone_get()) ?>. <?= $esPaciente ? 'Para reprogramar, comunicate con recepcion.' : ($esMedico ? 'Recepcion registra la asistencia de los pacientes.' : 'El registro de asistencia se realiza desde el detalle de la cita.') ?></span></p>
