<?php
declare(strict_types=1);

use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

/** Datos exclusivos de cada prueba; nunca utiliza pacientes reales. */
final class SoportePruebasAgenda
{
    public array $pacientes = [];
    public array $medicos = [];
    public array $usuariosPacientes = [];
    public array $usuariosMedicos = [];
    public readonly string $identificador;
    private array $identificadoresUsuarios = [];
    public function __construct(private readonly PDO $conexion)
    {
        if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
            throw new RuntimeException('Usa una base separada cuyo nombre termine en _pruebas.');
        }
        $this->identificador = bin2hex(random_bytes(5));
    }
    public function crearDatos(): void
    {
        $usuarios = new UsuarioRepository($this->conexion);
        $idEspecialidad = (int) $this->conexion->query('SELECT id_especialidad FROM especialidades WHERE especialidad_activa ORDER BY id_especialidad LIMIT 1')->fetchColumn();
        if (!$idEspecialidad) { throw new RuntimeException('Carga primero los datos iniciales.'); }
        foreach (['Primero', 'Segundo'] as $nombre) {
            $cuenta = ['nombresUsuario' => 'Prueba ' . $nombre, 'apellidosUsuario' => 'Agenda',
                'contrasenaUsuario' => 'ClaveDePruebaAgenda123', 'confirmacionContrasena' => 'ClaveDePruebaAgenda123'];
            $medico = (new MedicoService($this->conexion))->registrarMedicoConUsuario($cuenta + [
                'correoUsuario' => 'medico-' . $nombre . '-' . $this->identificador . '@example.test',
                'matriculaProfesional' => 'AGENDA-' . $nombre . '-' . $this->identificador, 'idEspecialidad' => $idEspecialidad]);
            $this->identificadoresUsuarios[] = $medico->idUsuario;
            $this->medicos[] = $medico;
            $this->usuariosMedicos[] = $usuarios->obtenerUsuarioPorId($medico->idUsuario);
            $paciente = (new PacienteService($this->conexion))->registrarPacienteConHistoriaClinica($cuenta + [
                'correoUsuario' => 'paciente-' . $nombre . '-' . $this->identificador . '@example.test',
                'cedulaIdentidad' => 'AG-' . $nombre . '-' . $this->identificador,
                'fechaNacimiento' => '1995-02-02', 'telefonoPaciente' => '70000001', 'direccionPaciente' => 'Direccion de prueba',
                'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '']);
            $paciente = $paciente->obtenerDatosPublicosPaciente();
            $this->identificadoresUsuarios[] = $paciente['idUsuario'];
            $this->pacientes[] = $paciente;
            $this->usuariosPacientes[] = $usuarios->obtenerUsuarioPorId($paciente['idUsuario']);
        }
    }
    public function eliminarDatos(): void
    {
        if ($this->conexion->inTransaction()) { $this->conexion->rollBack(); }
        if (!$this->identificadoresUsuarios) { return; }
        $identificadores = implode(',', array_map('intval', $this->identificadoresUsuarios));
        $this->conexion->beginTransaction();
        try {
            // Los identificadores proceden de registros creados por esta instancia.
            $filtroCitas = "id_paciente IN (SELECT id_paciente FROM pacientes WHERE id_usuario IN ($identificadores))";
            $this->conexion->exec("UPDATE citas SET id_cita_origen = NULL WHERE $filtroCitas");
            $this->conexion->exec("DELETE FROM citas WHERE $filtroCitas");
            $this->conexion->exec("DELETE FROM turnos WHERE id_horario_medico IN (SELECT id_horario_medico FROM horarios_medicos WHERE id_medico IN (SELECT id_medico FROM medicos WHERE id_usuario IN ($identificadores)))");
            $this->conexion->exec("DELETE FROM horarios_medicos WHERE id_medico IN (SELECT id_medico FROM medicos WHERE id_usuario IN ($identificadores))");
            $this->conexion->exec("DELETE FROM medicos WHERE id_usuario IN ($identificadores)");
            $this->conexion->exec("DELETE FROM historias_clinicas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE id_usuario IN ($identificadores))");
            $this->conexion->exec("DELETE FROM pacientes WHERE id_usuario IN ($identificadores)");
            $this->conexion->exec("DELETE FROM usuarios WHERE id_usuario IN ($identificadores)");
            $this->conexion->commit();
        } catch (Throwable $error) { $this->conexion->rollBack(); throw $error; }
    }
}
