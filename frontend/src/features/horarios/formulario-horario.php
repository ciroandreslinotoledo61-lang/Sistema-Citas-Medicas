<a class="enlace-volver" href="/horarios">&larr; Volver a horarios</a>
<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACION</p><h1>Crear horario de atencion</h1><p>Define un bloque para un medico. Los turnos se generan al guardar.</p></div></section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-horario" method="post">
        <fieldset id="campos-horario" disabled><legend>Datos del bloque</legend>
            <label>Medico<select name="idMedico" required><option value="">Cargando medicos...</option></select></label>
            <div class="rejilla-formulario">
                <label>Fecha de atencion<input name="fechaAtencion" type="date" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required></label>
                <label>Hora de inicio<input name="horaInicio" type="time" step="300" required></label>
                <label>Hora de fin<input name="horaFin" type="time" step="300" required></label>
            </div>
            <p class="ayuda" id="duracion-horario">Cada cita nueva dura 30 minutos. Un bloque de 08:00 a 09:00 genera dos turnos.</p><p class="ayuda">El bloque debe contener turnos completos y no cruzarse con otro bloque del mismo medico.</p>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-horario" disabled>Crear horario y turnos</button><a class="enlace" href="/horarios">Volver al listado</a></div>
    </form>
</section>
