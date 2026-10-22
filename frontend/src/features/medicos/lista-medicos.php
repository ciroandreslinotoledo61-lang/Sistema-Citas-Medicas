<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Medicos</h1><p>Gestiona el equipo medico y sus especialidades.</p></div>
    <a class="boton" href="/medicos/nuevo"><?= mostrarIcono('mas') ?> Registrar medico</a>
</section>
<section class="tarjeta tarjeta-listado" aria-label="Listado de medicos">
    <div class="cabecera-listado"><h2>Medicos registrados</h2><span class="texto-secundario">Directorio</span></div>
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer" for="busqueda-listado"><span class="solo-lector">Buscar medico</span>
            <span class="campo-busqueda"><?= mostrarIcono('buscar') ?><input id="busqueda-listado" name="busqueda" type="search" maxlength="80" placeholder="Nombre, correo, matricula o especialidad"></span>
        </label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Medicos registrados. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Medicos registrados</caption>
            <thead><tr><th scope="col">Medico</th><th scope="col">Matricula</th><th scope="col">Especialidad</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody id="filas-medicos"><tr><td colspan="5" class="sin-resultados">Cargando medicos...</td></tr></tbody>
        </table>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Al desactivar un medico, su cuenta pierde el acceso al sistema.</span></p>
