let enMemoria =
    typeof window !== 'undefined' &&
    typeof window.__POWERTECH_TOKEN__ === 'string'
        ? window.__POWERTECH_TOKEN__
        : null;

export function iniciar(token) {
    enMemoria = typeof token === 'string' && token !== ''
        ? token
        : null;
}

export function hayToken() {
    return enMemoria !== null;
}

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

async function pedir(metodo, ruta, {
    cuerpo = null,
    conToken = true,
    conCsrf = false
} = {}) {
    

    const cabeceras = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'ngrok-skip-browser-warning': 'true',
    };

    // Con FormData no se declara Content-Type: lo pone el navegador, que es
    // el unico que conoce el boundary del multipart. Declararlo a mano deja el
    // cuerpo sin boundary y PHP no lo parsea.
    const esFormulario = typeof FormData !== 'undefined' && cuerpo instanceof FormData;

    if (cuerpo !== null && !esFormulario) {
        cabeceras['Content-Type'] = 'application/json';
    }

    if (conToken && enMemoria !== null) {
        cabeceras.Authorization = `Bearer ${enMemoria}`;
    }

    if (conCsrf) {
        cabeceras['X-CSRF-TOKEN'] = csrf();
    }

    const respuesta = await fetch(
        ruta.startsWith('/') ? ruta : `${BASE}/${ruta}`,
        {
            method: metodo,
            headers: cabeceras,
            body: cuerpo === null
                ? undefined
                : (esFormulario ? cuerpo : JSON.stringify(cuerpo)),
            credentials: conCsrf ? 'same-origin' : 'omit',
        }
    );

    const texto = await respuesta.text();

    // Las subidas de imagen pueden chocar contra limites del servidor
    // (upload_max_filesize, client_max_body_size) que contestan HTML o un
    // cuerpo vacio. Sin este try, eso llegaria como SyntaxError y el error
    // real quedaria tapado.
    let datos = null;

    if (texto !== '') {
        try {
            datos = JSON.parse(texto);
        } catch {
            datos = null;
        }
    }

    if (!respuesta.ok) {
        throw new ErrorDeApi(respuesta.status, datos);
    }

    return datos;
}

export function tratar(error) {
    if (!(error instanceof ErrorDeApi)) {
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

export function obtener(ruta, parametros = {}) {
    const consulta = new URLSearchParams(parametros).toString();
    return pedir('GET', consulta ? `${ruta}?${consulta}` : ruta);
}

export function crear(ruta, cuerpo) {
    return pedir('POST', ruta, { cuerpo });
}

/**
 * POST multipart/form-data: el unico camino cuando viaja un archivo.
 *
 * Tambien se usa para editar con imagen, con _method=PUT adentro del
 * FormData: PHP no parsea el cuerpo multipart de un PUT. Es la misma
 * convencion que ya usaba el formulario Blade con @method('PUT') y la que
 * documentan las rutas de productos.
 */
export function enviarFormulario(ruta, formulario) {
    return pedir('POST', ruta, { cuerpo: formulario });
}

export function reemplazar(ruta, cuerpo) {
    return pedir('PUT', ruta, { cuerpo });
}

export function parchear(ruta, cuerpo) {
    return pedir('PATCH', ruta, { cuerpo });
}

export function eliminar(ruta) {
    return pedir('DELETE', ruta);
}

export function ingresar(email, password) {
    return pedir('POST', 'auth/login', {
        cuerpo: { email, password },
        conToken: false,
    });
}

export async function abrirSesionWeb(token) {
    const anterior = enMemoria;
    enMemoria = token;

    try {
        return await pedir('POST', '/gestion/sesion', {
            conCsrf: true,
        });
    } catch (error) {
        enMemoria = anterior;
        throw error;
    }
}

export function yo() {
    return pedir('GET', 'auth/yo');
}

export function cerrarSesion() {
    return pedir('POST', 'auth/logout', {
        conToken: false,
        conCsrf: true,
    });
}
