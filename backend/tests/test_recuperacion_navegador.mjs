// Formularios reales de recuperacion; correo capturado solo por el router aislado.
// Invocado por ejecutar-pruebas.php --completo --navegador; requiere Node 22+ y Edge.
import assert from "node:assert/strict";
import { spawn } from "node:child_process";
import { mkdir, readFile, writeFile } from "node:fs/promises";
import { randomUUID } from "node:crypto";
import path from "node:path";
import { fileURLToPath } from "node:url";

const raiz = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const front = process.env.FRONTEND_URL || "";
const api = process.env.API_URL || "";
const clave = process.env.SEED_USERS_PASSWORD || "";
assert.equal(process.env.VIDASALUD_TEST_LOCAL, "1", "Solo se admite el entorno local aislado.");
for (const url of [front, api]) assert.equal(new URL(url).hostname, "127.0.0.1", "No se prueban servidores publicados.");
assert.ok(clave.length >= 12, "Falta la clave sintetica del cluster.");
const artefactos = path.resolve(raiz, ".tmp-auth", "navegador-recuperacion-" + randomUUID());
await mkdir(artefactos, { recursive: true });
const perfil = path.join(artefactos, "perfil");
const edge = process.env.EDGE_BINARY || "C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe";
const proceso = spawn(edge, ["--headless", "--disable-gpu", "--no-first-run", "--no-default-browser-check",
    "--remote-debugging-port=0", "--remote-debugging-address=127.0.0.1", "--user-data-dir=" + perfil, "about:blank"],
{ windowsHide: true, stdio: "ignore" });
let falloProceso = null;
proceso.on("error", error => { falloProceso = error; });
const pausa = ms => new Promise(resolve => setTimeout(resolve, ms));
let socket;
let secuencia = 0;
const pendientes = new Map();
const erroresJavascript = [];
const urlsSolicitadas = [];
let verificaciones = 0;
function comando(method, params = {}) {
    const id = ++secuencia;
    return new Promise((resolve, reject) => {
        const temporizador = setTimeout(() => { pendientes.delete(id); reject(new Error("Tiempo excedido en " + method)); }, 20000);
        pendientes.set(id, { resolve, reject, temporizador });
        socket.send(JSON.stringify({ id, method, params }));
    });
}
async function evaluar(expression) {
    const respuesta = await comando("Runtime.evaluate", { expression, awaitPromise: true, returnByValue: true });
    assert.ok(!respuesta.exceptionDetails, "La evaluacion del navegador produjo un error.");
    return respuesta.result.value;
}
async function esperar(expression, descripcion) {
    for (let i = 0; i < 100; i++) {
        try { if (await evaluar(expression)) return; } catch (error) { if (i === 99) throw error; }
        await pausa(100);
    }
    throw new Error("No se completo: " + descripcion);
}
async function comprobar(expression, descripcion) {
    assert.equal(await evaluar(expression), true, descripcion);
    verificaciones++;
}
async function ir(ruta, selector = "h1") {
    // Cada enlace se abre como un documento nuevo, igual que desde el buzon.
    await comando("Page.navigate", { url: "about:blank" });
    await esperar("location.href === 'about:blank' && document.readyState === 'complete'", "preparar documento");
    await comando("Page.navigate", { url: front + ruta });
    await esperar(`location.pathname === ${JSON.stringify(ruta.split(/[?#]/)[0])} && document.readyState === "complete" && !!document.querySelector(${JSON.stringify(selector)})`, "abrir formulario");
}
async function solicitud(ruta, metodo = "GET", datos) {
    return evaluar(`(async () => {
        const { solicitarApi } = await import('/assets/api/cliente-api.js');
        return (await solicitarApi(${JSON.stringify(ruta)}, ${JSON.stringify(metodo)}, ${JSON.stringify(datos)})).datos;
    })()`);
}
async function login(correo, contrasena = clave) {
    await ir("/iniciar-sesion", "#formulario-iniciar-sesion");
    await evaluar(`(() => {
        const f = document.querySelector('#formulario-iniciar-sesion');
        f.elements.correoUsuario.value = ${JSON.stringify(correo)};
        f.elements.contrasenaUsuario.value = ${JSON.stringify(contrasena)};
        f.requestSubmit();
    })()`);
    await esperar("location.pathname === '/panel' && document.readyState === 'complete'", "inicio de sesion por formulario");
    verificaciones++;
}
async function logout() {
    await solicitud("/autenticacion/cerrar-sesion", "POST", {});
}
async function responsive(nombre) {
    for (const ancho of [320, 390, 1440]) {
        await comando("Emulation.setDeviceMetricsOverride", { width: ancho, height: 950, deviceScaleFactor: 1, mobile: ancho < 600 });
        await pausa(100);
        await comprobar("document.documentElement.scrollWidth <= window.innerWidth + 1", nombre + " sin desbordamiento a " + ancho);
        const captura = await comando("Page.captureScreenshot", { format: "png", captureBeyondViewport: false });
        await writeFile(path.join(artefactos, nombre + "-" + ancho + ".png"), Buffer.from(captura.data, "base64"));
    }
}

