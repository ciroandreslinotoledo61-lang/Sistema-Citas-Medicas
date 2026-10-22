<?php
$codigoRol = $usuarioAutenticado['codigoRol'];
$puedeRegistrar = in_array($codigoRol, ['administrador', 'recepcionista'], true);
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fechaPanel = date('d') . ' ' . $meses[(int) date('n') - 1] . ' ' . date('Y');
?>
<section class="titulo-vista titulo-panel">
    <div>
        <p class="sobretitulo"><?= $puedeRegistrar ? 'VISTA GENERAL' : 'MI ESPACIO' ?></p>
        <h1>Panel principal</h1>
        <p>Hola, <?= escaparTextoHtml($usuarioAutenticado['nombresUsuario']) ?>. Bienvenido a tu espacio de <?= $puedeRegistrar ? 'trabajo' : 'la clinica' ?>.</p>
    </div>
    <span class="fecha-panel"><?= mostrarIcono('calendario') ?><time datetime="<?= date('Y-m-d') ?>"><?= escaparTextoHtml($fechaPanel) ?></time></span>
</section>
<div class="rejilla-panel">
    <div class="contenido-panel">
        <?php if ($modulosDisponibles !== []): ?>
        <section aria-labelledby="titulo-directorio">
            <div class="cabecera-seccion"><h2 id="titulo-directorio"><?= $puedeRegistrar ? 'Directorio de la clinica' : 'Tu informacion' ?></h2><span>Accesos disponibles</span></div>
            <div class="rejilla-modulos">
                <?php foreach ($modulosDisponibles as $modulo): ?>
                <a class="tarjeta-modulo" href="<?= escaparTextoHtml($modulo['ruta']) ?>">
                    <span class="icono-modulo"><?= mostrarIcono($modulo['icono']) ?></span>
                    <h3><?= escaparTextoHtml($modulo['titulo']) ?></h3>
                    <p><?= escaparTextoHtml($modulo['descripcion']) ?></p>
                    <span class="pie-modulo"><?= $codigoRol === 'paciente' ? 'Consultar mi perfil' : 'Ver registros' ?> <?= mostrarIcono('flecha') ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="tarjeta aviso-medico">
            <span class="icono-modulo"><?= mostrarIcono('medicos') ?></span>
            <h2>Tu acceso medico esta habilitado</h2>
            <p class="texto-secundario">La agenda y el registro de consultas aun no estan disponibles.</p>
            <p class="ayuda">Puedes consultar los datos de tu cuenta en este panel. Para corregirlos, comunicate con Administracion.</p>
        </section>
        <?php endif; ?>
        <?php if ($puedeRegistrar): ?>
        <section class="acciones-panel" aria-labelledby="titulo-acciones">
            <div class="cabecera-seccion"><h2 id="titulo-acciones">Nuevo registro</h2></div>
            <div class="lista-acciones">
                <?php foreach ($modulosDisponibles as $modulo): ?>
                <a href="<?= escaparTextoHtml($modulo['rutaRegistro']) ?>"><span><?= mostrarIcono('mas') ?><?= escaparTextoHtml($modulo['accionRegistro']) ?></span><?= mostrarIcono('flecha') ?></a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php elseif ($codigoRol === 'paciente'): ?>
        <p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Para actualizar tus datos de contacto, comunicate con recepcion.</span></p>
        <?php endif; ?>
    </div>
    <aside class="resumen-panel" aria-label="Informacion de la cuenta">
        <section class="tarjeta tarjeta-perfil">
            <p class="sobretitulo">SESION ACTUAL</p>
            <span class="avatar avatar-grande" aria-hidden="true"><?= escaparTextoHtml(mb_strtoupper(mb_substr($usuarioAutenticado['nombresUsuario'], 0, 1))) ?></span>
            <h2><?= escaparTextoHtml($usuarioAutenticado['nombresUsuario'] . ' ' . $usuarioAutenticado['apellidosUsuario']) ?></h2>
            <p class="rol-perfil"><?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></p>
            <p class="correo-perfil"><?= escaparTextoHtml($usuarioAutenticado['correoUsuario']) ?></p>
            <span class="insignia activa">Cuenta activa</span>
        </section>
        <div class="nota-panel">
            <?= mostrarIcono('candado') ?>
            <div><strong>Tu sesion es personal</strong><p>Al terminar, cierra sesion si compartes este equipo.</p></div>
        </div>
    </aside>
</div>
