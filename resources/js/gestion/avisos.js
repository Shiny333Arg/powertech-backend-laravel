/**
 * Los avisos del panel: los mismos que mostraba `session('exito')` y
 * `session('error')` en las vistas Blade.
 *
 * Cuando el alta y la edición dejaron de ser POST tradicionales, dejó de haber
 * un redirect del servidor que pudiera arrastrar un `with('exito', ...)`. El
 * mensaje lo arma ahora la pantalla y lo pinta este módulo, con el MISMO
 * marcado de Bootstrap que usaban las vistas, para que la administración se
 * vea igual que antes.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EL AVISO QUE SOBREVIVE A UNA NAVEGACIÓN
 * ──────────────────────────────────────────────────────────────────────────
 *
 * «Se creó la categoría «Monitores».» se decide en el formulario y se muestra
 * en el listado, que es otra página. Antes eso lo resolvía el flash de la
 * sesión del servidor; acá lo resuelve `sessionStorage`, de donde el listado
 * lo saca UNA vez y lo borra.
 *
 * <b>Que esto no se confunda con el token.</b> El token no puede vivir en
 * ningún almacenamiento del navegador (requisito 6, ver `api.js`); un cartel
 * verde con el nombre de una categoría no es una credencial ni un dato
 * privado: es el texto que el empleado acaba de escribir. Si el navegador no
 * deja escribir —modo privado restrictivo—, el aviso se pierde y la pantalla
 * funciona igual.
 */

const CLAVE = 'powertech.gestion.aviso';

/** Las dos clases de alerta que ya usaban las vistas. */
const CLASES = {
    exito: 'alert-success',
    error: 'alert-danger',
};

/**
 * Deja un aviso para la PRÓXIMA página.
 *
 * @param {'exito'|'error'} tipo
 * @param {string} texto
 */
export function recordar(tipo, texto) {
    try {
        sessionStorage.setItem(CLAVE, JSON.stringify({ tipo, texto }));
    } catch {
        // Sin almacenamiento el aviso se pierde y no pasa nada más: la
        // operación ya se completó contra la API.
    }
}

/**
 * Devuelve el aviso pendiente y lo borra, para que no reaparezca al recargar.
 *
 * @returns {{tipo: string, texto: string}|null}
 */
export function tomar() {
    try {
        const guardado = sessionStorage.getItem(CLAVE);

        sessionStorage.removeItem(CLAVE);

        return guardado === null ? null : JSON.parse(guardado);
    } catch {
        return null;
    }
}

/**
 * Pinta un aviso dentro del contenedor, reemplazando al anterior.
 *
 * El marcado es el de las vistas: alerta de Bootstrap descartable, con el
 * texto como `textContent` —nunca `innerHTML`— porque puede venir del nombre
 * que cargó un empleado o del mensaje de un error de la API.
 *
 * @param {HTMLElement|null} contenedor
 * @param {'exito'|'error'} tipo
 * @param {string} texto
 */
export function mostrar(contenedor, tipo, texto) {
    if (! contenedor || ! texto) {
        return;
    }

    const alerta = document.createElement('div');

    alerta.className = `alert ${CLASES[tipo] ?? CLASES.error} alert-dismissible fade show`;
    alerta.setAttribute('role', 'alert');
    alerta.textContent = texto;

    const cerrar = document.createElement('button');

    cerrar.type = 'button';
    cerrar.className = 'btn-close';
    cerrar.setAttribute('data-bs-dismiss', 'alert');
    cerrar.setAttribute('aria-label', 'Cerrar');

    alerta.append(cerrar);

    contenedor.replaceChildren(alerta);
}

/** Saca el aviso en pantalla, si hay alguno. */
export function limpiar(contenedor) {
    contenedor?.replaceChildren();
}

/** Muestra el aviso que haya dejado la página anterior. */
export function mostrarPendiente(contenedor) {
    const aviso = tomar();

    if (aviso !== null) {
        mostrar(contenedor, aviso.tipo, aviso.texto);
    }
}