try {
    let puerto;
    for (let i = 0; i < 100; i++) {
        if (falloProceso) throw new Error("No se pudo iniciar Edge.");
        try { puerto = Number((await readFile(path.join(perfil, "DevToolsActivePort"), "utf8")).split("\n")[0]); break; }
        catch { await pausa(100); }
    }
    assert.ok(puerto, "Edge no habilito el puerto de pruebas.");
    const objetivo = await (await fetch(`http://127.0.0.1:${puerto}/json/new?about:blank`, { method: "PUT" })).json();
    socket = new WebSocket(objetivo.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.addEventListener("open", resolve, { once: true }); socket.addEventListener("error", reject, { once: true }); });
    socket.addEventListener("message", evento => {
        const mensaje = JSON.parse(evento.data);
        if (mensaje.method === "Network.requestWillBeSent") urlsSolicitadas.push(mensaje.params.request.url);
        if (mensaje.method === "Runtime.exceptionThrown") erroresJavascript.push(mensaje.params.exceptionDetails.text);
        const pendiente = pendientes.get(mensaje.id);
        if (!pendiente) return;
        clearTimeout(pendiente.temporizador);
        pendientes.delete(mensaje.id);
        if (mensaje.error) pendiente.reject(new Error("Error CDP: " + mensaje.error.code));
        else pendiente.resolve(mensaje.result);
    });
    await comando("Page.enable");
    await comando("Runtime.enable");
    await comando("Network.enable");

    const buzon = path.resolve(process.env.RECUPERACION_BUZON_PRUEBAS || "");
    assert.ok(buzon.startsWith(path.resolve(raiz, ".tmp-auth") + path.sep), "El buzon debe pertenecer a pruebas locales.");
    const mensajes = async () => JSON.parse(await readFile(buzon, "utf8").catch(error => {
        if (error.code === "ENOENT") return "[]";
        throw error;
    }));
    const sufijo = randomUUID().replaceAll("-", "").slice(0, 12);
    const correo = "rec-browser-" + sufijo + "@example.test";
    const nueva = "PersonalNavegador" + sufijo;
    await ir("/iniciar-sesion", "#formulario-iniciar-sesion");
    await comprobar("Array.from(document.querySelectorAll('a')).some(a => a.getAttribute('href') === '/recuperar-contrasena' && a.textContent.includes('¿Olvidaste tu contraseña?'))", "enlace publico de recuperacion");
    await solicitud("/autenticacion/registro-paciente", "POST", {
        nombresUsuario: "María", apellidosUsuario: "Muñoz", correoUsuario: correo,
        contrasenaUsuario: clave, confirmacionContrasena: clave,
        cedulaIdentidad: "REC-NAV-" + sufijo, fechaNacimiento: "2000-01-01", telefonoPaciente: "70000003",
        direccionPaciente: "Dirección de prueba", nombreContactoEmergencia: "", telefonoContactoEmergencia: "",
    });
    await ir("/recuperar-contrasena", "#formulario-recuperar-contrasena");
    await esperar("!document.querySelector('#formulario-recuperar-contrasena [type=submit]').disabled", "modulo de solicitud listo");
    await comprobar("document.body.innerText.includes('Correo electrónico registrado') && document.characterSet === 'UTF-8'", "etiquetas y codificacion");
    await evaluar("const f = document.querySelector('#formulario-recuperar-contrasena'); f.elements.correoUsuario.value = 'invalido'; f.requestSubmit()");
    await comprobar("!document.querySelector('[name=correoUsuario]').validity.valid && document.querySelector('#mensaje-formulario').hidden", "correo incorrecto se rechaza en formulario");
    await evaluar(`(() => {
        const f = document.querySelector('#formulario-recuperar-contrasena');
        f.elements.correoUsuario.value = ${JSON.stringify(correo)};
        f.elements.correoUsuario.dispatchEvent(new Event('input', { bubbles: true }));
        f.requestSubmit();
    })()`);
    await esperar("!document.querySelector('#mensaje-formulario').hidden && !document.querySelector('#formulario-recuperar-contrasena').hasAttribute('aria-busy')", "solicitud aceptada");
    await comprobar("document.querySelector('#mensaje-formulario').innerText.includes('Solicitud recibida') && document.querySelector('[name=correoUsuario]').value === ''", "respuesta generica sin revelar cuenta");
    const textoGenerico = await evaluar("document.querySelector('#mensaje-formulario').textContent");
    await responsive("recuperar-solicitud");
    const lista = await mensajes();
    const encontrados = lista.filter(m => m.destinatario === correo);
    assert.equal(encontrados.length, 1, "El transporte simulado debe capturar una sola solicitud.");
    const enlace = new URL(encontrados[0].enlace);
    assert.equal(enlace.origin, new URL(front).origin, "El enlace debe quedar en el frontend local.");
    assert.equal(enlace.search, "", "El token no debe viajar como query.");
    const token = new URLSearchParams(enlace.hash.slice(1)).get("token");
    assert.ok(/^[a-f0-9]{64}$/.test(token || ""), "Token aleatorio con formato esperado.");
    verificaciones += 4;

    await evaluar(`(() => {
        const f = document.querySelector('#formulario-recuperar-contrasena');
        f.elements.correoUsuario.value = ${JSON.stringify("sin-cuenta-" + sufijo + "@example.test")};
        f.requestSubmit();
    })()`);
    await esperar("!document.querySelector('#mensaje-formulario').hidden && !document.querySelector('#formulario-recuperar-contrasena').hasAttribute('aria-busy')", "solicitud sin cuenta");
    assert.equal(await evaluar("document.querySelector('#mensaje-formulario').textContent"), textoGenerico, "Misma respuesta para correo desconocido.");
    assert.equal((await mensajes()).length, lista.length, "El correo desconocido no genera envio.");
    verificaciones += 2;

    await ir("/recuperar-contrasena/confirmar", "#formulario-confirmar-recuperacion");
    await esperar("!document.querySelector('#mensaje-formulario').hidden", "rechazo de enlace incompleto");
    await comprobar("document.querySelector('#campos-recuperacion').disabled && document.querySelector('#mensaje-formulario').textContent.includes('inválido')", "sin token no se habilita el formulario");

    await ir(enlace.pathname + enlace.hash, "#formulario-confirmar-recuperacion");
    await esperar("!document.querySelector('#formulario-confirmar-recuperacion [type=submit]').disabled", "enlace valido cargado");
    await comprobar("location.hash === '' && location.search === '' && !document.querySelector('[name=token]')", "token retirado de URL y fuera del DOM");
    await comprobar("sessionStorage.length === 0 && localStorage.length === 0", "sin almacenamiento persistente de credenciales");
    await responsive("recuperar-confirmacion");
    async function completar(contrasena, confirmacion = contrasena) {
        await evaluar(`(() => {
            const f = document.querySelector('#formulario-confirmar-recuperacion');
            f.elements.nuevaContrasena.value = ${JSON.stringify(contrasena)};
            f.elements.confirmacionContrasena.value = ${JSON.stringify(confirmacion)};
            for (const input of f.querySelectorAll('input')) input.dispatchEvent(new Event('input', { bubbles: true }));
            f.requestSubmit();
        })()`);
    }
    await completar("corta1");
    await comprobar("!document.querySelector('[name=nuevaContrasena]').validity.valid", "clave debil rechazada");
    await completar(nueva, "ConfirmacionDiferente987");
    await comprobar("!document.querySelector('[name=confirmacionContrasena]').validity.valid", "confirmacion diferente rechazada");
    await completar(nueva);
    await esperar("!document.querySelector('#continuar-iniciar-sesion').hidden", "nueva clave guardada");
    await comprobar("document.querySelector('#campos-recuperacion').disabled && document.querySelector('[name=nuevaContrasena]').value === '' && document.querySelector('[name=confirmacionContrasena]').value === ''", "limpieza de claves al guardar");
    await comprobar("document.querySelector('#mensaje-formulario').innerText.includes('Contraseña actualizada')", "resultado visible con acentos");
    await responsive("recuperar-completada");
    await login(correo, nueva);
    await comprobar("JSON.parse(document.body.dataset.usuario).correoUsuario === " + JSON.stringify(correo), "inicio real con nueva clave");
    await logout();

    // El enlace ya utilizado vuelve a cargar la pantalla, pero el servidor impide repetir el cambio.
    await ir(enlace.pathname + enlace.hash, "#formulario-confirmar-recuperacion");
    await esperar("!document.querySelector('#formulario-confirmar-recuperacion [type=submit]').disabled", "volver a abrir enlace consumido");
    await completar("OtraPersonalNavegador" + sufijo);
    await esperar("!document.querySelector('#mensaje-formulario').hidden && !document.querySelector('#formulario-confirmar-recuperacion').hasAttribute('aria-busy')", "rechazo de token consumido");
    await comprobar("document.querySelector('#mensaje-formulario').textContent.includes('inválido') && document.querySelector('#continuar-iniciar-sesion').hidden", "token de un solo uso rechazado por backend");
    assert.ok(urlsSolicitadas.every(url => !url.includes(token)), "Ninguna solicitud de red debe incluir el token en su URL.");
    assert.deepEqual(erroresJavascript, [], "No debe haber excepciones JavaScript.");
    verificaciones += 2;
    console.log(`OK: ${verificaciones} comprobaciones Edge de recuperacion; formularios/API/PostgreSQL reales, correo simulado; 320, 390 y 1440 px.`);
    console.log("Capturas locales: " + path.relative(raiz, artefactos));
} finally {
    if (socket?.readyState === WebSocket.OPEN) {
        try { socket.send(JSON.stringify({ id: ++secuencia, method: "Browser.close" })); } catch { /* Edge pudo terminar. */ }
        await pausa(500);
        socket.close();
    }
    for (const pendiente of pendientes.values()) clearTimeout(pendiente.temporizador);
    if (proceso.exitCode === null) proceso.kill();
}

