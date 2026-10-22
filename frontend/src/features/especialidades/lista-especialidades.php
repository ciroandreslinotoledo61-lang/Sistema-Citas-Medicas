<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Especialidades</h1><p>Organiza los servicios medicos de la clinica.</p></div>
    <a class="boton" href="/especialidades/nueva"><?= mostrarIcono('mas') ?> Nueva especialidad</a>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de especialidades">
    <div class="cabecera-listado"><h2>Especialidades registradas</h2><span class="texto-secundario">Directorio</span></div>
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer" for="busqueda-listado"><span class="solo-lector">Buscar especialidad</span>
            <span class="campo-busqueda"><?= mostrarIcono('buscar') ?><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="Nombre o descripcion"></span>
        </label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Especialidades registradas. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Especialidades registradas</caption>
            <thead><tr><th scope="col">Especialidad</th><th scope="col">Descripcion</th><th scope="col">Medicos activos</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-especialidades"><tr><td colspan="5" class="sin-resultados">Cargando especialidades...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Para desactivar una especialidad, primero desactiva o reasigna sus medicos activos.</span></p>
