<?php $esAdministrador = $usuarioAutenticado['codigoRol'] === 'administrador'; ?>
<section class="titulo-vista"><div><p class="sobretitulo">DISPONIBILIDAD</p><h1><?= $esAdministrador ? 'Horarios de atención' : 'Mis horarios' ?></h1><p><?= $esAdministrador ? 'Configura bloques de atención y genera turnos automáticamente.' : 'Consulta los bloques de atención asignados por Administración.' ?></p></div><?php if ($esAdministrador): ?><a class="boton" href="/horarios/nuevo"><?= mostrarIcono('mas') ?> Programar horarios</a><?php endif; ?></section>
<section class="tarjeta tarjeta-listado" aria-label="Horarios de atención">
    <div class="cabecera-listado"><h2>Bloques registrados</h2></div>
    <form id="formulario-busqueda" class="barra-busqueda filtros-agenda" method="get">
        <label class="crecer">Buscar médico<input name="busqueda" type="search" maxlength="80" placeholder="Nombre o apellido"></label>
        <label>Fecha<input name="fecha" type="date"></label><button class="boton boton-secundario" type="submit">Filtrar</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" role="region" aria-label="Horarios. Desplaza horizontalmente para ver todas las columnas.">
        <table><caption class="solo-lector">Horarios registrados</caption><thead><tr><th scope="col">Médico</th><th scope="col">Fecha</th><th scope="col">Desde</th><th scope="col">Hasta</th><th scope="col">Turnos generados</th></tr></thead><tbody id="filas-horarios"><tr><td colspan="5" class="sin-resultados">Cargando horarios...</td></tr></tbody></table>
    </div>
    <?php require dirname(__DIR__, 3) . '/components/paginacion.php'; ?>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>La cantidad indica los turnos habilitados del bloque, incluidos los reservados. Las citas anteriores conservan sus horas.</span></p>
