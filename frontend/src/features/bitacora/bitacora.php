<section class="titulo-vista"><div><p class="sobretitulo">SUPERVISION</p><h1>Bitacora del sistema</h1><p>Actividades, responsables y resultados. Las mas recientes aparecen primero.</p></div></section>
<section class="tarjeta"><form id="formulario-busqueda" class="filtros filtros-bitacora"><label>Buscar<input name="busqueda" maxlength="80" placeholder="Responsable o descripcion"></label>
<label>Usuario<select name="idUsuario"><option value="">Todos</option></select></label><label>Modulo<select name="modulo"><option value="">Todos</option></select></label><label>Accion<select name="accion"><option value="">Todas</option></select></label>
<label>Desde<input type="date" name="fechaDesde"></label><label>Hasta<input type="date" name="fechaHasta"></label><button class="boton" type="submit">Filtrar actividades</button></form>
<p id="mensaje-listado" class="mensaje" role="status" hidden></p><p id="mensaje-filtros" class="mensaje" role="status" hidden></p>
<div class="tabla-contenedor" tabindex="0" aria-label="Actividades del sistema"><table><thead><tr><th>Fecha y hora</th><th>Responsable</th><th>Modulo / registro</th><th>Accion</th><th>Resultado</th><th>Descripcion</th></tr></thead><tbody id="filas-bitacora"></tbody></table></div>
<?php require dirname(__DIR__,2).'/components/paginacion.php'; ?></section>
