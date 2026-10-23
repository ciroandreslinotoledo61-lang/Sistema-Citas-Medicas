<?php
$esPaciente = $usuarioAutenticado['codigoRol'] === 'paciente';
$esReprogramacion = $idRegistro > 0;
?>
<a class="enlace-volver" href="<?= $esReprogramacion ? '/citas/' . $idRegistro : '/citas' ?>">&larr; Volver a citas</a>
<section class="titulo-vista"><div><p class="sobretitulo"><?= $esPaciente ? 'MI ATENCION' : 'RECEPCION' ?></p><h1><?= $esReprogramacion ? 'Reprogramar cita' : 'Reservar cita' ?></h1><p>Elige el medico, la fecha y un turno disponible.</p></div></section>
<section class="tarjeta formulario-ancho">
    <?php if (!$esPaciente && !$esReprogramacion): ?>
    <form id="busqueda-paciente-reserva" method="get" class="busqueda-paciente-reserva">
        <h2>1. Paciente</h2>
        <label for="buscar-paciente">Buscar por nombre o cedula</label>
        <div class="fila-busqueda"><input id="buscar-paciente" name="busqueda" type="search" maxlength="80" required><button class="boton boton-secundario" type="submit">Buscar paciente</button></div>
        <p class="ayuda">Se muestran hasta 20 coincidencias. Afina la busqueda si el paciente no aparece.</p>
        <p id="mensaje-busqueda-paciente" class="mensaje" role="status" hidden></p>
    </form>
    <?php endif; ?>
    <form id="formulario-cita" method="post">
        <fieldset id="campos-cita" disabled>
            <legend class="solo-lector">Datos de la reserva</legend>
            <?php if (!$esPaciente && !$esReprogramacion): ?>
            <label>Paciente seleccionado<select name="idPaciente" required><option value="">Busca y selecciona un paciente</option></select></label>
            <?php elseif ($esReprogramacion): ?><p class="resumen-reserva" id="resumen-cita-anterior">Cargando cita...</p>
            <?php else: ?><p class="resumen-reserva">Esta reserva se registrara a tu nombre.</p><?php endif; ?>
            <h2 class="margen-superior"><?= $esPaciente || $esReprogramacion ? 'Medico y horario' : '2. Medico y horario' ?></h2>
            <div class="rejilla-formulario">
                <label>Especialidad<select id="filtro-especialidad"><option value="">Todas las especialidades</option></select></label>
                <label>Medico<select name="idMedico" required><option value="">Cargando medicos...</option></select></label>
                <label>Fecha<input name="fecha" type="date" required min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>"></label>
            </div>
            <div class="cabecera-turnos"><h3>Turnos disponibles</h3><button class="enlace-boton" id="actualizar-turnos" type="button">Actualizar</button></div>
            <p class="ayuda">Cada cita dura 15 minutos. Zona horaria: <?= escaparTextoHtml(date_default_timezone_get()) ?>.</p>
            <p id="mensaje-turnos" role="status">Selecciona un medico y una fecha para consultar horarios.</p>
            <div id="turnos-disponibles" class="rejilla-turnos" role="group" aria-label="Selecciona un turno"></div>
            <?php if (!$esReprogramacion): ?><label class="margen-superior">Motivo de consulta (opcional)<textarea name="motivoCita" rows="3" maxlength="500" placeholder="Describe brevemente el motivo de tu visita"></textarea></label><?php endif; ?>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-cita" disabled><?= $esReprogramacion ? 'Confirmar nuevo horario' : 'Confirmar reserva' ?></button><a class="enlace" href="/citas">Volver al listado</a></div>
        <p class="ayuda margen-superior"><?= $esReprogramacion ? 'La reserva anterior se conserva hasta confirmar el nuevo turno.' : 'La disponibilidad se vuelve a comprobar al confirmar la reserva.' ?></p>
    </form>
</section>
