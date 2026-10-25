"""Prueba opcional: requiere Python 3.9+, PHP y una base NUEVA terminada en _pruebas.

Inicia servidores propios. La base debe tener las migraciones 001-008 y solo
las cuatro cuentas del seeder. Deja datos sinteticos para inspeccion.
"""
from pathlib import Path
import argparse
import json
import os
import socket
import subprocess
import sys
import tempfile
import time

sys.path.insert(0, str(Path(__file__).resolve().parent / "http"))
from escenario_http import ejecutar


def puerto_libre():
    with socket.socket() as conexion:
        conexion.bind(("127.0.0.1", 0))
        return conexion.getsockname()[1]


def main():
    argumentos = argparse.ArgumentParser(description=__doc__)
    argumentos.add_argument("--php", default="php", help="Ruta al ejecutable PHP")
    opciones = argumentos.parse_args()
    proyecto = Path(__file__).resolve().parents[2]
    configuracion_php = r'''
        require 'backend/src/bootstrap.php';
        use CitasMedicas\Shared\Config\CargarVariablesEntorno as Entorno;
        use CitasMedicas\Data\Config\ConexionPostgresql;
        $nombre = Entorno::obtenerVariableEntorno('DB_NAME');
        if (!str_ends_with($nombre, '_pruebas')) {
            fwrite(STDERR, "Usa una base independiente terminada en _pruebas.\n"); exit(1);
        }
        $conexion = ConexionPostgresql::obtenerConexionPostgresql();
        $usuarios = (int) $conexion->query('SELECT count(*) FROM usuarios')->fetchColumn();
        if ($usuarios !== 4) {
            fwrite(STDERR, "Esta prueba necesita una base nueva con solo las cuatro cuentas del seeder.\n"); exit(1);
        }
        echo json_encode(['clave' => Entorno::obtenerVariableEntorno('SEED_USERS_PASSWORD')]);
    '''
    proceso = subprocess.run(
        [opciones.php, "-r", configuracion_php], cwd=proyecto,
        text=True, capture_output=True, check=True,
    )
    clave = json.loads(proceso.stdout)["clave"]
    if not clave:
        raise RuntimeError("Configura SEED_USERS_PASSWORD con la clave usada al crear las cuentas de prueba.")
    puerto_backend = puerto_libre()
    puerto_frontend = puerto_libre()
    while puerto_frontend == puerto_backend:
        puerto_frontend = puerto_libre()
    api = f"http://127.0.0.1:{puerto_backend}/api"
    frontend = f"http://127.0.0.1:{puerto_frontend}"
    entorno = dict(os.environ, API_URL=api, FRONTEND_URL=frontend)
    servidores = []
    with tempfile.TemporaryDirectory(prefix="vidasalud-http-") as temporal:
        salidas = []
        try:
            for carpeta, puerto in [("backend", puerto_backend), ("frontend", puerto_frontend)]:
                salida = open(Path(temporal) / (carpeta + ".log"), "w", encoding="utf-8")
                salidas.append(salida)
                servidores.append(subprocess.Popen(
                    [opciones.php, "-S", f"127.0.0.1:{puerto}", "-t", "public", "src/server.php"],
                    cwd=proyecto / carpeta, env=entorno, stdout=salida, stderr=salida,
                ))
                for _ in range(50):
                    if servidores[-1].poll() is not None:
                        raise RuntimeError("El servidor PHP se cerro antes de iniciar.")
                    try:
                        with socket.create_connection(("127.0.0.1", puerto), timeout=0.2):
                            break
                    except OSError:
                        time.sleep(0.1)
                else:
                    raise RuntimeError("El servidor PHP no quedo disponible.")
            resultados = ejecutar(api, frontend, clave)
            print(f"OK: {len(resultados)} comprobaciones HTTP registradas; contrato previo y vistas por rol.")
            print("Los datos sinteticos quedan en la base de pruebas para inspeccion.")
        finally:
            for servidor in reversed(servidores):
                if servidor.poll() is None:
                    servidor.terminate()
                    servidor.wait(timeout=10)
            for salida in salidas:
                salida.close()


if __name__ == "__main__":
    try:
        main()
    except subprocess.CalledProcessError as error:
        print(error.stderr or str(error), file=sys.stderr)
        raise SystemExit(1)
