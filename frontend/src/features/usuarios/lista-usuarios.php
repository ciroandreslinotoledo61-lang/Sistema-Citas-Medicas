<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Usuarios</h1><p>Administra las cuentas de acceso al centro medico.</p></div>
    <a class="boton" href="/usuarios/nuevo">+ Nuevo usuario</a>
</section>
<section class="tarjeta">
    <form id="formulario-busqueda" class="barra-busqueda" method="get"><label class="crecer">Buscar usuario<input name="busqueda" type="search" maxlength="80" placeholder="Nombre, apellido o correo"></label><button class="boton boton-secundario" type="submit">Buscar</button></form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor"><table><caption class="solo-lector">Cuentas registradas</caption><thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="filas-usuarios"><tr><td colspan="5">Cargando usuarios...</td></tr></tbody></table></div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
