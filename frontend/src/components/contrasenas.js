/** Validacion visual; el servidor vuelve a comprobar todas las reglas. */
export function configurarValidacionContrasenas(formulario, nombreClave, exigirSeguridad = false) {
    const clave = formulario.elements.namedItem(nombreClave);
    const confirmacion = formulario.elements.namedItem("confirmacionContrasena");
    for (const campo of formulario.querySelectorAll("input")) {
        campo.addEventListener("input", () => {
            campo.setCustomValidity("");
            campo.removeAttribute("aria-invalid");
            // Una correccion de la clave tambien puede resolver la confirmacion.
            confirmacion.setCustomValidity("");
            confirmacion.removeAttribute("aria-invalid");
        });
    }
    return () => {
        clave.setCustomValidity("");
        confirmacion.setCustomValidity("");
        const bytes = new TextEncoder().encode(clave.value).length;
        let error = "";
        if (bytes < 12 || bytes > 72 || clave.value.includes("\0")) {
            error = "La contrasena debe ocupar entre 12 y 72 bytes. Los acentos pueden ocupar mas de un byte.";
        } else if (exigirSeguridad && (!/\p{L}/u.test(clave.value) || !/[0-9]/.test(clave.value) || /^(.{1,6})\1+$/su.test(clave.value))) {
            error = "Usa una frase larga con letras y numeros, sin patrones repetidos.";
        }
        clave.setCustomValidity(error);
        if (clave.value !== confirmacion.value) {
            confirmacion.setCustomValidity("La confirmacion debe coincidir con la nueva contrasena.");
        }
        for (const campo of [clave, confirmacion]) {
            campo.setAttribute("aria-invalid", String(!campo.validity.valid));
        }
        return formulario.reportValidity();
    };
}

export function marcarErroresContrasena(formulario, error) {
    for (const nombre of Object.keys(error.erroresCampos || {})) {
        const campo = formulario.elements.namedItem(nombre);
        if (campo instanceof HTMLInputElement) {
            campo.setAttribute("aria-invalid", "true");
        }
    }
    formulario.querySelector('[aria-invalid="true"]')?.focus();
}

export function limpiarContrasenas(formulario) {
    for (const campo of formulario.querySelectorAll(".campo-clave input")) {
        campo.value = "";
        campo.type = "password";
        campo.removeAttribute("aria-invalid");
        campo.setCustomValidity("");
    }
    for (const boton of formulario.querySelectorAll("[data-alternar-clave]")) {
        boton.textContent = "Mostrar";
        boton.setAttribute("aria-pressed", "false");
        boton.setAttribute("aria-label", "Mostrar contrasena");
    }
}
