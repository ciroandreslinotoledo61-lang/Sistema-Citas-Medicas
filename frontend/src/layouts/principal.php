<?php
require_once dirname(__DIR__) . '/components/iconos.php';
require_once dirname(__DIR__) . '/components/modulos-disponibles.php';
$esVistaPrivada = $usuarioAutenticado !== null;
$modulosDisponibles = $esVistaPrivada ? obtenerModulosDisponibles($usuarioAutenticado['codigoRol']) : [];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Clinica. Portal de pacientes y gestion del centro medico.">
    <meta name="theme-color" content="#215c4d">
    <title><?= escaparTextoHtml($tituloPagina) ?> | Clinica</title>
    <link rel="stylesheet" href="/assets/styles/estilos.css">
</head>
<body class="<?= $esVistaPrivada ? 'vista-privada' : 'vista-publica' ?>"
    data-api-url="<?= escaparTextoHtml(obtenerConfiguracionFrontend('API_URL', 'http://127.0.0.1:8000/api')) ?>"
    data-pagina="<?= escaparTextoHtml($paginaActual) ?>"
    data-id-registro="<?= escaparTextoHtml($idRegistro) ?>"
    data-usuario="<?= escaparTextoHtml(json_encode($usuarioAutenticado, JSON_THROW_ON_ERROR)) ?>">
<a class="saltar-contenido" href="#contenido-principal">Ir al contenido</a>
<?php require dirname(__DIR__) . '/components/navegacion.php'; ?>
<div class="area-aplicacion" id="area-aplicacion">
    <?php if ($esVistaPrivada): ?>
    <header class="barra-superior">
        <div class="ruta-superior">
            <button class="boton-icono boton-menu" id="abrir-navegacion" type="button" aria-label="Abrir menu" aria-controls="navegacion-lateral" aria-expanded="false"><?= mostrarIcono('menu') ?></button>
            <span><?= escaparTextoHtml($usuarioAutenticado['nombreRol']) ?></span>
            <span class="separador" aria-hidden="true">/</span>
            <strong><?= escaparTextoHtml($tituloPagina) ?></strong>
        </div>
        <span class="identidad-superior"><?= mostrarIcono('candado') ?> Espacio personal</span>
    </header>
    <?php endif; ?>
    <main id="contenido-principal" class="contenedor <?= $paginaActual === 'inicio' ? 'inicio' : 'contenido' ?>" tabindex="-1">
        <p id="mensaje-global" class="mensaje" role="status" hidden></p>
        <noscript><p class="mensaje error">Activa JavaScript para usar los formularios del portal.</p></noscript>
        <?php require dirname(__DIR__) . '/features/' . $vista; ?>
    </main>
    <footer class="pie-pagina"><div class="contenedor"><span>Clinica <span class="separador">/</span> <?= $esVistaPrivada ? 'Gestion del centro medico' : 'Portal de pacientes' ?></span><span>Atencion y cuidado</span></div></footer>
</div>
<script type="module" src="/assets/app/aplicacion.js"></script>
<?php if ($moduloJavascript !== ''): ?>
<script type="module" src="/assets/features/<?= escaparTextoHtml($moduloJavascript) ?>"></script>
<?php endif; ?>
</body>
</html>
