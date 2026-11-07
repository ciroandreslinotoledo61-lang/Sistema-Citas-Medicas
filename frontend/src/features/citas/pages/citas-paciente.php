<section class="titulo-vista">
    <div><p class="sobretitulo">MI ATENCIÓN</p><h1>Mis citas médicas</h1><p>Consulta y administra tus reservas.</p></div>
    <a class="boton" href="/citas/nueva"><?= mostrarIcono('mas') ?> Reservar cita</a>
</section>
<section id="citas-paciente" aria-label="Mis reservas">
    <div class="pestanas-citas" role="tablist" aria-label="Citas médicas">
        <button id="pestana-proximas" type="button" role="tab" aria-selected="true" aria-controls="panel-citas" data-vista="proximas">Próximas citas</button>
        <button id="pestana-historial" type="button" role="tab" aria-selected="false" aria-controls="panel-citas" tabindex="-1" data-vista="historial">Historial</button>
    </div>
    <div id="panel-citas" role="tabpanel" aria-labelledby="pestana-proximas" tabindex="0">
        <form id="filtros-historial" class="tarjeta filtros-historial-citas" method="get" hidden>
            <label>Fecha<input name="fecha" type="date"></label>
            <label>Especialidad o médico<input name="busqueda" type="search" maxlength="80" placeholder="Buscar en tus citas"></label>
            <button class="boton boton-secundario" type="submit">Filtrar historial</button>
            <button class="enlace-boton" type="reset">Limpiar filtros</button>
        </form>
        <p id="mensaje-listado" class="mensaje" role="status" hidden></p>
        <p id="cargando-citas" class="texto-secundario" role="status">Cargando tus citas...</p>
        <div id="tarjetas-citas" class="rejilla-citas" aria-busy="true"></div>
        <div id="citas-vacias" class="tarjeta citas-vacias" role="status" hidden>
            <span class="icono-citas-vacias" aria-hidden="true"><?= mostrarIcono('calendario') ?></span>
            <h2 id="titulo-citas-vacias"></h2><p id="descripcion-citas-vacias"></p>
            <a id="reservar-sin-citas" class="boton" href="/citas/nueva">Reservar cita</a>
        </div>
        <?php require dirname(__DIR__, 3) . '/components/paginacion.php'; ?>
    </div>
</section>
<p class="nota-informativa"><?= mostrarIcono('informacion') ?><span>Puedes cancelar una cita antes de su inicio. Para reprogramar, comunícate con recepción.</span></p>
