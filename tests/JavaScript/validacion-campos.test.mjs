import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const fuente = readFileSync(new URL('../../resources/js/validacion-campos.js', import.meta.url), 'utf8')
    .replace(/^import \{ Toast \} from 'bootstrap';\r?\n/, '')
    .replace('export function inicializarValidacionCampos()', 'function inicializarValidacionCampos()');

class Elemento {
    constructor(propiedades = {}) {
        Object.assign(this, { value: '', textContent: '', dataset: {}, disabled: false, required: false }, propiedades);
        this.atributos = new Map();
        this.eventos = new Map();
        const clases = new Set();
        this.classList = {
            contains: (clase) => clases.has(clase),
            toggle: (clase, activo) => activo ? clases.add(clase) : clases.delete(clase),
        };
    }
    addEventListener(evento, manejar) {
        const manejadores = this.eventos.get(evento) || [];
        manejadores.push(manejar);
        this.eventos.set(evento, manejadores);
    }
    emitir(evento) { this.eventos.get(evento)?.forEach((manejar) => manejar({ target: this })); }
    setAttribute(nombre, valor) { this.atributos.set(nombre, valor); }
    removeAttribute(nombre) { this.atributos.delete(nombre); }
    focus() { this.enfocado = true; }
}

function relojManual() {
    let ahora = 0;
    let siguiente = 0;
    const pendientes = new Map();
    return {
        setTimeout: (ejecutar, demora) => {
            const id = ++siguiente;
            pendientes.set(id, { ejecutar, fecha: ahora + demora });
            return id;
        },
        clearTimeout: (id) => pendientes.delete(id),
        avanzar: (milisegundos) => {
            const limite = ahora + milisegundos;
            for (;;) {
                const siguiente = [...pendientes.entries()].sort((a, b) => a[1].fecha - b[1].fecha)[0];
                if (!siguiente || siguiente[1].fecha > limite) break;
                const [id, tarea] = siguiente;
                ahora = tarea.fecha;
                pendientes.delete(id);
                tarea.ejecutar();
            }
            ahora = limite;
        },
    };
}

function entorno({ tipo = 'dpi', documento = '', nombre = '', contexto = 'crear', error = '' } = {}) {
    const reloj = relojManual();
    const selector = new Elemento({ value: tipo });
    const titular = new Elemento({ value: nombre, name: 'nombre' });
    const entrada = new Elemento({
        value: documento, name: tipo, required: true,
        dataset: { validacion: 'documento', tipoOriginal: tipo, documentoOriginal: documento },
    });
    const telefono = new Elemento({ name: 'telefono', dataset: { validacion: 'telefono', validacionMax: '25' } });
    const correo = new Elemento({ name: 'email', required: true, dataset: { validacion: 'email', validacionMax: '150' } });
    const campos = [entrada, telefono, correo];
    const errores = new Map(campos.map((campo) => [campo.dataset.validacion, new Elemento()]));
    const mensajes = new Map(campos.map((campo) => [campo.dataset.validacion, new Elemento()]));
    errores.get('documento').textContent = error;
    const form = new Elemento({ dataset: {
        contextoCliente: contexto, consultaDocumentoUrl: '/clientes/consultar-documento', validacionCorreoUrl: '/usuarios/validar-correo',
    } });
    form.querySelector = (consulta) => {
        if (consulta === '[data-tipo-documento]') return selector;
        if (consulta === '[name="nombre"]') return titular;
        if (consulta === '[name="_token"]') return { value: 'token-prueba' };
        const atributo = consulta.match(/^\[data-(feedback|error)-campo="(.+)"\]$/);
        return atributo ? (atributo[1] === 'feedback' ? mensajes : errores).get(atributo[2]) : null;
    };
    form.querySelectorAll = (consulta) => consulta === '[data-validacion]' ? campos
        : consulta === '[data-error-campo]' ? [...errores.values()] : [];
    campos.forEach((campo) => { campo.form = form; });

    const titulo = new Elemento();
    const texto = new Elemento();
    const aviso = new Elemento();
    aviso.querySelector = (consulta) => consulta === '[data-titulo-aviso]' ? titulo : texto;
    const inserciones = [];
    const notificaciones = [];
    const peticiones = [];
    const ventana = new Elemento();
    const documentoDom = {
        body: { insertAdjacentHTML: (posicion, html) => inserciones.push({ posicion, html }) },
        getElementById: () => aviso,
        querySelectorAll: () => [form],
    };
    const contextoVm = vm.createContext({
        document: documentoDom, window: ventana, AbortController,
        setTimeout: reloj.setTimeout, clearTimeout: reloj.clearTimeout,
        MutationObserver: class { observe() {} },
        Toast: class {
            show() {
                aviso.classList.toggle('show', true);
                notificaciones.push({ titulo: titulo.textContent, mensaje: texto.textContent });
            }
            hide() { aviso.classList.toggle('show', false); }
        },
        fetch: (url, opciones) => new Promise((resolve, reject) => {
            peticiones.push({ url, opciones, resolve, reject });
        }),
    });
    vm.runInContext(fuente, contextoVm, { filename: 'validacion-campos.js' });
    const iniciar = () => vm.runInContext('inicializarValidacionCampos()', contextoVm);
    iniciar();
    const responder = async (indice, datos, status = 200) => {
        peticiones[indice].resolve({ status, ok: status >= 200 && status < 300, redirected: false, json: async () => datos });
        await new Promise(setImmediate);
    };
    const escribir = (campo, valor, evento = 'input') => { campo.value = valor; campo.emitir(evento); };
    return { entrada, selector, titular, telefono, correo, errores, mensajes, form, aviso, titulo, texto,
        inserciones, notificaciones, peticiones, ventana, reloj, iniciar, responder, escribir };
}

