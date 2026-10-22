<div class="vista-acceso">
    <section class="texto-acceso">
        <span class="emblema-acceso" aria-hidden="true"><?= mostrarIcono('clinica') ?></span>
        <div>
            <p class="sobretitulo">PORTAL DE LA CLINICA</p>
            <h2>Un espacio para<br>pacientes y personal.</h2>
            <p>Consulta tu informacion o continua con la gestion del centro medico.</p>
        </div>
        <div class="nota-acceso"><?= mostrarIcono('candado') ?><p>Usa tu cuenta personal.<br>El acceso depende de tu rol.</p></div>
    </section>
    <section class="formulario-acceso">
        <p class="sobretitulo">BIENVENIDO</p>
        <h1>Iniciar sesion</h1>
        <p class="texto-secundario">Ingresa con tu correo y clave de acceso.</p>
        <form id="formulario-iniciar-sesion" method="post">
            <label for="correo-acceso">Correo electronico</label>
            <input id="correo-acceso" name="correoUsuario" type="email" required maxlength="160" autocomplete="username" placeholder="nombre@correo.com">
            <label for="clave-acceso">Clave de acceso</label>
            <div class="campo-clave">
                <input id="clave-acceso" name="contrasenaUsuario" type="password" required maxlength="72" autocomplete="current-password" placeholder="Ingresa tu clave">
                <button type="button" id="alternar-clave" aria-controls="clave-acceso" aria-pressed="false" aria-label="Mostrar clave de acceso" hidden>Mostrar</button>
            </div>
            <p id="mensaje-formulario" class="mensaje" role="status" hidden></p>
            <button class="boton ancho-total" type="submit">Iniciar sesion <?= mostrarIcono('flecha') ?></button>
        </form>
        <p class="pie-formulario">Aun no tienes cuenta? <a href="/registro-paciente">Registrate como paciente</a></p>
        <p class="ayuda ayuda-acceso">Si necesitas ayuda para ingresar, comunicate con recepcion.</p>
    </section>
</div>
