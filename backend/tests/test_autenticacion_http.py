"""Pruebas reales de autenticacion en un cluster PostgreSQL NUEVO y local.

No usa Neon ni la configuracion DB del proyecto. Crea .tmp-auth/<identificador>,
inicializa PostgreSQL con datos sinteticos, aplica 001-009 y detiene sus procesos.
Requiere PHP con pdo_pgsql/curl y los binarios PostgreSQL (initdb, pg_ctl).
Ejemplo Windows:
  python backend/tests/test_autenticacion_http.py --php C:/php85/php.exe \
    --pg-bin "C:/Program Files/PostgreSQL/18/bin" --regresion
"""
from pathlib import Path
import argparse
import http.cookiejar
import json
import os
import secrets
import socket
import subprocess
import time
import urllib.error
import urllib.request


def puerto_libre():
    with socket.socket() as conexion:
        conexion.bind(("127.0.0.1", 0))
        return conexion.getsockname()[1]


class Cliente:
    def __init__(self, base, resultados, secretos):
        self.base = base
        self.resultados = resultados
        self.secretos = secretos
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, metodo, ruta, datos=None, esperado=200, csrf=True, token=None):
        headers = {"Accept": "application/json"}
        body = None
        if metodo != "GET":
            if csrf:
                token = token or self.request("GET", "/autenticacion/token")["datos"]["tokenCsrf"]
                headers["X-CSRF-Token"] = token
            headers["Content-Type"] = "application/json"
            body = json.dumps(datos or {}).encode()
        solicitud = urllib.request.Request(self.base + ruta, data=body, headers=headers, method=metodo)
        try:
            with self.opener.open(solicitud, timeout=20) as respuesta:
                codigo, contenido = respuesta.status, respuesta.read()
        except urllib.error.HTTPError as error:
            codigo, contenido = error.code, error.read()
        for secreto in self.secretos:
            assert secreto.encode() not in contenido, "Una respuesta expuso una credencial."
        assert codigo == esperado, f"{metodo} {ruta}: esperado {esperado}, obtenido {codigo}"
        self.resultados.append((metodo, ruta, codigo))
        return json.loads(contenido)

    def login(self, correo, clave, esperado=200):
        return self.request("POST", "/autenticacion/iniciar-sesion", {
            "correoUsuario": correo, "contrasenaUsuario": clave,
        }, esperado)

    def cookie(self):
        return tuple((cookie.name, cookie.value) for cookie in self.cookies)

    def vista(self, front, ruta):
        with self.opener.open(front + ruta, timeout=20) as respuesta:
            return respuesta.geturl(), respuesta.read().decode("utf-8")


