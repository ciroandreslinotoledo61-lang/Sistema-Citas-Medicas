<?php
$esPaciente = $usuarioAutenticado['codigoRol'] === 'paciente';
$esMedico = $usuarioAutenticado['codigoRol'] === 'medico';
if ($esPaciente) {
    require __DIR__ . '/citas-paciente.php';
    return;
}
?>
<section class="titulo-vista">
    <div><p class="sobretitulo"><?= $esPaciente ? 'MI ATENCIÓN' : 'AGENDA' ?></p><h1><?= $esPaciente ? 'Mis citas' : ($esMedico ? 'Mi agenda' : 'Citas médicas') ?></h1><p><?= $esPaciente ? 'Consulta tus reservas o cancela una cita antes de su inicio.' : ($esMedico ? 'Consulta las citas asignadas a tu atención.' : 'Consulta las reservas, reprograma y registra la asistencia.') ?></p></div>
    <?php if (!$esMedico): ?><a class="boton" href="/citas/nueva"><?= mostrarIcono('mas') ?> <?= $esPaciente ? 'Reservar cita' : 'Agendar cita' ?></a><?php endif; ?>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de citas">
    <div class="cabecera-listado"><h2><?= $esPaciente ? 'Tus reservas' : 'Citas registradas' ?></h2><span class="texto-secundario">Agenda de citas</span></div>
    <form id="formulario-busqueda" class="barra-busqueda filtros-agenda" method="get">
        <label class="crecer" for="busqueda-listado"><span>Buscar</span><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="<?= $esPaciente ? 'Médico o especialidad' : 'Paciente, cédula o médico' ?>"></label>
        <input name="idPaciente" type="hidden" value="">
        <?php if (!$esPaciente && !$esMedico): ?><label>Médico<select name="idMedico" id="filtro-medico-agenda"><option value="">Todos los médicos</option></select></label><?php endif; ?>
        <label>Fecha<input name="fecha" type="date"></label>
        <label>Estado<select name="estado"><option value="">Todos</option><option value="programada">Programada</option><option value="cancelada">Cancelada</option><option value="reprogramada">Reprogramada</option><option value="no_asistio">Inasistencia</option><option value="atendida">Atendida</option></select></label>
        <button class="boton boton-secundario" type="submit">Filtrar</button>
    </form>
    <p id="filtro-paciente-activo" class="nota-informativa" hidden></p>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Citas. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Citas disponibles para tu cuenta</caption><thead><tr><th scope="col">Código</th><th scope="col">Fecha y hora</th><th scope="col">Paciente</th><th scope="col">Médico</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-citas"><tr><td colspan="6" class="sin-resultados">Cargando citas...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 3) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span><?= $esPaciente ? 'Para reprogramar, comunícate con recepción.' : ($esMedico ? 'Gestiona solo tus citas: atención desde el inicio, inasistencia al finalizar y cancelación con motivo antes del inicio.' : 'El registro de asistencia se realiza desde el detalle de la cita.') ?></span></p>
