<section class="titulo-vista"><div><p class="sobretitulo">HISTORIAL CLÍNICO</p><h1>Mis recetas médicas</h1><p>Documentos emitidos y anulados. Accede a la consulta para emitir una receta.</p></div></section>
<section class="tarjeta tarjeta-listado">
<form id="formulario-busqueda" class="barra-busqueda"><p>Se muestran únicamente las recetas autorizadas para tu cuenta.</p><button class="boton boton-secundario" type="submit">Actualizar</button></form>
<p id="mensaje-listado" class="mensaje" role="status" hidden></p>
<div class="tabla-contenedor"><table><thead><tr><th>Receta</th><th>Consulta</th><th>Emisión</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="filas-recetas"></tbody></table></div>
<?php require dirname(__DIR__,3).'/components/paginacion.php'; ?>
</section>
