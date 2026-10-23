<a class="enlace-volver" href="/citas">&larr; Volver a citas</a>
<section class="titulo-vista"><div><p class="sobretitulo">CITA MEDICA</p><h1>Detalle de la cita</h1><p>Informacion de la reserva y acciones disponibles.</p></div></section>
<p id="mensaje-detalle" class="mensaje" role="status" hidden></p>
<section class="tarjeta formulario-ancho" id="detalle-cita" aria-busy="true">
    <div class="cabecera-detalle"><h2 id="titulo-cita">Cargando cita...</h2><span id="estado-cita"></span></div>
    <dl class="datos-paciente" id="datos-cita"></dl>
    <a class="enlace" id="enlace-cita-origen" hidden></a>
    <div class="acciones" id="acciones-cita" hidden>
        <a class="boton boton-secundario" id="reprogramar-cita" hidden>Reprogramar</a>
        <button class="boton boton-peligro" type="button" id="mostrar-anulacion" hidden>Anular cita</button>
        <button class="boton" type="button" id="registrar-asistencia" hidden>Marcar atendida</button>
        <button class="boton boton-secundario" type="button" id="registrar-inasistencia" hidden>Registrar inasistencia</button>
    </div>
    <form id="formulario-anulacion" class="separacion-formulario" method="post" hidden>
        <h3>Anular esta cita</h3><p>El turno quedara libre. La cita se conservara en tu listado como anulada.</p>
        <label>Motivo de anulacion (opcional)<textarea name="motivoCancelacion" rows="2" maxlength="300"></textarea></label>
        <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
        <div class="acciones"><button class="boton boton-peligro" type="submit">Confirmar anulacion</button><button class="boton boton-secundario" id="mantener-cita" type="button">Mantener cita</button></div>
    </form>
</section>
