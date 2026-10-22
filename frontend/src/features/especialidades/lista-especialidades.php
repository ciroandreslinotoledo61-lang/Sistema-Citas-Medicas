<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Especialidades</h1><p>Organiza los servicios medicos del centro.</p></div>
    <a class="boton" href="/especialidades/nueva">+ Nueva especialidad</a>
</section>
<section class="tarjeta">
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer">Buscar especialidad<input name="busqueda" type="search" maxlength="80" placeholder="Nombre o descripcion"></label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor"><table>
        <caption class="solo-lector">Especialidades registradas</caption>
        <thead><tr><th>Especialidad</th><th>Descripcion</th><th>Medicos activos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody id="filas-especialidades"><tr><td colspan="5">Cargando especialidades...</td></tr></tbody>
    </table></div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
<p class="ayuda margen-superior">Para desactivar una especialidad, primero desactiva o reasigna sus medicos activos.</p>
