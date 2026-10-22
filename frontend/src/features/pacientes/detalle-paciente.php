<?php $esPerfilPropio = $paginaActual === 'mi-perfil'; ?>
<a class="enlace-volver" href="<?= $esPerfilPropio ? '/panel' : '/pacientes' ?>">&larr; Volver</a>
<section class="titulo-vista"><div><p class="sobretitulo"><?= $esPerfilPropio ? 'MI CUENTA' : 'RECEPCION' ?></p><h1><?= escaparTextoHtml($tituloPagina) ?></h1><p><?= $esPerfilPropio ? 'Revisa la informacion de tu registro.' : 'Consulta el registro y actualiza sus datos de contacto.' ?></p></div></section>
<p id="mensaje-detalle" class="mensaje" role="status" hidden></p>
<section class="tarjeta formulario-ancho"><h2 id="nombre-paciente">Cargando paciente...</h2><dl class="datos-paciente" id="datos-paciente"></dl></section>
<?php if (!$esPerfilPropio): ?>
<section class="tarjeta formulario-ancho margen-superior">
    <h2>Actualizar contacto</h2>
    <p class="ayuda-obligatorios">Los campos con <span class="campo-obligatorio" aria-hidden="true">*</span> son obligatorios.</p>
    <form id="formulario-contacto-paciente" method="post">
        <fieldset id="campos-contacto" disabled><legend class="solo-lector">Contacto del paciente</legend>
            <?php require dirname(__DIR__, 2) . '/components/campos-contacto.php'; ?>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <button class="boton" type="submit" id="guardar-contacto" disabled>Guardar cambios</button>
    </form>
</section>
<?php else: ?>
<p class="ayuda margen-superior">Si algun dato necesita corregirse, comunicate con recepcion.</p>
<?php endif; ?>
