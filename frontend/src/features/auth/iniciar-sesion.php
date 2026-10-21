<div class="vista-acceso">
    <section class="texto-acceso">
        <p class="sobretitulo">BIENVENIDO DE NUEVO</p>
        <h1>Un lugar para<br>cuidar de ti.</h1>
        <p>Ingresa a tu espacio personal con el correo que usaste al registrarte.</p>
        <div class="nota-acceso"><span class="simbolo-salud" aria-hidden="true">+</span><p>Si necesitas ayuda con tu cuenta, comunicate con recepcion.</p></div>
    </section>
    <section class="tarjeta formulario-acceso">
        <h2>Iniciar sesion</h2>
        <p class="texto-secundario">Escribe tus datos para continuar.</p>
        <form id="formulario-iniciar-sesion" method="post">
            <label>Correo electronico <input name="correoUsuario" type="email" required maxlength="160" autocomplete="username" placeholder="tu@correo.com"></label>
            <label>Clave de acceso <input name="contrasenaUsuario" type="password" required maxlength="72" autocomplete="current-password"></label>
            <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
            <button class="boton ancho-total" type="submit">Ingresar a mi cuenta <span aria-hidden="true">&rarr;</span></button>
        </form>
        <p class="pie-formulario">Primera vez aqui? <a href="/registro-paciente">Crear cuenta de paciente</a></p>
    </section>
</div>
