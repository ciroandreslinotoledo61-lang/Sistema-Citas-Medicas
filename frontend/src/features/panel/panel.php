<section class="titulo-vista">
    <div><p class="sobretitulo">MI ESPACIO <span class="separador">/</span> <?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></p>
    <h1>Hola, <?= escaparTextoHtml($usuarioAutenticado['nombresUsuario']) ?>.</h1>
    <p>Que necesitas hacer hoy?</p></div>
    <span class="etiqueta etiqueta-clara"><?= escaparTextoHtml(date('d/m/Y')) ?></span>
</section>
<div class="rejilla-panel">
    <section class="tarjeta tarjeta-perfil">
        <span class="avatar" aria-hidden="true"><?= escaparTextoHtml(mb_substr($usuarioAutenticado['nombresUsuario'], 0, 1)) ?></span>
        <p class="sobretitulo">TU CUENTA</p>
        <h2><?= escaparTextoHtml($usuarioAutenticado['nombresUsuario'] . ' ' . $usuarioAutenticado['apellidosUsuario']) ?></h2>
        <p><?= escaparTextoHtml($usuarioAutenticado['correoUsuario']) ?></p>
        <span class="insignia activa">Cuenta activa</span>
    </section>
    <section class="tarjeta">
        <p class="sobretitulo">ACCESOS DISPONIBLES</p>
        <?php if ($usuarioAutenticado['codigoRol'] === 'administrador'): ?>
            <a class="acceso-modulo" href="/usuarios"><div><h2>Gestionar usuarios</h2><p>Registra cuentas y revisa sus accesos.</p></div><span aria-hidden="true">&rarr;</span></a>
            <a class="acceso-modulo" href="/pacientes"><div><h2>Consultar pacientes</h2><p>Busca registros y actualiza contactos.</p></div><span aria-hidden="true">&rarr;</span></a>
        <?php elseif ($usuarioAutenticado['codigoRol'] === 'recepcionista'): ?>
            <a class="acceso-modulo" href="/pacientes"><div><h2>Consultar pacientes</h2><p>Encuentra un paciente por nombre o CI.</p></div><span aria-hidden="true">&rarr;</span></a>
            <a class="acceso-modulo" href="/pacientes/nuevo"><div><h2>Registrar paciente</h2><p>Abre su cuenta y su historia clinica.</p></div><span aria-hidden="true">&rarr;</span></a>
        <?php elseif ($usuarioAutenticado['codigoRol'] === 'paciente'): ?>
            <a class="acceso-modulo" href="/mi-perfil"><div><h2>Ver mi perfil</h2><p>Consulta tus datos personales y de contacto.</p></div><span aria-hidden="true">&rarr;</span></a>
            <p class="nota-panel">Para actualizar tus datos de contacto, comunicate con recepcion.</p>
        <?php else: ?>
            <h2>Bienvenido al portal medico</h2>
            <p>Tu cuenta esta habilitada. La agenda y el registro de consultas aun no estan disponibles en esta version.</p>
        <?php endif; ?>
    </section>
</div>
