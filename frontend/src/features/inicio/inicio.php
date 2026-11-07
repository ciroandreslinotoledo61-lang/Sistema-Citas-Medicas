<section class="hero">
    <div class="hero-texto">
        <p class="sobretitulo">PORTAL DE PACIENTES</p>
        <h1>Tu espacio<br>en la clínica.</h1>
        <p class="descripcion-hero">Elige tu médico, reserva un horario y consulta tus citas. Si es tu primera visita al portal, puedes registrarte aquí.</p>
        <div class="acciones"><a class="boton" href="/iniciar-sesion">Iniciar sesión <?= mostrarIcono('flecha') ?></a><a class="enlace" href="/registro-paciente">Crear cuenta de paciente</a></div>
        <p class="nota-hero"><?= mostrarIcono('candado') ?> Un acceso personal para tu información.</p>
    </div>
    <aside class="tarjeta-bienvenida" aria-labelledby="titulo-bienvenida">
        <div class="bienvenida-superior"><span class="sobretitulo">PRIMEROS PASOS</span><?= mostrarIcono('clinica') ?></div>
        <h2 id="titulo-bienvenida">Antes de comenzar</h2>
        <ol class="pasos-bienvenida">
            <li><span>01</span><div><h3>Ten tus datos a mano</h3><p>Necesitarás tu CI, correo y un teléfono de contacto.</p></div></li>
            <li><span>02</span><div><h3>Crea tu cuenta</h3><p>Completa el registro y elige tu clave de acceso.</p></div></li>
            <li><span>03</span><div><h3>Reserva tu cita</h3><p>Elige un médico y un turno disponible.</p></div></li>
        </ol>
    </aside>
</section>
<section class="franja-inicio" aria-label="Información del portal">
    <div><?= mostrarIcono('perfil') ?><div><h2>Para pacientes</h2><p>Tus datos y reservas en un solo lugar.</p></div></div>
    <div><?= mostrarIcono('usuarios') ?><div><h2>Para el personal</h2><p>Ingresa con la cuenta asignada por Administración.</p></div></div>
    <div><?= mostrarIcono('informacion') ?><div><h2>¿Necesitas ayuda?</h2><p>Recepción puede ayudarte a actualizar tus datos.</p></div></div>
</section>
