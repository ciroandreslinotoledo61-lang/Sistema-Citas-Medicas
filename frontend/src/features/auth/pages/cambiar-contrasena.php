<?php $cambioObligatorio = $usuarioAutenticado['requiereCambioContrasena'] ?? false; ?>
<section class="titulo-vista">
    <div><p class="sobretitulo">MI CUENTA</p><h1>Cambiar contraseña</h1><p>Protege el acceso a tu cuenta personal.</p></div>
</section>
<section class="tarjeta formulario-ancho">
    <?php if ($cambioObligatorio): ?>
    <p class="mensaje">Tu cuenta utiliza una contraseña temporal. Escríbela como contraseña actual y elige una personal para continuar. Completa el cambio antes de que transcurran 30 minutos desde el restablecimiento.</p>
    <?php endif; ?>
    <p class="ayuda">Al guardar se cerrarán tus otras sesiones. Esta sesión permanecerá abierta.</p>
    <form id="formulario-cambiar-contrasena" method="post">
        <fieldset id="campos-contrasena"><legend>Tu contraseña</legend>
            <label for="contrasena-actual">Contraseña actual</label>
            <div class="campo-clave">
                <input id="contrasena-actual" name="contrasenaActual" type="password" required maxlength="72" autocomplete="current-password">
                <button type="button" data-alternar-clave aria-controls="contrasena-actual" aria-pressed="false" aria-label="Mostrar contraseña actual" hidden>Mostrar</button>
            </div>
            <label for="nueva-contrasena">Nueva contraseña</label>
            <div class="campo-clave">
                <input id="nueva-contrasena" name="nuevaContrasena" type="password" required maxlength="72" autocomplete="new-password" aria-describedby="ayuda-nueva-contrasena">
                <button type="button" data-alternar-clave aria-controls="nueva-contrasena" aria-pressed="false" aria-label="Mostrar nueva contraseña" hidden>Mostrar</button>
            </div>
            <p id="ayuda-nueva-contrasena" class="ayuda">Usa una frase larga con letras y números, sin patrones repetidos. Debe ocupar entre 12 y 72 bytes; los acentos pueden ocupar más de uno. Debe ser diferente de la contraseña actual.</p>
            <label for="confirmacion-contrasena">Confirmar nueva contraseña</label>
            <div class="campo-clave">
                <input id="confirmacion-contrasena" name="confirmacionContrasena" type="password" required maxlength="72" autocomplete="new-password">
                <button type="button" data-alternar-clave aria-controls="confirmacion-contrasena" aria-pressed="false" aria-label="Mostrar confirmación de contraseña" hidden>Mostrar</button>
            </div>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones">
            <button class="boton" type="submit" disabled>Guardar contraseña</button>
            <a class="enlace" id="continuar-panel" href="/panel" <?= $cambioObligatorio ? 'hidden' : '' ?>>Volver al panel</a>
        </div>
    </form>
</section>
