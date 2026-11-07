<a class="enlace-volver" href="/usuarios">&larr; Volver a usuarios</a>
<section class="titulo-vista">
    <div><p class="sobretitulo">ADMINISTRACIÓN</p><h1>Restablecer contraseña</h1><p>Entrega acceso temporal a una cuenta existente.</p></div>
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
    <p class="mensaje margen-superior">La contraseña temporal caducará en 30 minutos y permite un solo inicio de sesión. La persona deberá elegir una nueva antes de usar el sistema. Se cerrarán sus sesiones anteriores.</p>
    <form id="formulario-restablecer-contrasena" method="post" autocomplete="off">
        <fieldset id="campos-restablecimiento" disabled><legend>Confirmar restablecimiento</legend>
            <button id="generar-temporal" class="boton boton-secundario" type="button">Generar contraseña temporal</button>
            <div id="area-temporal" class="margen-superior" hidden>
                <label for="contrasena-temporal">Contraseña temporal</label>
                <div class="campo-clave">
                    <input id="contrasena-temporal" type="password" readonly autocomplete="off" spellcheck="false" aria-describedby="ayuda-temporal">
                    <button type="button" data-alternar-clave aria-controls="contrasena-temporal" aria-pressed="false" aria-label="Mostrar contraseña temporal" hidden>Mostrar</button>
                </div>
                <p id="ayuda-temporal" class="ayuda">Guarda esta contraseña para entregarla de forma privada a la persona después de confirmar el restablecimiento. Al terminar dejará de mostrarse. No la envíes por enlaces ni la publiques.</p>
                <label class="confirmacion-restablecimiento"><input id="confirmar-restablecimiento" type="checkbox" required><span>He comprobado la cuenta y guardado la contraseña temporal. Confirmo el restablecimiento y el cierre de sus sesiones.</span></label>
            </div>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones"><button class="boton" type="submit" disabled>Restablecer contraseña</button><a class="enlace" href="/usuarios">Volver a usuarios</a></div>
    </form>
</section>
