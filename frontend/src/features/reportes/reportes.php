<section class="titulo-vista"><div><p class="sobretitulo">ADMINISTRACION</p><h1>Reportes administrativos</h1><p>Consulta las citas, la actividad de los medicos y los pacientes registrados.</p></div></section>
<section class="tarjeta tarjeta-listado" aria-label="Filtros del reporte">
<div class="cabecera-listado"><h2>Periodo y filtros</h2></div>
<form id="formulario-reportes" class="barra-busqueda filtros-reportes" method="get">
<label>Desde<input name="fechaDesde" type="date" required value="<?= date('Y-m-01') ?>"></label>
<label>Hasta<input name="fechaHasta" type="date" required value="<?= date('Y-m-d') ?>"></label>
<label>Medico<select name="idMedico"><option value="">Todos los medicos</option></select></label>
<label>Especialidad<select name="idEspecialidad"><option value="">Todas las especialidades</option></select></label>
<label>Estado<select name="estado"><option value="">Todos los estados</option><option value="pendiente">Programada</option><option value="confirmada">Confirmada</option><option value="atendida">Atendida</option><option value="cancelada">Cancelada</option><option value="no_asistio">Inasistencia</option><option value="reprogramada">Reprogramada</option></select></label>
<button class="boton" type="submit">Consultar reporte</button></form></section>
<p id="mensaje-reporte" class="mensaje margen-superior" role="status">Cargando reporte...</p>
<div id="contenido-reporte" hidden>
<p id="periodo-reporte" class="nota-informativa" aria-live="polite"></p>
<div class="totales-reporte margen-superior" aria-label="Totales del periodo">
<?php foreach (['total'=>'Total de citas','programadas'=>'Programadas','confirmadas'=>'Confirmadas','atendidas'=>'Atendidas','canceladas'=>'Canceladas','inasistencias'=>'Inasistencias','reprogramadas'=>'Reprogramadas','pacientesRegistrados'=>'Pacientes registrados'] as $clave=>$nombre): ?>
<section class="tarjeta total-reporte"><h2><?= escaparTextoHtml($nombre) ?></h2><strong id="total-<?= escaparTextoHtml($clave) ?>">--</strong></section>
<?php endforeach; ?></div>
<div class="grupos-reporte margen-superior">
<?php foreach (['medico'=>'Citas por medico','especialidad'=>'Citas por especialidad','estado'=>'Citas por estado'] as $grupo=>$nombre): ?>
<section class="tarjeta tarjeta-listado"><div class="cabecera-listado"><h2><?= escaparTextoHtml($nombre) ?></h2></div>
<div class="tabla-contenedor" tabindex="0" role="region" aria-label="<?= escaparTextoHtml($nombre) ?>"><table class="tabla-resumen-reporte"><caption class="solo-lector"><?= escaparTextoHtml($nombre) ?></caption><thead><tr><th scope="col">Detalle</th><th scope="col">Cantidad</th></tr></thead><tbody id="reporte-<?= escaparTextoHtml($grupo) ?>"></tbody></table></div></section>
<?php endforeach; ?></div>
<?php foreach (['citas'=>['Agenda del periodo',['Cita','Fecha y hora','Paciente','Medico / Especialidad','Estado']], 'pacientes'=>['Pacientes registrados',['Paciente','CI','Fecha de registro','Estado','Accion']]] as $tabla=>[$titulo,$columnas]): ?>
<section class="tarjeta tarjeta-listado margen-superior"><div class="cabecera-listado"><h2><?= escaparTextoHtml($titulo) ?></h2></div>
<?php if ($tabla==='pacientes'): ?><p class="explicacion-reporte">Este listado usa solo el periodo de registro del paciente. Los filtros de medico, especialidad y estado solo afectan los reportes de citas.</p><?php endif; ?>
<div class="tabla-contenedor" tabindex="0" role="region" aria-label="<?= escaparTextoHtml($titulo) ?>. Desplaza horizontalmente para ver todas las columnas.">
<table><caption class="solo-lector"><?= escaparTextoHtml($titulo) ?></caption><thead><tr><?php foreach ($columnas as $columna): ?><th scope="col"><?= escaparTextoHtml($columna) ?></th><?php endforeach; ?></tr></thead><tbody id="reporte-<?= escaparTextoHtml($tabla) ?>"></tbody></table></div>
<div class="paginacion"><span class="nota-paginacion">Los totales incluyen todas las paginas.</span><div class="controles-paginacion"><button type="button" class="boton boton-secundario boton-pequeno" id="<?= escaparTextoHtml($tabla) ?>-anterior">Anterior</button><span id="<?= escaparTextoHtml($tabla) ?>-pagina" aria-live="polite"></span><button type="button" class="boton boton-secundario boton-pequeno" id="<?= escaparTextoHtml($tabla) ?>-siguiente">Siguiente</button></div></div></section>
<?php endforeach; ?></div>
