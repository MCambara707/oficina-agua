import { Toast } from 'bootstrap';

const PAUSA_CONSULTA = 350;
let notificacion = null;

function avisos() {
    if (notificacion) return notificacion;
    document.body.insertAdjacentHTML('beforeend', `
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="avisoValidacion" class="toast notificacion-validacion border-2"
                 role="status" aria-live="polite" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto" data-titulo-aviso></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar aviso"></button>
                </div>
                <div class="toast-body text-break" data-mensaje-aviso></div>
            </div>
        </div>
    `);
    const elemento = document.getElementById('avisoValidacion');
    const titulo = elemento.querySelector('[data-titulo-aviso]');
    const texto = elemento.querySelector('[data-mensaje-aviso]');
    const toast = new Toast(elemento, { delay: 5000, animation: false });
    let propietario = null;
    notificacion = {
        mostrar(input, estado, mensaje) {
            if (!mensaje) return;
            const repetido = propietario === input && texto.textContent === mensaje;
            propietario = input;
            titulo.textContent = input
                ? ({ dpi: 'DPI', nit: 'NIT', telefono: 'Teléfono', email: 'Correo' }[input.name] || 'Validación')
                : 'Revisa los campos';
            texto.textContent = mensaje;
            elemento.classList.toggle('border-success', estado === 'valido');
            elemento.classList.toggle('border-danger', estado === 'invalido');
            elemento.classList.toggle('border-secondary', estado === 'neutro');
            if (!repetido || !elemento.classList.contains('show')) toast.show();
        },
        ocultar(input) {
            if (!input || propietario === input) toast.hide();
        },
    };
    window.addEventListener('pagehide', () => toast.hide());
    return notificacion;
}

function mostrarEstado(input, feedback, estado, mensaje = '') {
    input.classList.toggle('is-valid', estado === 'valido');
    input.classList.toggle('is-invalid', estado === 'invalido');
    input.setAttribute('aria-invalid', String(estado === 'invalido'));
    feedback.textContent = mensaje;
    input.title = mensaje;
}

function tipoDocumento(input) {
    return input.form.querySelector('[data-tipo-documento]')?.value || 'dpi';
}

function normalizarDocumento(valor, tipo) {
    return tipo === 'nit' ? valor.toUpperCase().replace(/-([0-9K])$/, '$1') : valor;
}

function errorLocal(input, valor) {
    const tipo = input.dataset.validacion;
    const etiqueta = tipo === 'documento' ? tipoDocumento(input).toUpperCase() : 'correo electrónico';
    if (!valor && input.required) return `El ${etiqueta} es obligatorio.`;
    if (tipo === 'documento') {
        if (tipoDocumento(input) === 'dpi' && !/^[0-9]{13}$/.test(valor)) {
            return 'El DPI debe contener exactamente 13 dígitos, sin letras ni guiones.';
        }
        if (tipoDocumento(input) === 'nit' && !/^[1-9][0-9]{0,11}-?[0-9K]$/i.test(valor)) {
            return 'El NIT debe tener de 2 a 13 caracteres, iniciar del 1 al 9 y terminar en un número o K. El guion antes del último carácter es opcional.';
        }
    }
    const maximo = Number(input.dataset.validacionMax);
    if (maximo && Array.from(valor).length > maximo) {
        return `${tipo === 'telefono' ? 'El teléfono' : 'El correo'} no puede superar ${maximo} caracteres.`;
    }
    return null;
}

