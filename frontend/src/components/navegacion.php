<?php if ($esVistaPrivada): ?>
<div class="fondo-navegacion" id="fondo-navegacion" hidden></div>
<aside class="navegacion-lateral" id="navegacion-lateral" aria-label="Menu principal">
    <div class="cabecera-lateral">
        <a class="marca" href="/panel" aria-label="Clinica, inicio">
            <span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span>
            <span>Clinica<small>Centro medico</small></span>
        </a>
        <button class="boton-icono cerrar-navegacion" id="cerrar-navegacion" type="button" aria-label="Cerrar menu"><?= mostrarIcono('cerrar') ?></button>
    </div>
    <p class="etiqueta-menu">PRINCIPAL</p>
    <nav class="menu-lateral" aria-label="Navegacion principal">
        <a href="/panel" <?= $paginaActual === 'panel' ? 'aria-current="page"' : '' ?>><?= mostrarIcono('panel') ?><span>Panel principal</span></a>
        <?php if ($modulosDisponibles !== []): ?><span class="etiqueta-menu">GESTION</span><?php endif; ?>
        <?php foreach ($modulosDisponibles as $modulo): ?>
        <a href="<?= escaparTextoHtml($modulo['ruta']) ?>" <?= str_starts_with($paginaActual, $modulo['prefijo']) ? 'aria-current="page"' : '' ?>>
            <?= mostrarIcono($modulo['icono']) ?><span><?= escaparTextoHtml($modulo['titulo']) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="pie-lateral">
        <div class="usuario-lateral">
            <span class="avatar" aria-hidden="true"><?= escaparTextoHtml(mb_strtoupper(mb_substr($usuarioAutenticado['nombresUsuario'], 0, 1))) ?></span>
            <div><strong><?= escaparTextoHtml($usuarioAutenticado['nombresUsuario']) ?></strong><span><?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></span></div>
        </div>
        <button class="boton-salir" type="button" id="boton-cerrar-sesion"><?= mostrarIcono('salir') ?> Cerrar sesion</button>
    </div>
</aside>
<?php else: ?>
<header class="cabecera-publica">
    <div class="contenedor barra-navegacion">
        <a class="marca" href="/" aria-label="Clinica, inicio"><span class="marca-simbolo"><?= mostrarIcono('clinica') ?></span><span>Clinica<small>Centro medico</small></span></a>
        <nav class="menu-publico" aria-label="Navegacion principal">
            <a href="/iniciar-sesion" <?= $paginaActual === 'iniciar-sesion' ? 'aria-current="page"' : '' ?>>Iniciar sesion</a>
            <a class="boton boton-secundario boton-pequeno" href="/registro-paciente" <?= $paginaActual === 'registro-paciente' ? 'aria-current="page"' : '' ?>>Crear cuenta</a>
        </nav>
    </div>
</header>
<?php endif; ?>
