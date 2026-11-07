// Navegador real, frontend/API reales y datos sinteticos del cluster aislado.
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
const artefactos = path.resolve(raiz, ".tmp-auth", "navegador-" + randomUUID());
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
    await comando("Page.navigate", { url: front + ruta });
    await esperar(`location.pathname === ${JSON.stringify(ruta.split("?")[0])} && document.readyState === "complete" && !!document.querySelector(${JSON.stringify(selector)})`, "abrir " + ruta);
}
async function solicitud(ruta, metodo = "GET", datos) {
    return evaluar(`(async () => {
        const { solicitarApi } = await import('/assets/api/cliente-api.js');
        return (await solicitarApi(${JSON.stringify(ruta)}, ${JSON.stringify(metodo)}, ${JSON.stringify(datos)})).datos;
    })()`);
}
async function login(correo) {
    await ir("/iniciar-sesion", "#formulario-iniciar-sesion");
    await evaluar(`(() => {
        const f = document.querySelector('#formulario-iniciar-sesion');
        f.elements.correoUsuario.value = ${JSON.stringify(correo)};
        f.elements.contrasenaUsuario.value = ${JSON.stringify(clave)};
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

    // Los cuatro roles conservan su formulario personal y las pantallas de citas del personal.
    for (const rol of ["recepcionista", "medico", "paciente", "administrador"]) {
        await login(rol + "@vidasalud.test");
        await ir("/cambiar-contrasena", "#formulario-cambiar-contrasena");
        await comprobar("['contrasenaActual','nuevaContrasena','confirmacionContrasena'].every(n => document.querySelector('[name=\"' + n + '\"]'))", "cambio propio del rol " + rol);
        if (rol !== "paciente") {
            await ir("/citas", "#filas-citas");
            await esperar("!document.querySelector('#filas-citas').hasAttribute('aria-busy')", "agenda del personal");
            await comprobar("!!document.querySelector('table') && !document.querySelector('#citas-paciente')", "tabla conservada para " + rol);
        }
        if (rol !== "administrador") await logout();
    }
    const medicos = await solicitud("/medicos?pagina=1");
    const idMedico = medicos.elementos[0].idMedico;
    const dia = new Date(Date.now() + 24 * 86400000).toISOString().slice(0, 10);
    await solicitud("/horarios", "POST", { idMedico, fechaAtencion: dia, horaInicio: "08:00", horaFin: "10:00" });
    await ir("/usuarios", "#filas-usuarios");
    await esperar("!document.querySelector('#filas-usuarios').hasAttribute('aria-busy')", "cuentas administrativas");
    await comprobar("Array.from(document.querySelectorAll('#filas-usuarios a')).some(a => a.textContent.includes('Restablecer contraseña'))", "accion administrativa clara");
    await logout();

    const sufijo = randomUUID().replaceAll("-", "").slice(0, 12);
    const correo = "navegador-" + sufijo + "@example.test";
    await solicitud("/autenticacion/registro-paciente", "POST", {
        nombresUsuario: "María", apellidosUsuario: "Muñoz", correoUsuario: correo,
        contrasenaUsuario: clave, confirmacionContrasena: clave,
        cedulaIdentidad: "NAV-" + sufijo, fechaNacimiento: "2000-01-01", telefonoPaciente: "70000003",
        direccionPaciente: "Dirección de prueba", nombreContactoEmergencia: "", telefonoContactoEmergencia: "",
    });
    await login(correo);
    await ir("/citas", "#citas-paciente");
    await esperar("!document.querySelector('#tarjetas-citas').hasAttribute('aria-busy')", "citas vacias");
    await comprobar("document.querySelector('h1').textContent === 'Mis citas médicas' && document.body.innerText.includes('No tienes próximas citas')", "titulo y estado vacio");
    await comprobar("!document.querySelector('[name=idPaciente]') && !document.querySelector('table')", "sin filtros administrativos de pacientes");
    await responsive("citas-vacias");

    // Reservar con el formulario real y cancelar con su confirmacion real.
    await ir("/citas/nueva", "#formulario-cita");
    await esperar("!document.querySelector('#campos-cita').disabled", "formulario de reserva");
    await evaluar(`(() => {
        const f = document.querySelector('#formulario-cita');
        f.elements.idMedico.value = ${JSON.stringify(String(idMedico))};
        f.elements.fecha.value = ${JSON.stringify(dia)};
        f.elements.fecha.dispatchEvent(new Event('change', { bubbles: true }));
    })()`);
    await esperar("document.querySelectorAll('input[name=idTurno]').length === 4", "turnos de treinta minutos");
    await evaluar("document.querySelector('input[name=idTurno]').click()");
    await esperar("!document.querySelector('#guardar-cita').disabled", "reserva habilitada");
    await evaluar("document.querySelector('#formulario-cita').requestSubmit()");
    await esperar("/^\\/citas\\/[1-9][0-9]*$/.test(location.pathname) && !!document.querySelector('#detalle-cita') && !document.querySelector('#detalle-cita').hasAttribute('aria-busy')", "detalle de reserva creada");
    const idCita = await evaluar("Number(document.body.dataset.idRegistro)");
    verificaciones++;
    await ir("/citas", "#citas-paciente");
    await esperar("document.querySelectorAll('.tarjeta-cita').length === 1", "tarjeta proxima cita");
    await comprobar("document.querySelector('.tarjeta-cita').innerText.includes('Medicina general') && document.querySelector('.tarjeta-cita').innerText.includes('08:00')", "especialidad y hora de tarjeta");
    await responsive("citas-proximas");
    await evaluar("document.querySelector('.tarjeta-cita .cancelar-cita-tarjeta').click()");
    await esperar("!!document.querySelector('#formulario-anulacion') && !document.querySelector('#formulario-anulacion').hidden", "confirmacion de cancelacion");
    await evaluar("document.querySelector('#formulario-anulacion').elements.motivoCancelacion.value = 'Cambio de planes'; document.querySelector('#formulario-anulacion').requestSubmit()");
    await esperar("document.querySelector('#estado-cita').innerText.includes('Cancelada')", "cancelacion guardada");
    await comprobar("document.querySelector('#mostrar-anulacion').hidden", "no cancelar dos veces");
    await ir("/citas", "#citas-paciente");
    await esperar("!document.querySelector('#tarjetas-citas').hasAttribute('aria-busy')", "proximas vacias tras cancelar");
    await comprobar("document.querySelectorAll('.tarjeta-cita').length === 0", "cancelada fuera de proximas");
    await evaluar("document.querySelector('#pestana-historial').click()");
    await esperar("document.querySelectorAll('.tarjeta-cita').length === 1", "historial de cancelacion");
    await comprobar(`document.querySelector('.tarjeta-cita a').getAttribute('href') === '/citas/${idCita}' && document.querySelector('.tarjeta-cita').innerText.includes('Cancelada')`, "historial propio y detalles");
    await responsive("citas-historial");
    await evaluar("const f = document.querySelector('#filtros-historial'); f.elements.busqueda.value = 'Sin coincidencias'; f.requestSubmit()");
    await esperar("!document.querySelector('#tarjetas-citas').hasAttribute('aria-busy') && document.querySelectorAll('.tarjeta-cita').length === 0", "filtro simple de historial");
    verificaciones++;
    await ir("/historial-clinico", "#filas-consultas");
    await esperar("!document.querySelector('#historia-clinica').hidden", "historial clinico propio");
    await comprobar("document.body.innerText.includes('María Muñoz') && !document.querySelector('[name=busqueda]') && !document.querySelector('[name=idPaciente]')", "identidad propia con acentos y sin buscador de pacientes");
    await responsive("historial-clinico");
    assert.deepEqual(erroresJavascript, [], "No debe haber excepciones JavaScript.");
    verificaciones++;
    console.log(`OK: ${verificaciones} comprobaciones Edge con frontend/API/PostgreSQL reales; vistas a 320, 390 y 1440 px.`);
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
