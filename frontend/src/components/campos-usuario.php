<div class="rejilla-formulario">
    <label>Nombres <input name="nombresUsuario" required maxlength="80" autocomplete="given-name"></label>
    <label>Apellidos <input name="apellidosUsuario" required maxlength="80" autocomplete="family-name"></label>
    <label class="ancho-completo">Correo electronico <input name="correoUsuario" type="email" required maxlength="160" autocomplete="email"></label>
    <?php if ($mostrarCamposContrasena ?? true): ?>
        <label>Clave de acceso <input name="contrasenaUsuario" type="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="ayuda-clave"></label>
        <label>Repetir clave <input name="confirmacionContrasena" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
        <p class="ayuda ancho-completo" id="ayuda-clave">Usa una frase larga de al menos 12 caracteres.</p>
    <?php endif; ?>
</div>
