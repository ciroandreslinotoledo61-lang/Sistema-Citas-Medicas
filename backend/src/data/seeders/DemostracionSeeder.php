<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Seeders;

use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

/** Exclusivo de bases locales aisladas; nunca actualiza cuentas existentes. */
final class DemostracionSeeder
{
    public static function validarEntorno(): string
    {
        $variable = [CargarVariablesEntorno::class, 'obtenerVariableEntorno'];
        $base = $variable('DB_NAME');
        if (!in_array($variable('APP_ENV'), ['local', 'test'], true)
            || $variable('DEMO_SEED_ENABLE') !== '1'
            || !in_array($variable('DB_HOST'), ['127.0.0.1', '::1', 'localhost'], true)
            || !preg_match('/^[a-z][a-z0-9_]*_pruebas$/D', $base)
            || !preg_match('/^[0-9]{1,5}$/D', $variable('DB_PORT'))
            || (int) $variable('DB_PORT') < 1 || (int) $variable('DB_PORT') > 65535) {
            throw new \RuntimeException('Seed rechazado: exige APP_ENV=local/test, DEMO_SEED_ENABLE=1, host local, puerto explícito y DB_NAME terminado en _pruebas. Producción y hosts remotos están prohibidos.');
        }
        return $base;
    }

    public static function cargarDatosDemostracion(\PDO $conexion, string $contrasena): array
    {
        $base = self::validarEntorno();
        $destino = $conexion->query('SELECT current_database() AS base, host(inet_server_addr()) AS host')->fetch();
        if ($destino['base'] !== $base || !in_array($destino['host'], ['127.0.0.1', '::1'], true)) {
            throw new \RuntimeException('La conexión no corresponde a la base local de pruebas declarada.');
        }
        UsuarioValidator::validarCambioContrasena([
            'contrasenaActual' => $contrasena, 'nuevaContrasena' => $contrasena,
            'confirmacionContrasena' => $contrasena,
        ]);
        if ($conexion->inTransaction()) {
            throw new \RuntimeException('Ejecuta el seed fuera de una transacción existente.');
        }
        // Las altas usan los servicios normales y sus transacciones por cuenta.
        // El bloqueo de sesión serializa dos ejecuciones de este seed.
        $conexion->query('SELECT pg_advisory_lock(2026110701)');
        try {
            $roles = $conexion->prepare('INSERT INTO roles (codigo_rol, nombre_rol) VALUES (:codigo, :nombre) ON CONFLICT (codigo_rol) DO NOTHING');
            foreach (['medico' => 'Médico', 'paciente' => 'Paciente'] as $codigo => $nombre) {
                $roles->execute(compact('codigo', 'nombre'));
            }
            $usuarios = new UsuarioRepository($conexion);
            $resultado = [];
            foreach (['medico', 'paciente'] as $rol) {
                for ($numero = 1; $numero <= 3; $numero++) {
                    $correo = $rol . $numero . '@demo.vidasalud.test';
                    $nombres = $rol === 'medico' ? 'Médico' : 'Paciente';
                    $existente = $usuarios->obtenerUsuarioPorCorreo($correo);
                    $especialidad = $rol === 'medico' ? ['Medicina general', 'Pediatría', 'Dermatología'][$numero - 1] : null;
                    if ($existente !== null) {
                        self::exigirCuentaCompatible($conexion, $existente->idUsuario, $rol, $nombres, $numero, $especialidad);
                        $resultado[] = ['correo' => $correo, 'estado' => 'conservada', 'idUsuario' => $existente->idUsuario];
                        continue;
                    }
                    $datos = [
                        'nombresUsuario' => $nombres, 'apellidosUsuario' => (string) $numero, 'correoUsuario' => $correo,
                        'contrasenaUsuario' => $contrasena, 'confirmacionContrasena' => $contrasena,
                    ];
                    if ($rol === 'medico') {
                        $idEspecialidad = self::obtenerEspecialidad($conexion, $especialidad);
                        $perfil = (new MedicoService($conexion))->registrarMedicoConUsuario($datos + [
                            'idEspecialidad' => $idEspecialidad, 'matriculaProfesional' => 'VS-DEMO-M00' . $numero,
                            'telefonoMedico' => '7000000' . $numero,
                        ]);
                        $idUsuario = $perfil->idUsuario;
                    } else {
                        $perfil = (new PacienteService($conexion))->registrarPacienteConHistoriaClinica($datos + [
                            'cedulaIdentidad' => 'VS-DEMO-P00' . $numero, 'fechaNacimiento' => '2000-01-15',
                            'telefonoPaciente' => '7100000' . $numero, 'direccionPaciente' => 'Datos ficticios de demostración',
                            'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '',
                        ]);
                        $idUsuario = $perfil->obtenerDatosPublicosPaciente()['idUsuario'];
                    }
                    $resultado[] = ['correo' => $correo, 'estado' => 'creada', 'idUsuario' => $idUsuario];
                }
            }
            return $resultado;
        } finally {
            $conexion->query('SELECT pg_advisory_unlock(2026110701)');
        }
    }

