<a class="enlace-volver" href="/usuarios">&larr; Volver a usuarios</a>
<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACION</p><h1>Restablecer contrasena</h1><p>Entrega acceso temporal a una cuenta existente.</p></div>
</section>
<section class="tarjeta formulario-ancho">
    <h2>Cuenta seleccionada</h2>
    <p id="estado-cuenta" class="ayuda" role="status">Cargando cuenta...</p>
    <dl id="datos-cuenta" class="datos-paciente" hidden>
        <div><dt>Nombre</dt><dd id="nombre-cuenta"></dd></div>
        <div><dt>Correo</dt><dd id="correo-cuenta"></dd></div>
        <div><dt>Rol</dt><dd id="rol-cuenta"></dd></div>
        <div><dt>Estado</dt><dd id="estado-usuario"></dd></div>
    </dl>
    <p class="mensaje margen-superior">La contrasena temporal caducara en 30 minutos y permite un solo inicio de sesion. La persona debera elegir una nueva antes de usar el sistema. Se cerraran sus sesiones anteriores.</p>
    <form id="formulario-restablecer-contrasena" method="post" autocomplete="off">
        <fieldset id="campos-restablecimiento" disabled><legend>Confirmar restablecimiento</legend>
            <button id="generar-temporal" class="boton boton-secundario" type="button">Generar contrasena temporal</button>
            <div id="area-temporal" class="margen-superior" hidden>
                <label for="contrasena-temporal">Contrasena temporal</label>
                <div class="campo-clave">
                    <input id="contrasena-temporal" type="password" readonly autocomplete="off" spellcheck="false" aria-describedby="ayuda-temporal">
                    <button type="button" data-alternar-clave aria-controls="contrasena-temporal" aria-pressed="false" aria-label="Mostrar contrasena temporal" hidden>Mostrar</button>
                </div>
                <p id="ayuda-temporal" class="ayuda">Guarda esta contrasena para entregarla de forma privada a la persona despues de confirmar el restablecimiento. Al terminar dejara de mostrarse. No la envies por enlaces ni la publiques.</p>
                <label class="confirmacion-restablecimiento"><input id="confirmar-restablecimiento" type="checkbox" required><span>He comprobado la cuenta y guardado la contrasena temporal. Confirmo el restablecimiento y el cierre de sus sesiones.</span></label>
            </div>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones"><button class="boton" type="submit" disabled>Restablecer contrasena</button><a class="enlace" href="/usuarios">Volver a usuarios</a></div>
    </form>
</section>
