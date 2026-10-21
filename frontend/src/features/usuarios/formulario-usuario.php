<a class="enlace-volver" href="/usuarios">&larr; Volver a usuarios</a>
<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACION</p><h1><?= escaparTextoHtml($tituloPagina) ?></h1><p><?= $idRegistro ? 'Actualiza el nombre y el correo de la cuenta.' : 'Crea una cuenta para Administracion o Recepcion.' ?></p></div></section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-usuario" method="post">
        <fieldset id="campos-usuario" <?= $idRegistro ? 'disabled' : '' ?>><legend>Datos de acceso</legend>
        <?php $mostrarCamposContrasena = $idRegistro === 0; require dirname(__DIR__, 2) . '/components/campos-usuario.php'; ?>
        <?php if ($idRegistro === 0): ?>
        <label>Rol de acceso<select name="codigoRol" required><option value="">Selecciona un rol</option><option value="administrador">Administrador</option><option value="recepcionista">Recepcionista</option></select></label>
        <p class="ayuda">Para crear una cuenta de paciente, usa el modulo Pacientes.</p>
        <?php else: ?>
        <p class="ayuda">El rol y la clave de acceso no se modifican desde este formulario.</p>
        <?php endif; ?>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit" id="guardar-usuario" <?= $idRegistro ? 'disabled' : '' ?>><?= $idRegistro ? 'Guardar cambios' : 'Registrar usuario' ?></button><a class="enlace" href="/usuarios">Cancelar</a></div>
    </form>
</section>
