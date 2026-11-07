<section class="titulo-vista">
    <div><p class="sobretitulo">RECUPERAR ACCESO</p><h1>Establecer nueva contraseña</h1><p>Elige una contraseña personal para proteger tu cuenta.</p></div>
</section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-confirmar-recuperacion" method="post">
        <fieldset id="campos-recuperacion"><legend>Tu nueva contraseña</legend>
            <label for="nueva-contrasena">Nueva contraseña</label>
            <div class="campo-clave">
                <input id="nueva-contrasena" name="nuevaContrasena" type="password" required maxlength="72" autocomplete="new-password" aria-describedby="ayuda-nueva-contrasena">
                <button type="button" data-alternar-clave aria-controls="nueva-contrasena" aria-pressed="false" aria-label="Mostrar nueva contraseña" hidden>Mostrar</button>
            </div>
            <p id="ayuda-nueva-contrasena" class="ayuda">Usa una frase larga con letras y números, sin patrones repetidos. Debe ocupar entre 12 y 72 bytes; los acentos pueden ocupar más de uno.</p>
            <label for="confirmacion-contrasena">Confirmar nueva contraseña</label>
            <div class="campo-clave">
                <input id="confirmacion-contrasena" name="confirmacionContrasena" type="password" required maxlength="72" autocomplete="new-password">
                <button type="button" data-alternar-clave aria-controls="confirmacion-contrasena" aria-pressed="false" aria-label="Mostrar confirmación de contraseña" hidden>Mostrar</button>
            </div>
        </fieldset>
        <p class="ayuda">Al guardar se cerrarán todas las sesiones anteriores. Después podrás iniciar sesión con tu nueva contraseña.</p>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones">
            <button class="boton" type="submit" disabled>Guardar nueva contraseña</button>
            <a class="enlace" id="nuevo-enlace-recuperacion" href="/recuperar-contrasena">Solicitar otro enlace</a>
            <a class="enlace" id="continuar-iniciar-sesion" href="/iniciar-sesion" hidden>Iniciar sesión</a>
        </div>
    </form>
</section>
