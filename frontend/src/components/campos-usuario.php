<div class="rejilla-formulario">
    <label><span>Nombres <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="nombresUsuario" required maxlength="80" autocomplete="given-name"></label>
    <label><span>Apellidos <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="apellidosUsuario" required maxlength="80" autocomplete="family-name"></label>
    <label class="ancho-completo"><span>Correo electronico <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="correoUsuario" type="email" required maxlength="160" autocomplete="email" placeholder="nombre@correo.com"></label>
    <?php if ($mostrarCamposContrasena ?? true): ?>
        <label><span>Clave de acceso <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="contrasenaUsuario" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="ayuda-clave"></label>
        <label><span>Repetir clave <span class="campo-obligatorio" aria-hidden="true">*</span></span><input name="confirmacionContrasena" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="ayuda-clave"></label>
        <p class="ayuda ancho-completo" id="ayuda-clave">Usa una frase larga de al menos 12 caracteres.</p>
    <?php endif; ?>
</div>