test('los mensajes se muestran en una notificación flotante sin agregar filas al formulario', async () => {
    const e = entorno();
    e.iniciar();
    assert.equal(e.inserciones.length, 1, 'La inicialización repetida no duplica el aviso');
    assert.match(e.inserciones[0].html, /toast-container position-fixed/);
    const parcial = readFileSync(new URL('../../resources/views/partials/validacion-campo.blade.php', import.meta.url), 'utf8');
    assert.equal((parcial.match(/class="visually-hidden"/g) || []).length, 2);
    assert.doesNotMatch(parcial, /<(?:div|p|br)\b/);

    e.escribir(e.entrada, '123');
    assert.equal(e.entrada.atributos.get('aria-invalid'), 'true');
    assert.equal(e.notificaciones.length, 0);
    e.reloj.avanzar(349);
    assert.equal(e.notificaciones.length, 0);
    e.reloj.avanzar(1);
    assert.match(e.texto.textContent, /exactamente 13 dígitos/);
    assert.equal(e.aviso.classList.contains('border-danger'), true);
    assert.equal(e.peticiones.length, 0);

    e.escribir(e.entrada, '1234567890101', 'blur');
    await e.responder(0, { encontrado: false });
    assert.equal(e.entrada.classList.contains('is-valid'), true);
    assert.match(e.texto.textContent, /Formato de DPI válido/);
    assert.equal(e.aviso.classList.contains('border-success'), true);
});

test('cambiar el selector limpia el documento y adapta nombre, longitud y teclado', () => {
    const e = entorno({ documento: '1234567890101' });
    e.selector.value = 'nit';
    e.selector.emitir('change');
    assert.equal(e.entrada.value, '');
    assert.equal(e.entrada.name, 'nit');
    assert.equal(e.entrada.maxLength, 14);
    assert.equal(e.entrada.inputMode, 'text');
    assert.equal(e.entrada.enfocado, true);
    assert.equal(e.entrada.classList.contains('is-invalid'), false);
    assert.match(e.texto.textContent, /Ingrese el NIT/);
    e.selector.value = 'dpi';
    e.selector.emitir('change');
    assert.equal(e.entrada.name, 'dpi');
    assert.equal(e.entrada.maxLength, 13);
    assert.equal(e.entrada.inputMode, 'numeric');
    assert.equal(e.peticiones.length, 0);
});

test('DPI y NIT distinguen longitud y caracteres sin afirmar verificación externa', async () => {
    for (const [tipo, valor, valido] of [
        ['dpi', '1234567890101', true], ['dpi', '123456789010', false], ['dpi', '12345678901012', false],
        ['dpi', '123456789010K', false], ['nit', '19', true], ['nit', '1234567-k', true],
        ['nit', '1234567890123', true], ['nit', '123456789012-K', true], ['nit', '1', false],
        ['nit', '0123', false], ['nit', '12345678901234', false], ['nit', '123-A', false], ['nit', '12-34-5', false],
    ]) {
        const e = entorno({ tipo });
        e.escribir(e.entrada, valor, 'blur');
        assert.equal(e.peticiones.length, valido ? 1 : 0, `${tipo}: ${valor}`);
        if (valido) {
            await e.responder(0, { encontrado: false });
            assert.match(e.texto.textContent, /Formato de (DPI|NIT) válido/);
            assert.equal(e.entrada.classList.contains('is-valid'), true);
        } else {
            assert.equal(e.entrada.classList.contains('is-invalid'), true, valor);
        }
    }
});

test('el titular se autocompleta y al cambiar documento se restaura sin perder una corrección manual', async () => {
    const e = entorno({ nombre: 'Nombre inicial' });
    e.escribir(e.entrada, '1234567890101', 'blur');
    const opciones = e.peticiones[0].opciones;
    assert.deepEqual(JSON.parse(opciones.body), { dpi: '1234567890101', tipo_documento: 'dpi' });
    assert.equal(opciones.headers['X-CSRF-TOKEN'], 'token-prueba');
    await e.responder(0, { encontrado: true, nombre: 'Titular registrado' });
    assert.equal(e.titular.value, 'Titular registrado');
    e.escribir(e.entrada, '2234567890101', 'blur');
    assert.equal(e.titular.value, 'Nombre inicial');
    await e.responder(1, { encontrado: false });
    assert.equal(e.titular.value, 'Nombre inicial');

    e.escribir(e.entrada, '3234567890101', 'blur');
    await e.responder(2, { encontrado: true, nombre: 'Otro titular' });
    e.titular.value = 'Corrección manual';
    e.selector.value = 'nit';
    e.selector.emitir('change');
    assert.equal(e.titular.value, 'Corrección manual');
    e.escribir(e.entrada, '1234567-k', 'blur');
    await e.responder(3, { encontrado: true, nombre: 'Empresa registrada' });
    assert.equal(e.titular.value, 'Empresa registrada');
    assert.match(e.texto.textContent, /Titular: Empresa registrada/);
});

