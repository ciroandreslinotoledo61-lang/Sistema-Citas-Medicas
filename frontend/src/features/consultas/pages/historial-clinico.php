<?php $esHistorialPropio = ($usuarioAutenticado['codigoRol'] ?? '') === 'paciente'; ?>
<section class="titulo-vista"><div><p class="sobretitulo">CONTINUIDAD DE ATENCIÓN</p>
<h1><?= $esHistorialPropio ? 'Mi historial clínico' : 'Historial clínico' ?></h1>
<p><?= $esHistorialPropio ? 'Consulta tus antecedentes y atenciones médicas.' : 'Antecedentes y atenciones del paciente autorizado.' ?></p></div></section>
<section class="tarjeta" id="historia-clinica" hidden>
    <h2 id="nombre-paciente"></h2><dl class="datos-paciente" id="antecedentes-paciente"></dl>
    <?php if (!$esHistorialPropio): ?>
    <details id="editar-antecedentes" hidden><summary>Actualizar antecedentes</summary><form id="formulario-historia">
        <label>Tipo de sangre<select name="tipoSangre"><option value="">Sin registrar</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></label>
        <label>Alergias<textarea name="alergias" maxlength="5000" rows="3"></textarea></label>
        <label>Antecedentes<textarea name="antecedentes" maxlength="5000" rows="3"></textarea></label>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p><button type="submit" class="boton">Guardar antecedentes</button>
    </form></details>
    <?php endif; ?>
</section>
<section class="tarjeta margen-superior">
    <h2>Atenciones registradas</h2>
    <form id="formulario-busqueda" class="filtros">
        <label>Desde<input type="date" name="fechaDesde"></label><label>Hasta<input type="date" name="fechaHasta"></label>
        <button class="boton" type="submit">Filtrar por fecha</button>
    </form>
    <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
    <div class="tabla-contenedor" tabindex="0" aria-label="Atenciones médicas registradas"><table>
        <thead><tr><th>Fecha</th><?php if (!$esHistorialPropio): ?><th>Paciente</th><?php endif; ?><th>Médico</th><?php if (!$esHistorialPropio): ?><th>Cita</th><?php endif; ?><th>Acciones</th></tr></thead>
        <tbody id="filas-consultas"></tbody>
    </table></div>
    <?php require dirname(__DIR__,3).'/components/paginacion.php'; ?>
</section>