    private static function obtenerEspecialidad(\PDO $conexion, string $nombre): int
    {
        // Reutiliza también nombres legados sin tilde, sin renombrarlos ni activarlos.
        $buscar = $conexion->prepare("SELECT id_especialidad, especialidad_activa FROM especialidades
            WHERE translate(lower(regexp_replace(btrim(nombre_especialidad), '[[:space:]]+', ' ', 'g')), 'áéíóú', 'aeiou') = :nombre");
        $buscar->execute(['nombre' => strtr(mb_strtolower($nombre), ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u'])]);
        $filas = $buscar->fetchAll();
        if (count($filas) > 1) {
            throw new \RuntimeException('Especialidad ambigua: ' . $nombre . '. Revisa la base de pruebas manualmente.');
        }
        if ($filas) {
            if (!in_array($filas[0]['especialidad_activa'], [true, 1, '1', 't', 'true'], true)) {
                throw new \RuntimeException('La especialidad existente está inactiva: ' . $nombre);
            }
            return (int) $filas[0]['id_especialidad'];
        }
        $insertar = $conexion->prepare('INSERT INTO especialidades (nombre_especialidad, descripcion_especialidad) VALUES (:nombre, :descripcion) RETURNING id_especialidad');
        $insertar->execute(['nombre' => $nombre, 'descripcion' => 'Especialidad para demostración local']);
        return (int) $insertar->fetchColumn();
    }

    private static function exigirCuentaCompatible(\PDO $conexion, int $idUsuario, string $rol, string $nombres, int $numero, ?string $especialidad): void
    {
        $consulta = $conexion->prepare("SELECT r.codigo_rol, u.nombres_usuario, u.apellidos_usuario,
            m.matricula_profesional, e.nombre_especialidad, p.cedula_identidad, h.id_historia_clinica
            FROM usuarios u JOIN roles r USING(id_rol)
            LEFT JOIN medicos m USING(id_usuario) LEFT JOIN especialidades e USING(id_especialidad)
            LEFT JOIN pacientes p ON p.id_usuario=u.id_usuario LEFT JOIN historias_clinicas h USING(id_paciente)
            WHERE u.id_usuario=:id");
        $consulta->execute(['id' => $idUsuario]);
        $fila = $consulta->fetch();
        $normalizar = static fn(string $valor): string => strtr(mb_strtolower($valor), ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u']);
        $perfilValido = $rol === 'medico'
            ? $fila['matricula_profesional'] === 'VS-DEMO-M00' . $numero && $normalizar($fila['nombre_especialidad'] ?? '') === $normalizar($especialidad ?? '')
            : $fila['cedula_identidad'] === 'VS-DEMO-P00' . $numero && $fila['id_historia_clinica'] !== null;
        if ($fila['codigo_rol'] !== $rol || $fila['nombres_usuario'] !== $nombres || $fila['apellidos_usuario'] !== (string) $numero || !$perfilValido) {
            throw new \RuntimeException('Correo de demostración ocupado por una cuenta incompatible. No se modificó esa cuenta.');
        }
    }
}
