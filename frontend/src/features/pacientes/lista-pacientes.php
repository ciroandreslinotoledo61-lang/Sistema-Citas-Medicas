<section class="titulo-vista">
    <div><p class="sobretitulo">RECEPCION</p><h1>Pacientes</h1><p>Consulta los registros y actualiza los datos personales y de contacto.</p></div>
    <a class="boton" href="/pacientes/nuevo"><?= mostrarIcono('mas') ?> Registrar paciente</a>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de pacientes">
    <div class="cabecera-listado"><h2>Pacientes registrados</h2><span class="texto-secundario">Directorio</span></div>
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer" for="busqueda-listado"><span class="solo-lector">Buscar paciente</span>
            <span class="campo-busqueda"><?= mostrarIcono('buscar') ?><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="Nombre, apellido o CI"></span>
        </label>
        <label>Estado<select name="estado"><option value="">Todos</option><option value="activo">Activos</option><option value="inactivo">Inactivos</option></select></label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Pacientes registrados. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Pacientes registrados</caption>
            <thead><tr><th scope="col">Paciente</th><th scope="col">CI</th><th scope="col">Telefono</th><th scope="col">Historia</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-pacientes"><tr><td colspan="6" class="sin-resultados">Cargando pacientes...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