def probar(base, front, clave, cuentas, soporte):
    resultados, secretos = [], [clave]
    cliente = lambda: Cliente(base, resultados, secretos)
    nueva = "PersonalNueva" + secrets.token_hex(8)
    secretos.append(nueva)
    cambio = {"contrasenaActual": clave, "nuevaContrasena": nueva, "confirmacionContrasena": nueva}
    anon = cliente()
    anon.request("POST", "/autenticacion/cambiar-contrasena", cambio, 401)
    anon.request("POST", "/usuarios/1/restablecer-contrasena", {}, 401)
    anon.request("POST", "/autenticacion/cambiar-contrasena", cambio, 419, csrf=False)
    anon.request("POST", "/autenticacion/cerrar-sesion")
    clientes, usuarios = {}, {}
    for rol in ["administrador", "recepcionista", "medico", "paciente"]:
        actual, otra = cliente(), cliente()
        correo = rol + "@vidasalud.test"
        usuarios[rol] = actual.login(correo, clave)["datos"]
        otra.login(correo, clave)
        cookie_anterior = actual.cookie()
        csrf_anterior = actual.request("GET", "/autenticacion/token")["datos"]["tokenCsrf"]
        actual.request("POST", "/autenticacion/cambiar-contrasena", cambio)
        assert actual.cookie() != cookie_anterior, "El cambio no regenero el identificador de sesion."
        assert actual.request("GET", "/autenticacion/token")["datos"]["tokenCsrf"] != csrf_anterior
        actual.request("GET", "/autenticacion/sesion")
        otra.request("GET", "/autenticacion/sesion", esperado=401)
        cliente().login(correo, clave, 401)
        cliente().login(correo, nueva)
        actual.request("POST", "/autenticacion/cambiar-contrasena", cambio, 419, token=csrf_anterior)
        url, html = actual.vista(front, "/cambiar-contrasena")
        assert url.endswith("/cambiar-contrasena"), "El formulario no esta disponible para el rol."
        assert "contrasenaActual" in html and "confirmacionContrasena" in html
        if rol != "administrador":
            assert "Restablecer contrase" not in html and 'href="/usuarios"' not in html
        clientes[rol] = actual
    print("OK: cuatro roles, cambio propio, nueva clave, revocacion, cookie y CSRF renovados.")

    admin = clientes["administrador"]
    id_paciente = usuarios["paciente"]["idUsuario"]
    datos_admin = {"contrasenaTemporal": secrets.token_hex(12), "confirmarRestablecimiento": True}
    secretos.append(datos_admin["contrasenaTemporal"])
    for rol in ["recepcionista", "medico", "paciente"]:
        clientes[rol].request("GET", "/usuarios", esperado=403)
        clientes[rol].request("POST", f"/usuarios/{id_paciente}/restablecer-contrasena", datos_admin, 403)
    clientes["paciente"].request("POST", "/autenticacion/cambiar-contrasena", {
        "contrasenaActual": nueva, "nuevaContrasena": clave, "confirmacionContrasena": clave,
        "idUsuario": usuarios["administrador"]["idUsuario"],
    }, 422)
    validaciones = cliente()
    validaciones.login("validaciones@auth.example.test", clave)
    validaciones.request("POST", "/autenticacion/cambiar-contrasena", cambio | {"contrasenaActual": "ErroneaPrueba123"}, 422)
    validaciones.request("POST", "/autenticacion/cambiar-contrasena", cambio | {"confirmacionContrasena": "DiferentePrueba123"}, 422)
    validaciones.request("POST", "/autenticacion/cambiar-contrasena", cambio | {"nuevaContrasena": "corta", "confirmacionContrasena": "corta"}, 422)
    validaciones.request("POST", "/autenticacion/cambiar-contrasena", cambio | {"nuevaContrasena": clave, "confirmacionContrasena": clave}, 422)
    print("OK: permisos, manipulacion de identidad y validaciones del servidor.")

    soporte("reiniciar-limites")
    admin.request("POST", f"/usuarios/{usuarios['administrador']['idUsuario']}/restablecer-contrasena", datos_admin, 409)
    admin.request("POST", f"/usuarios/{cuentas['inactiva']}/restablecer-contrasena", datos_admin, 409)
    admin.request("POST", f"/usuarios/{id_paciente}/restablecer-contrasena", datos_admin | {"confirmarRestablecimiento": False}, 422)
    admin.request("POST", f"/usuarios/{id_paciente}/restablecer-contrasena", datos_admin, 419, csrf=False)
    soporte("reiniciar-limites")
    admin.request("POST", f"/usuarios/{id_paciente}/restablecer-contrasena", datos_admin)
    clientes["paciente"].request("GET", "/autenticacion/sesion", esperado=401)
    cliente().login("paciente@vidasalud.test", nueva, 401)
    temporal = cliente()
    assert temporal.login("paciente@vidasalud.test", datos_admin["contrasenaTemporal"])["datos"]["requiereCambioContrasena"] is True
    temporal.request("GET", "/autenticacion/sesion")
    temporal.request("GET", "/citas", esperado=403)
    url, html = temporal.vista(front, "/panel")
    assert url.endswith("/cambiar-contrasena"), "No redirige al cambio obligatorio."
    cliente().login("paciente@vidasalud.test", datos_admin["contrasenaTemporal"], 401)
    personal = "PersonalDefinitiva" + secrets.token_hex(8)
    secretos.append(personal)
    temporal.request("POST", "/autenticacion/cambiar-contrasena", {
        "contrasenaActual": datos_admin["contrasenaTemporal"], "nuevaContrasena": personal,
        "confirmacionContrasena": personal,
    })
    assert temporal.request("GET", "/autenticacion/sesion")["datos"]["requiereCambioContrasena"] is False
    temporal.request("GET", "/citas")
    cliente().login("paciente@vidasalud.test", personal)
    cliente().login("paciente@vidasalud.test", datos_admin["contrasenaTemporal"], 401)
    eventos = json.loads(soporte("auditoria", str(id_paciente)))
    assert any(evento["id_usuario"] == usuarios["administrador"]["idUsuario"] and evento["id_registro"] == id_paciente and evento["fecha_hora"] for evento in eventos)
    print("OK: restablecimiento autorizado, uso unico, cambio obligatorio y bitacora.")

    soporte("reiniciar-limites")
    for nombre in ["caducada", "temporal"]:
        clave_temporal = secrets.token_hex(12)
        secretos.append(clave_temporal)
        admin.request("POST", f"/usuarios/{cuentas[nombre]}/restablecer-contrasena", {
            "contrasenaTemporal": clave_temporal, "confirmarRestablecimiento": True,
        })
        if nombre == "caducada":
            soporte("caducar-temporal", str(cuentas[nombre]))
            cliente().login(nombre + "@auth.example.test", clave_temporal, 401)
        else:
            usado = cliente()
            usado.login(nombre + "@auth.example.test", clave_temporal)
            soporte("caducar-temporal", str(cuentas[nombre]))
            usado.request("POST", "/autenticacion/cambiar-contrasena", {
                "contrasenaActual": clave_temporal, "nuevaContrasena": personal,
                "confirmacionContrasena": personal,
            }, 401)
    print("OK: expiracion temporal antes y despues del inicio de sesion.")

    soporte("reiniciar-limites")
    segundo_admin = cliente()
    segundo_admin.login("limiteadmin@auth.example.test", clave)
    for rol in ["administrador", "recepcionista", "medico", "paciente"]:
        temporal_rol, definitiva_rol = secrets.token_hex(12), "DefinitivaRol" + secrets.token_hex(8)
        secretos.extend([temporal_rol, definitiva_rol])
        segundo_admin.request("POST", f"/usuarios/{usuarios[rol]['idUsuario']}/restablecer-contrasena", {
            "contrasenaTemporal": temporal_rol, "confirmarRestablecimiento": True,
        })
        sesion_anterior = temporal if rol == "paciente" else clientes[rol]
        sesion_anterior.request("GET", "/autenticacion/sesion", esperado=401)
        restringido = cliente()
        usuario = restringido.login(rol + "@vidasalud.test", temporal_rol)["datos"]
        assert usuario["requiereCambioContrasena"] is True and usuario["codigoRol"] == rol
        restringido.request("GET", "/citas", esperado=403)
        if rol == "administrador":
            restringido.request("POST", f"/usuarios/{id_paciente}/restablecer-contrasena", datos_admin, 403)
        restringido.request("POST", "/autenticacion/cambiar-contrasena", {
            "contrasenaActual": temporal_rol, "nuevaContrasena": definitiva_rol,
            "confirmacionContrasena": definitiva_rol,
        })
        usuario = restringido.request("GET", "/autenticacion/sesion")["datos"]
        assert usuario["requiereCambioContrasena"] is False and usuario["codigoRol"] == rol
        restringido.request("GET", "/citas")
        restringido.request("POST", "/autenticacion/cerrar-sesion")
        restringido.request("GET", "/autenticacion/sesion", esperado=401)
        restringido.login(rol + "@vidasalud.test", definitiva_rol)
    print("OK: restablecimiento y recuperacion de acceso de los cuatro roles, sin cambiar permisos.")

    soporte("reiniciar-limites")
    for intento in range(6):
        cliente().login("limitelogin@auth.example.test", "ErroneaPrueba123", 401 if intento < 5 else 429)
    limite = cliente()
    limite.login("limitecambio@auth.example.test", clave)
    for intento in range(6):
        limite.request("POST", "/autenticacion/cambiar-contrasena", cambio | {"contrasenaActual": "ErroneaPrueba123"}, 422 if intento < 5 else 429)
    limite_admin = cliente()
    limite_admin.login("limiteadmin@auth.example.test", clave)
    for intento in range(6):
        clave_temporal = secrets.token_hex(12)
        secretos.append(clave_temporal)
        limite_admin.request("POST", f"/usuarios/{cuentas['temporal']}/restablecer-contrasena", {
            "contrasenaTemporal": clave_temporal, "confirmarRestablecimiento": True,
        }, 200 if intento < 5 else 429)
    soporte("reiniciar-limites")
    for intento in range(51):
        cliente().login(f"inexistente{intento}@auth.example.test", "ErroneaPrueba123", 401 if intento < 50 else 429)
    print("OK: limites persistentes por cuenta, operacion e IP, incluso cambiando cookies.")

    soporte("hashes-seguros")
    bitacora = soporte("sin-secretos")
    for secreto in secretos:
        assert secreto not in bitacora, "La bitacora contiene una credencial."
    print(f"OK: {len(resultados)} comprobaciones HTTP; ninguna respuesta o bitacora expone credenciales.")
    return secretos


