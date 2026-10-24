<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/backend/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;

try {
    $db = ConexionPostgresql::obtenerConexionPostgresql();
    $db->beginTransaction();
    $db->query('SELECT pg_advisory_xact_lock(2026102101)');
    if ($db->query("SELECT to_regclass('public.despliegue_inicial')")->fetchColumn()) {
        if ((int) $db->query('SELECT version FROM despliegue_inicial')->fetchColumn() !== 1) {
            throw new RuntimeException('El marcador de inicializacion no corresponde a esta version. Revisa la base antes de continuar.');
        }
        $db->commit();
        echo "Base inicializada anteriormente; se conservan las cuentas y datos.\n";
        exit(0);
    }
    $tablas = (int) $db->query("SELECT count(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'")->fetchColumn();
    
if ($tablas !== 0) {
    if (getenv('ALLOW_IMPORTED_DATABASE') !== 'true') {
        throw new RuntimeException('La base existente no esta autorizada.');
    }

    $columnas = (int) $db->query(
        "SELECT count(*) FROM information_schema.columns
        WHERE table_schema='public'
        AND table_name='citas'
        AND column_name IN ('historial_cambios','excepcion_cita_activa_previa')"
    )->fetchColumn();

    if ($columnas !== 2 ||
        !$db->query("SELECT to_regclass('public.usuarios')")->fetchColumn()) {
        throw new RuntimeException('La base importada no tiene la estructura esperada.');
    }

    $db->commit();
    echo "Base importada reconocida; se conservan los datos.\n";
    exit(0);
}

    if (getenv('INITIALIZE_EMPTY_DATABASE') !== 'true') {
        throw new RuntimeException('Para inicializar la base vacia configura INITIALIZE_EMPTY_DATABASE=true.');
    }
    $correo = strtolower(trim(getenv('INITIAL_ADMIN_EMAIL') ?: ''));
    $clave = getenv('INITIAL_ADMIN_PASSWORD') ?: '';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 160) {
        throw new RuntimeException('Configura INITIAL_ADMIN_EMAIL con tu correo.');
    }
    ValidarCamposFormulario::validarContrasenaRegistro([
        'contrasenaUsuario' => $clave, 'confirmacionContrasena' => $clave,
    ]);
    $migraciones = [
        '001_crear_tablas_sistema.sql',
        '002_unicidad_especialidades_medicos.sql',
        '003_responsabilidades_citas.sql',
        '004_agenda_30_minutos_y_perfiles.sql',
        '005_programacion_y_cita_activa.sql',
    ];
    foreach ($migraciones as $nombre) {
        $archivo = dirname(__DIR__) . '/backend/src/data/migrations/' . $nombre;
        $sql = file_get_contents($archivo);
        if ($sql === false) { throw new RuntimeException('No se pudo leer la migracion.'); }
        $db->exec($sql);
    }
    $insertarRol = $db->prepare('INSERT INTO roles(codigo_rol, nombre_rol) VALUES (?, ?)');
    foreach (['administrador' => 'Administrador', 'recepcionista' => 'Recepcionista', 'medico' => 'Medico', 'paciente' => 'Paciente'] as $codigo => $nombre) {
        $insertarRol->execute([$codigo, $nombre]);
    }
    $insertarAdmin = $db->prepare("INSERT INTO usuarios(id_rol, nombres_usuario, apellidos_usuario, correo_usuario, contrasena_hash) SELECT id_rol, 'Administrador', 'Clinica', ?, ? FROM roles WHERE codigo_rol='administrador'");
    $insertarAdmin->execute([$correo, password_hash($clave, PASSWORD_DEFAULT)]);
    $db->exec('CREATE TABLE despliegue_inicial (version INTEGER PRIMARY KEY, fecha TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('INSERT INTO despliegue_inicial(version) VALUES (1)');
    $db->commit();
    echo "Base creada y administrador registrado. No se crearon cuentas de prueba.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    // No imprimir credenciales ni valores SQL en los registros publicos.
    if ($error instanceof PDOException) {
        fwrite(STDERR, "Error PostgreSQL: revisa DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS y DB_SSLMODE.\n");
    } elseif ($error instanceof \CitasMedicas\Shared\Errors\ErrorAplicacion) {
        fwrite(STDERR, "INITIAL_ADMIN_PASSWORD debe tener entre 12 y 72 bytes, sin bytes nulos.\n");
    } elseif (get_class($error) === RuntimeException::class) {
        // Los mensajes definidos en este script no contienen datos del usuario.
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
    } else {
        fwrite(STDERR, "Inicializacion detenida: revisa los archivos y la configuracion de PHP.\n");
    }
    exit(1);
}
