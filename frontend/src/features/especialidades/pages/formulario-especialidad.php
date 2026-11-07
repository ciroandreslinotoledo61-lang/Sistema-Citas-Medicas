<a class="enlace-volver" href="/especialidades">&larr; Volver a especialidades</a>
<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACIÓN</p><h1><?= escaparTextoHtml($tituloPagina) ?></h1><p>Define el nombre y la descripción del servicio médico.</p></div></section>
<section class="tarjeta formulario-ancho">
    <p class="ayuda-obligatorios">Los campos con <span class="campo-obligatorio" aria-hidden="true">*</span> son obligatorios.</p>
    <form id="formulario-especialidad" method="post">
        <fieldset id="campos-especialidad" <?= $idRegistro ? 'disabled' : '' ?>>
            <legend>Datos de la especialidad</legend>
            <label><span>Nombre de la especialidad <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="nombreEspecialidad" required minlength="3" maxlength="100" autocomplete="off" placeholder="Ejemplo: Dermatología"></label>
            <label>Descripción (opcional)<input name="descripcionEspecialidad" maxlength="300" autocomplete="off" placeholder="Describe brevemente el servicio"></label>
            <p class="ayuda">El nombre debe ser único. La descripción es opcional.</p>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-especialidad" <?= $idRegistro ? 'disabled' : '' ?>><?= $idRegistro ? 'Guardar cambios' : 'Registrar especialidad' ?></button><a class="enlace" href="/especialidades">Cancelar</a></div>
    </form>
</section>