test('editar el propio cliente y escribir el nombre durante una consulta conservan el nombre', async () => {
    const propio = entorno({ contexto: 'editar', documento: '1234567890101', nombre: 'Nombre corregido' });
    propio.entrada.emitir('blur');
    await propio.responder(0, { encontrado: true, nombre: 'Nombre en base de datos' });
    assert.equal(propio.titular.value, 'Nombre corregido');
    assert.match(propio.texto.textContent, /cliente que está editando/);

    const e = entorno();
    e.escribir(e.entrada, '1234567890101', 'blur');
    e.titular.value = 'Escrito mientras esperaba';
    await e.responder(0, { encontrado: true, nombre: 'Titular registrado' });
    assert.equal(e.titular.value, 'Escrito mientras esperaba');
});

test('las respuestas tardías y el cambio de tipo no reemplazan el documento actual', async () => {
    const e = entorno();
    e.escribir(e.entrada, '1234567890101', 'blur');
    e.escribir(e.entrada, '2234567890101', 'blur');
    assert.equal(e.peticiones[0].opciones.signal.aborted, true);
    await e.responder(1, { encontrado: true, nombre: 'Titular actual' });
    await e.responder(0, { encontrado: true, nombre: 'Respuesta antigua' });
    assert.equal(e.titular.value, 'Titular actual');
    assert.doesNotMatch(e.texto.textContent, /Respuesta antigua/);

    e.escribir(e.entrada, '3234567890101', 'blur');
    e.selector.value = 'nit';
    e.selector.emitir('change');
    assert.equal(e.peticiones[2].opciones.signal.aborted, true);
    await e.responder(2, { encontrado: true, nombre: 'Respuesta anterior al selector' });
    assert.equal(e.titular.value, '');
    assert.equal(e.entrada.value, '');
    assert.match(e.texto.textContent, /Ingrese el NIT/);
});

test('un fallo de red deja estado neutral y un rechazo del backend muestra su error', async () => {
    const e = entorno();
    e.escribir(e.entrada, '1234567890101', 'blur');
    e.peticiones[0].reject(new Error('Sin conexión'));
    await new Promise(setImmediate);
    assert.equal(e.entrada.classList.contains('is-valid'), false);
    assert.equal(e.entrada.classList.contains('is-invalid'), false);
    assert.match(e.texto.textContent, /No se pudo consultar el titular/);
    assert.equal(e.entrada.atributos.has('aria-busy'), false);

    e.escribir(e.correo, 'persona@', 'blur');
    await e.responder(1, { errors: { email: ['El correo no tiene un formato válido.'] } }, 422);
    assert.equal(e.correo.classList.contains('is-invalid'), true);
    assert.equal(e.texto.textContent, 'El correo no tiene un formato válido.');
    e.escribir(e.correo, 'persona@correo.com', 'blur');
    await e.responder(2, { valido: true });
    assert.equal(e.correo.classList.contains('is-valid'), true);
    assert.equal(e.texto.textContent, 'Correo válido.');
});

test('el teléfono opcional queda neutral y el error inicial del servidor se conserva hasta cambiar el valor', () => {
    const mensaje = 'Este documento ya fue registrado.';
    const e = entorno({ documento: '1234567890101', error: mensaje });
    assert.equal(e.notificaciones[0].mensaje, mensaje);
    e.entrada.emitir('blur');
    assert.equal(e.entrada.classList.contains('is-invalid'), true);
    assert.equal(e.texto.textContent, mensaje);
    assert.equal(e.peticiones.length, 0);
    e.escribir(e.entrada, '2234567890101');
    assert.equal(e.errores.get('documento').textContent, '');
    e.ventana.emitir('pagehide');
    e.reloj.avanzar(350);
    assert.equal(e.peticiones.length, 0, 'Salir de la página cancela las consultas programadas');

    e.escribir(e.telefono, '', 'blur');
    assert.equal(e.telefono.classList.contains('is-valid'), false);
    assert.equal(e.telefono.classList.contains('is-invalid'), false);
    assert.equal(e.mensajes.get('telefono').textContent, '');
    e.escribir(e.telefono, '12345678901234567890123456', 'blur');
    assert.equal(e.telefono.classList.contains('is-invalid'), true);
    assert.match(e.texto.textContent, /25 caracteres/);
    e.escribir(e.telefono, '5555-5555', 'blur');
    assert.equal(e.telefono.classList.contains('is-valid'), true);
    assert.equal(e.texto.textContent, 'Teléfono válido.');
});
