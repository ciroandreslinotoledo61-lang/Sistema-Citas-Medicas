<section class="titulo-vista"><div><p class="sobretitulo">ATENCIÓN PRESENCIAL</p><h1>Sala de espera</h1><p>Información operativa privada: sin diagnósticos, tratamientos ni motivos clínicos.</p></div></section>
<section class="tarjeta tarjeta-listado"><form id="formulario-busqueda" class="barra-busqueda"><label>Fecha<input type="date" name="fecha" required value="<?= escaparTextoHtml(date('Y-m-d')) ?>"></label><button class="boton boton-secundario" type="submit">Actualizar sala</button></form>
<p id="mensaje-listado" class="mensaje" role="status" hidden></p><div class="tabla-contenedor"><table><thead><tr><th>Cita</th><th>Paciente</th><th>Médico</th><th>Hora</th><th>Sala</th><th>Estado cita</th><th>Acciones</th></tr></thead><tbody id="filas-sala"></tbody></table></div>
<?php require dirname(__DIR__,3).'/components/paginacion.php'; ?></section>
<section class="tarjeta margen-superior" id="historial-sala" hidden><h2>Cambios de sala</h2><ol id="eventos-sala"></ol></section>
