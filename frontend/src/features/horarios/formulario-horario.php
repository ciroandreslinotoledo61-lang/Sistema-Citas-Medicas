<a class="enlace-volver" href="/horarios">&larr; Volver a horarios</a>
<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACION / AGENDA</p><h1>Programar horarios</h1><p>Organiza un mes o el periodo que necesites. Los turnos se generan consecutivamente.</p></div></section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-horario" method="post">
        <fieldset id="campos-horario" disabled><legend>Medico y periodo de atencion</legend>
            <label>Medico<select name="idMedico" required><option value="">Cargando medicos...</option></select></label>
            <label>Tipo de programacion<select name="tipoProgramacion"><option value="periodo">Un periodo / mes completo</option><option value="dia">Un solo dia</option></select></label>
            <div class="rejilla-formulario">
                <label><span id="etiqueta-fecha-inicial">Fecha inicial</span><input name="fechaInicial" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required></label>
                <label id="campo-fecha-final">Fecha final<input name="fechaFinal" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required></label>
            </div>
            <fieldset id="dias-programacion"><legend>Dias de trabajo</legend><div class="seleccion-dias">
                <?php foreach ([1=>'Lunes',2=>'Martes',3=>'Miercoles',4=>'Jueves',5=>'Viernes',6=>'Sabado',7=>'Domingo'] as $numeroDia=>$nombreDia): ?>
                <label><input type="checkbox" name="diasSemana" value="<?= $numeroDia ?>" <?= $numeroDia <= 5 ? 'checked' : '' ?>><span><?= $nombreDia ?></span></label>
                <?php endforeach; ?>
            </div></fieldset>
            <div class="rejilla-formulario">
                <label>Hora inicial<select name="horaInicio" required><option value="">Cargando horas...</option></select></label>
                <label>Hora final<select name="horaFin" required><option value="">Cargando horas...</option></select></label>
            </div>
            <p class="nota-informativa" id="duracion-horario">Cargando configuracion de la clinica...</p>
            <p class="ayuda" id="prevision-turnos" role="status"></p>
            <p class="ayuda">Se conservan los horarios y las reservas existentes. Las fechas con cruces o con inicio pasado se omiten y aparecen en el resumen.</p>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-horario" disabled>Generar horarios</button><a class="enlace" href="/horarios">Volver al listado</a></div>
    </form>
</section>
<section class="tarjeta margen-superior" id="resumen-programacion" hidden tabindex="-1" aria-labelledby="titulo-resumen">
    <h2 id="titulo-resumen">Resultado de la programacion</h2>
    <dl class="resumen-programacion" id="contadores-programacion"></dl>
    <p class="ayuda">Fechas procesadas: solo los dias de trabajo elegidos. Repetir la operacion conserva los bloques existentes.</p>
    <div class="tabla-contenedor" role="region" aria-label="Resultado por fecha" tabindex="0">
        <table><thead><tr><th scope="col">Fecha</th><th scope="col">Resultado</th><th scope="col">Turnos nuevos</th><th scope="col">Detalle</th></tr></thead><tbody id="detalle-programacion"></tbody></table>
    </div>
</section>
