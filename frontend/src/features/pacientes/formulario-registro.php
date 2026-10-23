<?php $registroPublico = $usuarioAutenticado === null; ?>
<?php if (!$registroPublico): ?><a class="enlace-volver" href="/pacientes">&larr; Volver a pacientes</a><?php endif; ?>
<section class="titulo-vista"><div><p class="sobretitulo"><?= $registroPublico ? 'REGISTRO DE PACIENTE' : 'RECEPCION' ?></p><h1><?= $registroPublico ? 'Crear cuenta' : 'Registrar paciente' ?></h1><p>Completa los datos para abrir el perfil del paciente.</p></div></section>
<section class="tarjeta formulario-ancho">
    <p class="ayuda-obligatorios">Los campos con <span class="campo-obligatorio" aria-hidden="true">*</span> son obligatorios.</p>
    <form id="formulario-registro-paciente" method="post">
        <fieldset><legend><span>01</span> Datos de acceso</legend>
            <?php $mostrarCamposContrasena = true; require dirname(__DIR__, 2) . '/components/campos-usuario.php'; ?>
        </fieldset>
        <fieldset><legend><span>02</span> Datos personales</legend><?php require dirname(__DIR__, 2) . '/components/campos-personales-paciente.php'; ?></fieldset>
        <fieldset><legend><span>03</span> Datos de contacto</legend>
            <?php require dirname(__DIR__, 2) . '/components/campos-contacto.php'; ?>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton" type="submit"><?= $registroPublico ? 'Crear mi cuenta' : 'Registrar paciente' ?></button><a class="enlace" href="<?= $registroPublico ? '/iniciar-sesion' : '/pacientes' ?>"><?= $registroPublico ? 'Ya tengo una cuenta' : 'Cancelar' ?></a></div>
    </form>
</section>
