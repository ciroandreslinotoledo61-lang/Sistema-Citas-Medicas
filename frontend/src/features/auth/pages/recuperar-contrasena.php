<section class="titulo-vista">
    <div><p class="sobretitulo">RECUPERAR ACCESO</p><h1>¿Olvidaste tu contraseña?</h1><p>Solicita un enlace para volver a entrar a tu cuenta.</p></div>
</section>
<section class="tarjeta formulario-ancho">
    <form id="formulario-recuperar-contrasena" method="post">
        <label for="correo-recuperacion">Correo electrónico registrado</label>
        <input id="correo-recuperacion" name="correoUsuario" type="email" required maxlength="160" autocomplete="email" placeholder="nombre@correo.com">
        <p class="ayuda">El enlace dura 30 minutos y solo puede utilizarse una vez. Nunca te pediremos que envíes tu contraseña por correo.</p>
        <p id="mensaje-formulario" class="mensaje" role="status" tabindex="-1" hidden></p>
        <div class="acciones">
            <button class="boton" type="submit" disabled>Enviar enlace de recuperación</button>
            <a class="enlace" href="/iniciar-sesion">Volver a iniciar sesión</a>
        </div>
    </form>
</section>
