<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Usuarios</h1><p>Cuentas de acceso, roles y estado de los usuarios.</p></div>
    <a class="boton" href="/usuarios/nuevo"><?= mostrarIcono('mas') ?> Nuevo usuario</a>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de usuarios">
    <div class="cabecera-listado"><h2>Cuentas registradas</h2><span class="texto-secundario">Directorio</span></div>
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer" for="busqueda-listado"><span class="solo-lector">Buscar usuario</span>
            <span class="campo-busqueda"><?= mostrarIcono('buscar') ?><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="Nombre, apellido o correo"></span>
        </label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Cuentas registradas. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Cuentas registradas</caption>
            <thead><tr><th scope="col">Usuario</th><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-usuarios"><tr><td colspan="5" class="sin-resultados">Cargando usuarios...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
