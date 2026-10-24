"""Prueba la configuracion de arranque sin conectarse a ninguna base de datos."""
from pathlib import Path
import argparse
import os
import subprocess


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--php', default='php')
    args = parser.parse_args()
    script = Path(__file__).resolve().parents[1] / 'verificar-configuracion.php'
    entorno = dict(os.environ, DB_HOST='postgres.example.test', DB_PORT='5432',
                   DB_NAME='ejemplo', DB_USER='ejemplo', DB_PASS='NoEsUnaClaveReal',
                   DB_SSLMODE='require', FRONTEND_URL='https://clinica.example.test')
    casos = [
        ({}, 0),
        ({'DB_HOST': ''}, 1),
        ({'DB_PASS': ''}, 1),
        ({'DB_PORT': 'no-es-un-puerto'}, 1),
        ({'DB_PORT': '65536'}, 1),
        ({'DB_SSLMODE': 'disable'}, 1),
        ({'FRONTEND_URL': 'http://clinica.example.test'}, 1),
        ({'FRONTEND_URL': 'https://usuario:clave@clinica.example.test'}, 1),
        ({'FRONTEND_URL': 'https://clinica.example.test/ruta'}, 1),
        ({'FRONTEND_URL': 'https://clinica.example.test?clave=secreto'}, 1),
    ]
    for cambios, esperado in casos:
        resultado = subprocess.run([args.php, str(script)], env=entorno | cambios,
                                   text=True, capture_output=True, timeout=10)
        assert resultado.returncode == esperado, (cambios.keys(), resultado.stderr)
        assert 'NoEsUnaClaveReal' not in resultado.stdout + resultado.stderr
    print(f'OK: {len(casos)} casos de configuracion; ningun secreto en los mensajes.')


if __name__ == '__main__':
    main()
