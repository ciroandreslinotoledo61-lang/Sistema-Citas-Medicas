<header class="cabecera">
<div class="contenedor barra-navegacion">
    <a class="marca" href="<?= $usuarioAutenticado ? '/panel' : '/' ?>" aria-label="Vida Salud, inicio">
        <span class="marca-simbolo" aria-hidden="true">+</span>
        <span>vida<span class="marca-salud">salud</span><small>CENTRO MEDICO</small></span>
    </a>
    <nav aria-label="Navegacion principal">
        <?php if ($usuarioAutenticado): ?>
            <a href="/panel" <?= $paginaActual === 'panel' ? 'aria-current="page"' : '' ?>>Mi panel</a>
            <?php if ($usuarioAutenticado['codigoRol'] === 'administrador'): ?>
                <a href="/usuarios" <?= str_starts_with($paginaActual, 'usuario') ? 'aria-current="page"' : '' ?>>Usuarios</a>
            <?php endif; ?>
            <?php if (in_array($usuarioAutenticado['codigoRol'], ['administrador', 'recepcionista'], true)): ?>
                <a href="/pacientes" <?= str_starts_with($paginaActual, 'paciente') ? 'aria-current="page"' : '' ?>>Pacientes</a>
            <?php endif; ?>
            <?php if ($usuarioAutenticado['codigoRol'] === 'paciente'): ?>
                <a href="/mi-perfil" <?= $paginaActual === 'mi-perfil' ? 'aria-current="page"' : '' ?>>Mi perfil</a>
            <?php endif; ?>
            <button class="boton boton-secundario boton-pequeno" type="button" id="boton-cerrar-sesion">Salir</button>
        <?php else: ?>
            <a href="/iniciar-sesion">Iniciar sesion</a>
            <a class="boton boton-pequeno" href="/registro-paciente">Crear cuenta</a>
        <?php endif; ?>
    </nav>
</div>
<div class="contenedor"><p id="mensaje-global" class="mensaje" role="status" hidden></p></div>
</header>
