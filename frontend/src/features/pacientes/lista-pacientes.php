<section class="titulo-vista"><div><p class="sobretitulo">RECEPCION</p><h1>Pacientes</h1><p>Encuentra sus datos y mantente en contacto.</p></div><a class="boton" href="/pacientes/nuevo">+ Registrar paciente</a></section>
<section class="tarjeta">
    <form id="formulario-busqueda" class="barra-busqueda" method="get"><label class="crecer">Buscar paciente<input name="busqueda" type="search" maxlength="80" placeholder="Nombre, apellido o CI"></label><button class="boton boton-secundario" type="submit">Buscar</button></form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor"><table><caption class="solo-lector">Pacientes registrados</caption><thead><tr><th>Paciente</th><th>CI</th><th>Telefono</th><th>Historia</th><th>Acciones</th></tr></thead><tbody id="filas-pacientes"><tr><td colspan="5">Cargando pacientes...</td></tr></tbody></table></div>
    <?php require dirname(__DIR__, 2) . '/components/paginacion.php'; ?>
</section>
