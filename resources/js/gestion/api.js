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

    if (cuerpo !== null) {
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
            body: cuerpo !== null ? JSON.stringify(cuerpo) : undefined,
            credentials: conCsrf ? 'same-origin' : 'omit',
        }
    );

    const texto = await respuesta.text();
    const datos = texto !== '' ? JSON.parse(texto) : null;

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
