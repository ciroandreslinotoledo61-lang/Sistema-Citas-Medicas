<a class="enlace-volver" href="/recetas">&larr; Mis recetas</a>
<section class="titulo-vista"><div><p class="sobretitulo">DOCUMENTO CLÍNICO</p><h1>Receta médica</h1><p>El PDF no incluye firma digital certificada.</p></div><button class="boton" id="descargar-receta" disabled>Descargar PDF</button></section>
<p id="mensaje-detalle" class="mensaje" role="status" hidden></p><section class="tarjeta" id="detalle-receta" hidden><dl class="datos-paciente" id="resumen-receta"></dl><div id="medicamentos-emitidos"></div><a id="consulta-receta" class="enlace">Ver consulta e historial</a></section>
<?php if($usuarioAutenticado['codigoRol']==='medico'): ?>
<form id="formulario-anular-receta" class="tarjeta margen-superior" hidden><h2>Anular receta</h2><p>La receta original se conserva. Después puedes emitir una corrección enlazada.</p><label>Motivo obligatorio<textarea name="motivo" required maxlength="500"></textarea></label><p id="mensaje-formulario" class="mensaje" role="status" hidden></p><button class="boton boton-peligro" type="submit">Anular receta</button></form>
<a id="corregir-receta" class="boton margen-superior" hidden>Emitir corrección de esta receta anulada</a>
<?php endif; ?>
