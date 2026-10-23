<div class="rejilla-formulario">
<label>Cedula de identidad <span class="campo-obligatorio" aria-hidden="true">*</span><input name="cedulaIdentidad" required minlength="3" maxlength="25" autocomplete="off"></label>
<label>Fecha de nacimiento <span class="campo-obligatorio" aria-hidden="true">*</span><input name="fechaNacimiento" type="date" required max="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d', strtotime('-120 years')) ?>" autocomplete="bday"></label>
<label>Sexo (opcional)<select name="sexoPaciente"><option value="no_especificado">No especificado</option><option value="femenino">Femenino</option><option value="masculino">Masculino</option><option value="otro">Otro</option></select></label>
</div>
