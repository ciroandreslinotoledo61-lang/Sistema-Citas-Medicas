<a class="enlace-volver" href="/medicos">&larr; Volver a medicos</a>
<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACION</p><h1><?= escaparTextoHtml($tituloPagina) ?></h1><p><?= $idRegistro ? 'Actualiza los datos personales y profesionales del medico.' : 'Crea la cuenta y el perfil profesional del medico.' ?></p></div></section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-medico" method="post">
        <fieldset id="campos-medico" disabled>
            <legend>Datos de la cuenta</legend>
            <?php $mostrarCamposContrasena = $idRegistro === 0; require dirname(__DIR__, 2) . '/components/campos-usuario.php'; ?>
            <div class="separacion-formulario"><h2>Datos profesionales</h2><div class="rejilla-formulario">
                <label>Matricula profesional<input name="matriculaProfesional" required minlength="3" maxlength="40" autocomplete="off" placeholder="Ejemplo: SC-12345"></label>
                <label><span id="etiqueta-especialidad">Especialidad</span><select name="idEspecialidad" required aria-labelledby="etiqueta-especialidad"><option value="">Cargando especialidades...</option></select></label>
            </div></div>
            <p id="ayuda-especialidad" class="ayuda">Selecciona una especialidad activa.</p>
            <?php if ($idRegistro): ?><p class="ayuda">La clave de acceso se conserva. El estado se cambia desde el listado de Medicos.</p><?php endif; ?>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-medico" disabled><?= $idRegistro ? 'Guardar cambios' : 'Registrar medico' ?></button><a class="enlace" href="/medicos">Cancelar</a><a class="enlace" href="/especialidades">Ver especialidades</a></div>
    </form>
</section>