function prepararCampo(input, notificador) {
    const form = input.form;
    const tipo = input.dataset.validacion;
    const feedback = form.querySelector(`[data-feedback-campo="${tipo}"]`);
    const backend = form.querySelector(`[data-error-campo="${tipo}"]`);
    const nombre = tipo === 'documento' ? form.querySelector('[name="nombre"]') : null;
    const selector = tipo === 'documento' ? form.querySelector('[data-tipo-documento]') : null;
    const valorBackend = input.value.trim();
    const tipoBackend = selector?.value;
    let conservarErrorBackend = Boolean(backend?.textContent.trim());
    let espera = null;
    let solicitud = null;
    let version = 0;
    let ultimaClave = null;
    let nombreCompletado = null;
    let nombreAnterior = '';
    let claveCompletada = null;

    const claveDocumento = () => `${tipoDocumento(input)}:${normalizarDocumento(input.value.trim(), tipoDocumento(input))}`;
    const cancelarConsulta = () => {
        clearTimeout(espera);
        espera = null;
        solicitud?.abort();
        solicitud = null;
        version += 1;
        input.removeAttribute('aria-busy');
    };
    const retirarNombre = () => {
        // Conserva cualquier corrección escrita por el usuario después del autocompletado.
        if (nombre && nombreCompletado !== null && nombre.value === nombreCompletado) {
            nombre.value = nombreAnterior;
        }
        nombreCompletado = null;
        claveCompletada = null;
    };
    const estado = (valor, mensaje, notificar = true) => {
        mostrarEstado(input, feedback, valor, mensaje);
        if (notificar) notificador.mostrar(input, valor, mensaje);
    };
    const consultar = async (valor, revision) => {
        const controlador = new AbortController();
        solicitud = controlador;
        input.setAttribute('aria-busy', 'true');
        const documento = tipoDocumento(input);
        const nombreAlConsultar = nombre?.value;
        const url = tipo === 'documento' ? form.dataset.consultaDocumentoUrl : form.dataset.validacionCorreoUrl;
        const cuerpo = { [input.name]: valor };
        if (tipo === 'documento') cuerpo.tipo_documento = documento;
        try {
            const respuesta = await fetch(url, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controlador.signal,
                headers: {
                    Accept: 'application/json', 'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify(cuerpo),
            });
            const datos = await respuesta.json();
            if (revision !== version || input.disabled || input.value.trim() !== valor) return;
            if (respuesta.status === 422) {
                const mensaje = datos.errors?.[input.name]?.[0] || datos.errors?.tipo_documento?.[0];
                if (mensaje) { estado('invalido', mensaje); return; }
            }
            if (!respuesta.ok || respuesta.redirected) throw new Error('Consulta no disponible');
            if (tipo === 'email' && datos.valido === true) {
                estado('valido', 'Correo válido.');
                return;
            }
            if (tipo !== 'documento' || typeof datos.encontrado !== 'boolean'
                || (datos.encontrado && typeof datos.nombre !== 'string')) {
                throw new Error('Respuesta no válida');
            }
            const etiqueta = documento.toUpperCase();
            if (!datos.encontrado) {
                retirarNombre();
                estado('valido', `Formato de ${etiqueta} válido. No hay un titular registrado en AquaTech.`);
                return;
            }
            const propio = form.dataset.contextoCliente === 'editar'
                && documento === input.dataset.tipoOriginal
                && normalizarDocumento(valor, documento) === input.dataset.documentoOriginal;
            const clave = claveDocumento();
            if (nombre && !nombre.disabled && !propio && claveCompletada !== clave && nombre.value === nombreAlConsultar) {
                nombreAnterior = nombre.value;
                nombre.value = datos.nombre;
                nombreCompletado = datos.nombre;
                claveCompletada = clave;
            }
            const ayuda = propio ? 'Corresponde al cliente que está editando.'
                : form.dataset.contextoCliente === 'alta' ? 'Utilice la opción Cliente existente.'
                    : 'Este documento ya está registrado; no puede duplicarse.';
            estado('valido', `Formato de ${etiqueta} válido. Titular: ${datos.nombre}. ${ayuda}`);
        } catch (error) {
            if (error.name === 'AbortError' || revision !== version || input.disabled || input.value.trim() !== valor) return;
            if (tipo === 'documento') {
                estado('neutro', 'No se pudo consultar el titular. Intente nuevamente; se verificará al guardar.');
            } else {
                estado('neutro', 'No se pudo comprobar el correo. Se validará al guardar.');
            }
        } finally {
            if (revision === version) { solicitud = null; input.removeAttribute('aria-busy'); }
        }
    };
    const validar = (inmediato = false) => {
        cancelarConsulta();
        notificador.ocultar(input);
        const valor = input.value.trim();
        if (tipo === 'documento') {
            const clave = claveDocumento();
            if (ultimaClave !== null && ultimaClave !== clave) retirarNombre();
            ultimaClave = clave;
        }
        if (input.disabled) {
            estado('neutro', '', false);
            return;
        }
        let mensaje = null;
        if (conservarErrorBackend && valor === valorBackend && selector?.value === tipoBackend) {
            mensaje = backend.textContent.trim();
        } else {
            conservarErrorBackend = false;
            backend.textContent = '';
            if (!valor && !input.required) { estado('neutro', '', false); return; }
            mensaje = errorLocal(input, valor);
        }
        if (mensaje || tipo === 'telefono') {
            const resultado = mensaje ? 'invalido' : 'valido';
            mensaje ||= 'Teléfono válido.';
            estado(resultado, mensaje, inmediato);
            if (!inmediato) espera = setTimeout(() => {
                espera = null;
                notificador.mostrar(input, resultado, mensaje);
            }, PAUSA_CONSULTA);
            return;
        }
        estado('neutro', tipo === 'documento' ? 'Consultando documento…' : 'Comprobando correo…', false);
        const revision = version;
        if (inmediato) consultar(valor, revision);
        else espera = setTimeout(() => { espera = null; consultar(valor, revision); }, PAUSA_CONSULTA);
    };
    input.addEventListener('input', () => validar());
    input.addEventListener('blur', () => validar(true));
    if (selector) selector.addEventListener('change', () => {
        cancelarConsulta();
        retirarNombre();
        conservarErrorBackend = false;
        backend.textContent = '';
        input.value = '';
        input.name = selector.value;
        input.maxLength = selector.value === 'dpi' ? 13 : 14;
        input.inputMode = selector.value === 'dpi' ? 'numeric' : 'text';
        input.placeholder = selector.value === 'dpi' ? '13 dígitos' : 'NIT, con o sin guion';
        ultimaClave = claveDocumento();
        estado('neutro', selector.value === 'dpi'
            ? 'Ingrese los 13 dígitos del DPI.'
            : 'Ingrese el NIT: de 2 a 13 caracteres, con guion opcional antes del último.');
        input.focus();
    });
    return { validar, cancelarConsulta };
}

export function inicializarValidacionCampos() {
    document.querySelectorAll('form[data-validacion-campos]').forEach((form) => {
        if (form.dataset.validacionInicializada) return;
        form.dataset.validacionInicializada = 'true';
        const notificador = avisos();
        const campos = new Map();
        form.querySelectorAll('[data-validacion]').forEach((input) => {
            campos.set(input, prepararCampo(input, notificador));
        });
        const errores = Array.from(form.querySelectorAll('[data-error-campo]'))
            .map((error) => error.textContent.trim()).filter(Boolean);
        if (errores.length) notificador.mostrar(null, 'invalido', errores.join(' '));
        const observador = new MutationObserver((cambios) => {
            cambios.forEach(({ target }) => { if (target.disabled) campos.get(target)?.validar(); });
        });
        observador.observe(form, { subtree: true, attributes: true, attributeFilter: ['disabled'] });
        window.addEventListener('pagehide', () => campos.forEach(({ cancelarConsulta }) => cancelarConsulta()));
    });
}
