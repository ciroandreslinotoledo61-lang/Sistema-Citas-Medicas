<div class="rejilla-formulario">
    <label><span>Nombres <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="nombresUsuario" required maxlength="80" autocomplete="given-name"></label>
    <label><span>Apellidos <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="apellidosUsuario" required maxlength="80" autocomplete="family-name"></label>
    <label class="ancho-completo"><span>Correo electronico <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="correoUsuario" type="email" required maxlength="160" autocomplete="email" placeholder="nombre@correo.com"></label>
    <?php if ($mostrarCamposContrasena ?? true): ?>
        <div>
            <label for="clave-registro">Clave de acceso <span class="campo-obligatorio" aria-hidden="true">*</span></label>
            <div class="campo-clave">
                <input id="clave-registro" name="contrasenaUsuario" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="ayuda-clave">
                <button type="button" data-alternar-clave aria-controls="clave-registro" aria-pressed="false" aria-label="Mostrar clave de acceso" hidden>Mostrar</button>
            </div>
        </div>
        <div>
            <label for="confirmacion-registro">Repetir clave <span class="campo-obligatorio" aria-hidden="true">*</span></label>
            <div class="campo-clave">
                <input id="confirmacion-registro" name="confirmacionContrasena" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="ayuda-clave">
                <button type="button" data-alternar-clave aria-controls="confirmacion-registro" aria-pressed="false" aria-label="Mostrar confirmacion de clave" hidden>Mostrar</button>
            </div>
        </div>
        <p class="ayuda ancho-completo" id="ayuda-clave">Usa una frase larga de al menos 12 caracteres, hasta 72 bytes. Los acentos pueden ocupar mas de un byte.</p>
    <?php endif; ?>
</div>
