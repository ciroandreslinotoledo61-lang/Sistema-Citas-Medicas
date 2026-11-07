<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACIÓN</p><h1>Médicos</h1><p>Gestiona el equipo médico y sus especialidades.</p></div>
    <a class="boton" href="/medicos/nuevo"><?= mostrarIcono('mas') ?> Registrar médico</a>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de médicos">
    <div class="cabecera-listado"><h2>Médicos registrados</h2><span class="texto-secundario">Directorio</span></div>
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer" for="busqueda-listado"><span class="solo-lector">Buscar médico</span>
            <span class="campo-busqueda"><?= mostrarIcono('buscar') ?><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="Nombre, correo, matrícula o especialidad"></span>
        </label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Médicos registrados. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Médicos registrados</caption>
            <thead><tr><th scope="col">Médico</th><th scope="col">Matrícula</th><th scope="col">Especialidad</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-medicos"><tr><td colspan="5" class="sin-resultados">Cargando médicos...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 3) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Al desactivar un médico, su cuenta pierde el acceso al sistema.</span></p>
