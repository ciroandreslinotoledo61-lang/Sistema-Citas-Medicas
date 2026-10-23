/** Comportamientos visuales compartidos. No administra sesiones ni permisos. */
export function inicializarInterfaz() {
    configurarMenuMovil();
    configurarVisibilidadClave();
}

function configurarMenuMovil() {
    const navegacion = document.querySelector("#navegacion-lateral");
    const botonAbrir = document.querySelector("#abrir-navegacion");
    const botonCerrar = document.querySelector("#cerrar-navegacion");
    const fondo = document.querySelector("#fondo-navegacion");
    const areaAplicacion = document.querySelector("#area-aplicacion");
    if (!navegacion || !botonAbrir) return;

    const pantallaMovil = window.matchMedia("(max-width: 960px)");
    let menuAbierto = false;

    function actualizarMenu(abrir, devolverFoco = false) {
        menuAbierto = pantallaMovil.matches && abrir;
        document.body.classList.toggle("menu-abierto", menuAbierto);
        botonAbrir.setAttribute("aria-expanded", String(menuAbierto));
        fondo.hidden = !menuAbierto;
        areaAplicacion.inert = menuAbierto;
        navegacion.inert = pantallaMovil.matches && !menuAbierto;
        if (menuAbierto) botonCerrar.focus();
        else if (devolverFoco) botonAbrir.focus();
    }

    document.body.classList.add("navegacion-interactiva");
    botonAbrir.addEventListener("click", () => actualizarMenu(true));
    botonCerrar.addEventListener("click", () => actualizarMenu(false, true));
    fondo.addEventListener("click", () => actualizarMenu(false, true));
    pantallaMovil.addEventListener("change", () => {
        const focoEnMenu = navegacion.contains(document.activeElement);
        actualizarMenu(false, pantallaMovil.matches && focoEnMenu);
    });
    document.addEventListener("keydown", (evento) => {
        if (!menuAbierto) return;
        if (evento.key === "Escape") {
            evento.preventDefault();
            actualizarMenu(false, true);
        }
        if (evento.key === "Tab") {
            const elementos = [...navegacion.querySelectorAll("a[href], button:not(:disabled), summary")]
                .filter((elemento) => elemento.getClientRects().length > 0);
            const primero = elementos[0];
            const ultimo = elementos[elementos.length - 1];
            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault(); ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault(); primero.focus();
            }
        }
    });
    actualizarMenu(false);
}

function configurarVisibilidadClave() {
    const boton = document.querySelector("#alternar-clave");
    const campo = document.querySelector("#clave-acceso");
    if (!boton || !campo) return;
    boton.hidden = false;
    boton.addEventListener("click", () => {
        const mostrarClave = campo.type === "password";
        campo.type = mostrarClave ? "text" : "password";
        boton.textContent = mostrarClave ? "Ocultar" : "Mostrar";
        boton.setAttribute("aria-label", mostrarClave ? "Ocultar clave de acceso" : "Mostrar clave de acceso");
        boton.setAttribute("aria-pressed", String(mostrarClave));
    });
}
