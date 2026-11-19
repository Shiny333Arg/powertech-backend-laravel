/**
 * Cliente de la API para el panel de gestión.
 *
 * El requisito 8 de la consigna pide que "la comunicación deberá centralizarse
 * para incorporar la dirección de la API, el token y el tratamiento de errores,
 * evitando repetir esa lógica en cada pantalla". Esto es ese lugar.
 *
 * En F2 atiende tres llamadas —ingresar, consultar el usuario y cerrar sesión—.
 * A partir de F3, cuando los listados y formularios dejen de ser Blade
 * tradicional, crece sin cambiar de forma: `pedir()` ya resuelve la cabecera de
 * autorización, el CSRF y el mapeo de errores.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * DÓNDE VIVE EL TOKEN
 * ──────────────────────────────────────────────────────────────────────────
 *
 * En `enMemoria`, una variable de módulo. Nada más.
 *
 * NO va a localStorage, NO va a sessionStorage, NO va a una cookie propia, NO
 * va a un campo oculto ni a un atributo data- del DOM, NO va en ninguna URL. El
 * requisito 6 pide que en la administración el token se mantenga "en memoria".
 *
 * Cada recarga completa de página borra esta variable, y la página siguiente la
 * vuelve a crear porque el layout llama a `iniciar()` con el valor que guarda la
 * sesión del servidor. Eso es lo que permite que las 82 vistas sigan navegando
 * con enlaces tradicionales sin pedir el ingreso en cada clic.
 *
 * Que esté en memoria no protege contra XSS —cualquier script inyectado en la
 * página puede leerla—; lo que evita es la persistencia: cerrada la pestaña, en
 * el navegador no queda nada. La defensa contra XSS es el escapado de Blade.
 */

let enMemoria = null;

/** Lo llama layouts/gestion.blade.php en cada render. */
export function iniciar(token) {
    enMemoria = typeof token === 'string' && token !== '' ? token : null;
}

/** Para que una pantalla sepa si tiene credencial antes de pedir algo. */
export function hayToken() {
    return enMemoria !== null;
}

/** Error de la API con el código estable que manda el backend. */
export class ErrorDeApi extends Error {
    constructor(estado, cuerpo) {
        super(cuerpo?.message ?? 'No pudimos completar la operación.');
        this.name = 'ErrorDeApi';
        this.estado = estado;
        this.codigo = cuerpo?.codigo ?? null;
        this.errores = cuerpo?.errors ?? null;
    }
}

const BASE = '/api/v1';

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Una petición a la API.
 *
 * @param {object} opciones
 * @param {boolean} opciones.conToken  manda Authorization: Bearer. Por defecto
 *                                     sí, salvo las excepciones comentadas en
 *                                     cada función de abajo.
 * @param {boolean} opciones.conCsrf   manda X-CSRF-TOKEN y cookies. Hace falta
 *                                     cuando la petición se autentica por sesión.
 */
async function pedir(metodo, ruta, { cuerpo = null, conToken = true, conCsrf = false } = {}) {
    const cabeceras = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (cuerpo !== null) {
        cabeceras['Content-Type'] = 'application/json';
    }

    if (conToken && enMemoria !== null) {
        cabeceras.Authorization = `Bearer ${enMemoria}`;
    }

    if (conCsrf) {
        cabeceras['X-CSRF-TOKEN'] = csrf();
    }

    const respuesta = await fetch(ruta.startsWith('/') ? ruta : `${BASE}/${ruta}`, {
        method: metodo,
        headers: cabeceras,
        body: cuerpo !== null ? JSON.stringify(cuerpo) : undefined,
        credentials: conCsrf ? 'same-origin' : 'omit',
    });

    const texto = await respuesta.text();
    const datos = texto !== '' ? JSON.parse(texto) : null;

    if (! respuesta.ok) {
        throw new ErrorDeApi(respuesta.status, datos);
    }

    return datos;
}

/**
 * Qué hacer con un error, según la política única del backend.
 *
 *   401 = la credencial ya no sirve  -> pedir el ingreso otra vez
 *   403 = la credencial es buena pero no alcanza -> informar falta de permiso
 *
 * Es literalmente lo que pide el requisito 8: "ante un 401 solicitará
 * nuevamente el ingreso; ante un 403 informará que el usuario no dispone de
 * permiso". Se resuelve una vez acá y no pantalla por pantalla.
 *
 * Devuelve el mensaje a mostrar, o null si ya redirigió.
 */
export function tratar(error) {
    if (! (error instanceof ErrorDeApi)) {
        return 'No pudimos conectarnos con el servidor. Revisá tu conexión.';
    }

    if (error.estado === 401) {
        enMemoria = null;
        window.location.assign('/gestion/login');
        return null;
    }

    if (error.estado === 419) {
        window.location.reload();
        return null;
    }

    if (error.estado === 403) {
        return error.message || 'No tenés permiso para esta operación.';
    }

    return error.message;
}

// ──────────────────────────────────────────────────────────────────────────
// Las tres llamadas de F2
// ──────────────────────────────────────────────────────────────────────────

/**
 * Valida credenciales. El ÚNICO lugar del sistema al que viajan.
 *
 * `conToken: false` porque todavía no hay ninguno. No manda `ambito`: el
 * servidor lo deriva del rol, que es lo único que puede hacerse con un solo
 * juego de credenciales compartido entre empleados y clientes.
 */
export function ingresar(email, password) {
    return pedir('POST', 'auth/login', {
        cuerpo: { email, password, dispositivo: navegador() },
        conToken: false,
    });
}

/**
 * Cambia el token por una sesión Blade, para que sobreviva a las recargas.
 *
 * Manda el token recién emitido como Bearer —no el de memoria, que todavía no
 * existe— y el CSRF, porque es una ruta del grupo `web`.
 */
export async function abrirSesionWeb(token) {
    const anterior = enMemoria;
    enMemoria = token;

    try {
        return await pedir('POST', '/gestion/sesion', { conCsrf: true });
    } catch (error) {
        enMemoria = anterior;
        throw error;
    }
}

/** El usuario autenticado, su rol y sus permisos. Mismo endpoint que Flutter. */
export function yo() {
    return pedir('GET', 'auth/yo');
}

/**
 * Cierra la sesión: revoca el token y destruye la sesión web, en una sola
 * operación y contra el mismo endpoint que usa Flutter.
 *
 * <b>`conToken: false` es deliberado y es el punto más importante de este
 * archivo.</b> El endpoint tiene dos modos y elige por la presencia de la
 * cabecera Authorization: con Bearer revoca y nada más —es lo que necesita
 * Flutter—; sin Bearer se autentica por la cookie de sesión, revoca el token que
 * guardó la sesión Y destruye la sesión. El panel necesita lo segundo, así que
 * no manda el token aunque lo tenga a mano.
 *
 * Si la revocación falla, el backend no destruye la sesión: el empleado queda
 * adentro y puede reintentar, en lugar de quedarse afuera con el token vivo.
 */
export function cerrarSesion() {
    return pedir('POST', 'auth/logout', { conToken: false, conCsrf: true });
}

function navegador() {
    const agente = navigator.userAgent ?? '';

    const nombre = ['Edg', 'Chrome', 'Firefox', 'Safari'].find((n) => agente.includes(n));

    return `Panel de gestión (${nombre ?? 'navegador'})`;
}
