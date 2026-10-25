<a class="enlace-volver" href="/citas">&larr; Volver a citas</a>
<section class="titulo-vista"><div><p class="sobretitulo">CITA MEDICA</p><h1>Detalle de la cita</h1><p>Informacion de la reserva y acciones disponibles.</p></div></section>
<p id="mensaje-detalle" class="mensaje" role="status" hidden></p>
<section class="tarjeta formulario-ancho" id="detalle-cita" aria-busy="true">
    <div class="cabecera-detalle"><h2 id="titulo-cita">Cargando cita...</h2><span id="estado-cita"></span></div>
    <dl class="datos-paciente" id="datos-cita"></dl>
    <a class="enlace" id="enlace-cita-origen" hidden></a>
    <p id="explicacion-acciones" class="nota-informativa"></p>
    <div class="acciones" id="acciones-cita" hidden>
        <a class="boton boton-secundario" id="reprogramar-cita" hidden>Reprogramar</a>
        <button class="boton boton-peligro" type="button" id="mostrar-anulacion" hidden>Cancelar cita</button>
        <button class="boton" type="button" id="registrar-asistencia" hidden>Marcar atendida</button>
        <button class="boton boton-secundario" type="button" id="registrar-inasistencia" hidden>Registrar inasistencia</button>
    </div>
    <p id="resultado-reprogramacion" class="nota-informativa" hidden></p>
    <div class="acciones margen-superior" id="acciones-clinicas" hidden><a class="boton" id="registrar-consulta" hidden>Registrar consulta</a><a class="boton" id="ver-consulta" hidden>Ver consulta</a><a class="boton boton-secundario" id="ver-historia" hidden>Historial clinico</a></div>
    <form id="formulario-anulacion" class="separacion-formulario" method="post" hidden>
        <h3>Cancelar esta cita</h3><p>El turno quedara libre. La cita cancelada se conserva en el historial.</p>
        <dl class="datos-paciente" id="resumen-cancelacion"></dl>
        <label><span id="etiqueta-motivo-cancelacion">Motivo de cancelacion</span><textarea name="motivoCancelacion" rows="2" maxlength="300"></textarea></label>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton boton-peligro" type="submit">Confirmar cancelacion</button><button class="boton boton-secundario" id="mantener-cita" type="button">Volver</button></div>
    </form>
</section>
<section class="tarjeta margen-superior" aria-labelledby="titulo-historial-cambios">
    <h2 id="titulo-historial-cambios">Historial de cambios</h2>
    <p class="ayuda">Responsable y momento de cada accion registrada desde esta actualizacion.</p>
    <ol id="historial-cambios" class="historial-cambios"></ol>
</section>
