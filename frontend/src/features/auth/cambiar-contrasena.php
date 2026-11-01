<?php $cambioObligatorio = $usuarioAutenticado['requiereCambioContrasena'] ?? false; ?>
<section class="titulo-vista">
    <div><p class="sobretitulo">MI CUENTA</p><h1>Cambiar contrasena</h1><p>Protege el acceso a tu cuenta personal.</p></div>
</section>
<section class="tarjeta formulario-ancho">
    <?php if ($cambioObligatorio): ?>
    <p class="mensaje">Tu cuenta utiliza una contrasena temporal. Escribela como contrasena actual y elige una personal para continuar. Completa el cambio antes de que transcurran 30 minutos desde el restablecimiento.</p>
    <?php endif; ?>
    <p class="ayuda">Al guardar se cerraran tus otras sesiones. Esta sesion permanecera abierta.</p>
    <form id="formulario-cambiar-contrasena" method="post">
        <fieldset id="campos-contrasena"><legend>Tu contrasena</legend>
            <label for="contrasena-actual">Contrasena actual</label>
            <div class="campo-clave">
                <input id="contrasena-actual" name="contrasenaActual" type="password" required maxlength="72" autocomplete="current-password">
                <button type="button" data-alternar-clave aria-controls="contrasena-actual" aria-pressed="false" aria-label="Mostrar contrasena actual" hidden>Mostrar</button>
            </div>
            <label for="nueva-contrasena">Nueva contrasena</label>
            <div class="campo-clave">
                <input id="nueva-contrasena" name="nuevaContrasena" type="password" required maxlength="72" autocomplete="new-password" aria-describedby="ayuda-nueva-contrasena">
                <button type="button" data-alternar-clave aria-controls="nueva-contrasena" aria-pressed="false" aria-label="Mostrar nueva contrasena" hidden>Mostrar</button>
            </div>
            <p id="ayuda-nueva-contrasena" class="ayuda">Usa una frase larga con letras y numeros, sin patrones repetidos. Debe ocupar entre 12 y 72 bytes; los acentos pueden ocupar mas de uno. Debe ser diferente de la contrasena actual.</p>
            <label for="confirmacion-contrasena">Confirmar nueva contrasena</label>
            <div class="campo-clave">
                <input id="confirmacion-contrasena" name="confirmacionContrasena" type="password" required maxlength="72" autocomplete="new-password">
                <button type="button" data-alternar-clave aria-controls="confirmacion-contrasena" aria-pressed="false" aria-label="Mostrar confirmacion de contrasena" hidden>Mostrar</button>
            </div>
        </fieldset>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones">
            <button class="boton" type="submit" disabled>Guardar contrasena</button>
            <a class="enlace" id="continuar-panel" href="/panel" <?= $cambioObligatorio ? 'hidden' : '' ?>>Volver al panel</a>
        </div>
    </form>
</section>
