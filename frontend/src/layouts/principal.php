<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal de pacientes del Centro Medico Vida Salud.">
    <title><?= escaparTextoHtml($tituloPagina) ?> | Vida Salud</title>
    <link rel="stylesheet" href="/assets/styles/estilos.css">
</head>
<body
    data-api-url="<?= escaparTextoHtml(obtenerConfiguracionFrontend('API_URL', 'http://127.0.0.1:8000/api')) ?>"
    data-pagina="<?= escaparTextoHtml($paginaActual) ?>"
    data-id-registro="<?= escaparTextoHtml($idRegistro) ?>"
    data-usuario="<?= escaparTextoHtml(json_encode($usuarioAutenticado, JSON_THROW_ON_ERROR)) ?>">
<a class="saltar-contenido" href="#contenido-principal">Ir al contenido</a>
<?php require dirname(__DIR__) . '/components/navegacion.php'; ?>
<main id="contenido-principal" class="contenedor <?= $paginaActual === 'inicio' ? 'inicio' : 'contenido' ?>">
    <noscript><p class="mensaje error">Activa JavaScript para usar los formularios del portal.</p></noscript>
    <?php require dirname(__DIR__) . '/features/' . $vista; ?>
</main>
<footer class="pie-pagina"><div class="contenedor"><span>Vida Salud <span class="separador">/</span> Centro Medico</span><span>Portal de pacientes</span></div></footer>
<script type="module" src="/assets/app/aplicacion.js"></script>
<?php if ($moduloJavascript !== ''): ?>
<script type="module" src="/assets/features/<?= escaparTextoHtml($moduloJavascript) ?>"></script>
<?php endif; ?>
</body>
</html>