def main():
    os.sys.stdout.reconfigure(line_buffering=True)
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--php", default="php")
    parser.add_argument("--pg-bin", required=True)
    parser.add_argument("--regresion", action="store_true", help="Ejecuta tambien las suites PHP y el contrato HTTP previo en bases nuevas distintas.")
    opciones = parser.parse_args()
    proyecto = Path(__file__).resolve().parents[2]
    temporal = proyecto / ".tmp-auth" / ("ejecucion-" + secrets.token_hex(6))
    temporal.mkdir(parents=True)
    pg_bin = Path(opciones.pg_bin)
    sufijo = ".exe" if os.name == "nt" else ""
    ejecutable = lambda nombre: str(pg_bin / (nombre + sufijo))
    flags = subprocess.CREATE_NO_WINDOW if os.name == "nt" else 0
    puerto_bd, puerto_api, puerto_front = puerto_libre(), puerto_libre(), puerto_libre()
    while len({puerto_bd, puerto_api, puerto_front}) != 3:
        puerto_bd, puerto_api, puerto_front = puerto_libre(), puerto_libre(), puerto_libre()
    base, front = f"http://127.0.0.1:{puerto_api}/api", f"http://127.0.0.1:{puerto_front}"
    clave = "PruebaAislada" + secrets.token_hex(12)
    entorno = dict(os.environ, DB_HOST="127.0.0.1", DB_PORT=str(puerto_bd),
        DB_NAME="vidasalud_auth_pruebas", DB_USER="postgres", DB_PASS="", DB_SSLMODE="disable",
        APP_ENV="local", APP_DEBUG="false", SESSION_SECURE="false", SESSION_COOKIE_NAME="vidasalud_auth_pruebas",
        SESSION_IDLE_SECONDS="1800", SEED_USERS_PASSWORD=clave, API_URL=base, API_INTERNAL_URL=base,
        FRONTEND_URL=front, VIDASALUD_TEST_LOCAL="1", CITA_DURACION_MINUTOS="30")
    def ejecutar(argumentos, env=None):
        # PostgreSQL en Windows hereda handles: no usar PIPE al iniciar el daemon.
        if Path(argumentos[0]).stem == "pg_ctl":
            with open(temporal / "pg_ctl.log", "a", encoding="utf-8") as salida:
                resultado = subprocess.run(argumentos, cwd=proyecto, env=env or entorno,
                    stdout=salida, stderr=subprocess.STDOUT, text=True, creationflags=flags)
        else:
            resultado = subprocess.run(argumentos, cwd=proyecto, env=env or entorno, capture_output=True, text=True, creationflags=flags)
            # Conserva los diagnosticos para no ocultar la suite que falla.
            # Estas salidas son de procesos locales con datos sinteticos, nunca de Neon.
            nombre = Path(argumentos[1]).stem if len(argumentos) > 1 and not argumentos[1].startswith("-") else Path(argumentos[0]).stem
            with open(temporal / (nombre + ".log"), "a", encoding="utf-8") as salida:
                salida.write(resultado.stdout + resultado.stderr)
        if resultado.returncode:
            # No imprime cuerpos de solicitudes, claves sinteticas ni salida arbitraria del servidor.
            raise RuntimeError(f"Fallo el proceso {Path(argumentos[0]).name} ({resultado.returncode}); revisar artefactos locales de pruebas.")
        return resultado.stdout or ""
    def soporte(*argumentos, env=None):
        return ejecutar([opciones.php, "backend/tests/autenticacion_aislada.php", *argumentos], env)
    servidores, archivos = [], []
    iniciado = False
    try:
        ejecutar([ejecutable("initdb"), "-D", str(temporal / "pgdata"), "-U", "postgres", "--auth-local=trust", "--auth-host=trust", "--encoding=UTF8", "--no-locale"])
        ejecutar([ejecutable("pg_ctl"), "-D", str(temporal / "pgdata"), "-l", str(temporal / "postgres.log"), "-o", f"-h 127.0.0.1 -p {puerto_bd}", "-w", "start"])
        iniciado = True
        print("OK: cluster PostgreSQL nuevo local, sin conexion a Neon.", flush=True)
        cuentas = json.loads(soporte("preparar"))
        print("OK: migraciones 001-009 y datos sinteticos aplicados exclusivamente al cluster local.", flush=True)
        for carpeta, puerto in [("backend", puerto_api), ("frontend", puerto_front)]:
            log = open(temporal / (carpeta + ".log"), "w", encoding="utf-8")
            archivos.append(log)
            proceso = subprocess.Popen([opciones.php, "-S", f"127.0.0.1:{puerto}", "-t", "public", "src/server.php"],
                cwd=proyecto / carpeta, env=entorno, stdout=log, stderr=log, creationflags=flags)
            servidores.append(proceso)
            for _ in range(60):
                if proceso.poll() is not None:
                    raise RuntimeError("Un servidor PHP termino antes de iniciar.")
                try:
                    with socket.create_connection(("127.0.0.1", puerto), timeout=0.2):
                        break
                except OSError:
                    time.sleep(0.1)
            else:
                raise RuntimeError("El servidor PHP no inicio.")
        secretos = probar(base, front, clave, cuentas, soporte)
        for archivo in archivos:
            archivo.flush()
        for nombre in ["backend.log", "frontend.log", "postgres.log"]:
            contenido = (temporal / nombre).read_text(encoding="utf-8", errors="replace")
            assert not any(secreto in contenido for secreto in secretos), "Un log contiene credenciales."
        if opciones.regresion:
            regresion = entorno | {"DB_NAME": "vidasalud_regresion_pruebas"}
            soporte("preparar", "solo-seeder", env=regresion)
            print(ejecutar([opciones.php, "backend/tests/ejecutar-pruebas.php", "--integracion"], regresion), end="", flush=True)
            http_regresion = entorno | {"DB_NAME": "vidasalud_http_pruebas"}
            soporte("preparar", "solo-seeder", env=http_regresion)
            print(ejecutar([os.sys.executable, "backend/tests/test_contrato_http.py", "--php", opciones.php], http_regresion), end="", flush=True)
    finally:
        for proceso in reversed(servidores):
            if proceso.poll() is None:
                proceso.terminate()
                proceso.wait(timeout=10)
        for archivo in archivos:
            archivo.close()
        if iniciado:
            ejecutar([ejecutable("pg_ctl"), "-D", str(temporal / "pgdata"), "-m", "fast", "-w", "stop"])
        print("Artefactos de pruebas locales:", temporal.relative_to(proyecto), flush=True)


if __name__ == "__main__":
    main()
