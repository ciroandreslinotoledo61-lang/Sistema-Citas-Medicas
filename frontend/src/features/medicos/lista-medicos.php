<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Medicos</h1><p>Gestiona el equipo medico y sus especialidades.</p></div>
    <a class="boton" href="/medicos/nuevo">+ Registrar medico</a>
</section>
<section class="tarjeta">
    <form id="formulario-busqueda" class="barra-busqueda" method="get">
        <label class="crecer">Buscar medico<input name="busqueda" type="search" maxlength="80" placeholder="Nombre, correo, matricula o especialidad"></label>
        <button class="boton boton-secundario" type="submit">Buscar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor"><table>
        <caption class="solo-lector">Medicos registrados</caption>
        <thead><tr><th>Medico</th><th>Matricula</th><th>Especialidad</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody id="filas-medicos"><tr><td colspan="5">Cargando medicos...</td></tr></tbody>
    </table></div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
<p class="ayuda margen-superior">Desactivar un medico bloquea el acceso de su cuenta y cierra sus sesiones en la siguiente solicitud.</p>
