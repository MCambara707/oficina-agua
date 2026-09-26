import { Modal } from 'bootstrap'

const TIEMPO_INACTIVIDAD =  10 * 1000
const SEGUNDOS_CUENTA_REGRESIVA = 30
let inicializado = false

export function inicializarTemporizadorInactividad() {
  if (inicializado || !document.querySelector('[data-authenticated="true"]')) return

  inicializado = true

  document.body.insertAdjacentHTML('beforeend', `
    <div class="modal fade" id="modalInactividad" tabindex="-1"
      aria-labelledby="modalInactividadLabel"
      aria-describedby="descripcionInactividad mensajeInactividad" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title fs-5 d-flex align-items-center gap-2" id="modalInactividadLabel">
              <i class="bi bi-clock-history text-primary" aria-hidden="true"></i>
              Sesión inactiva
            </h2>
          </div>
          <div class="modal-body">
            <p id="descripcionInactividad" class="mb-3">
              Detectamos un periodo de inactividad. Confirma que deseas continuar utilizando AquaTech GT.
            </p>
            <div class="text-center mb-3">
              <span class="d-block small text-body-secondary">Tiempo restante</span>
              <span id="contadorInactividad" class="font-monospace fs-1 fw-semibold text-primary"
                role="timer" aria-live="off">00:30</span>
              <progress id="progresoInactividad" class="w-100" max="30" value="30"
                aria-label="Segundos restantes de la demostración">30 segundos</progress>
            </div>
            <p id="mensajeInactividad" class="small text-body-secondary mb-0" role="status" aria-live="polite">
              Esta es una demostración. La sesión no se cerrará al terminar la cuenta.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary w-100" id="btnSeguirConectado" disabled>
              Seguir conectado
            </button>
          </div>
        </div>
      </div>
    </div>
  `)

  const elementoModal = document.getElementById('modalInactividad')
  const contador = document.getElementById('contadorInactividad')
  const progreso = document.getElementById('progresoInactividad')
  const mensaje = document.getElementById('mensajeInactividad')
  const boton = document.getElementById('btnSeguirConectado')
  const modal = new Modal(elementoModal, { backdrop: 'static', keyboard: false })

  let temporizadorInactividad = null
  let intervaloCuentaRegresiva = null
  let ultimaActividad = Date.now()
  let finCuentaRegresiva = null
  let estado = 'inactivo'
  let suspendido = false
  let focoAnterior = null

  function detenerEspera() {
    clearTimeout(temporizadorInactividad)
    temporizadorInactividad = null
  }

  function detenerCuentaRegresiva() {
    clearInterval(intervaloCuentaRegresiva)
    intervaloCuentaRegresiva = null
  }

  function actualizarCuentaRegresiva() {
    // La fecha límite evita alargar la cuenta cuando el navegador retrasa un tick.
    const segundos = Math.max(0, Math.ceil((finCuentaRegresiva - Date.now()) / 1000))
    contador.textContent = `00:${String(segundos).padStart(2, '0')}`
    progreso.value = segundos
    progreso.textContent = `${segundos} segundos`

    if (segundos === 0) {
      detenerCuentaRegresiva()
      mensaje.textContent = 'Tiempo agotado. La sesión permanece activa para esta demostración.'
    }

    return segundos
  }

  function continuarCuentaRegresiva() {
    detenerCuentaRegresiva()
    if (actualizarCuentaRegresiva() > 0 && !suspendido) {
      intervaloCuentaRegresiva = setInterval(actualizarCuentaRegresiva, 1000)
    }
  }

  function revisarInactividad() {
    temporizadorInactividad = null
    if (suspendido || estado !== 'inactivo') return

    const tiempoRestante = TIEMPO_INACTIVIDAD - (Date.now() - ultimaActividad)
    if (tiempoRestante > 0) {
      temporizadorInactividad = setTimeout(revisarInactividad, tiempoRestante)
      return
    }

    estado = 'abriendo'
    focoAnterior = document.activeElement
    boton.disabled = true
    contador.textContent = '00:30'
    progreso.value = SEGUNDOS_CUENTA_REGRESIVA
    progreso.textContent = '30 segundos'
    mensaje.textContent = 'Esta es una demostración. La sesión no se cerrará al terminar la cuenta.'
    modal.show()
  }

  function iniciarEspera() {
    detenerEspera()
    ultimaActividad = Date.now()
    if (!suspendido) {
      temporizadorInactividad = setTimeout(revisarInactividad, TIEMPO_INACTIVIDAD)
    }
  }

  function registrarActividad() {
    // Los eventos frecuentes solo actualizan la fecha; no recrean temporizadores.
    if (!suspendido && estado === 'inactivo') ultimaActividad = Date.now()
  }

  elementoModal.addEventListener('shown.bs.modal', () => {
    estado = 'visible'
    finCuentaRegresiva = Date.now() + SEGUNDOS_CUENTA_REGRESIVA * 1000
    boton.disabled = false
    boton.focus()
    continuarCuentaRegresiva()
  })

  elementoModal.addEventListener('hide.bs.modal', () => {
    estado = 'cerrando'
    boton.disabled = true
    detenerCuentaRegresiva()
  })

  elementoModal.addEventListener('hidden.bs.modal', () => {
    estado = 'inactivo'
    if (focoAnterior?.isConnected) focoAnterior.focus({ preventScroll: true })
    focoAnterior = null
    iniciarEspera()
  })

  boton.addEventListener('click', () => {
    if (estado === 'visible') modal.hide()
  })

  // capture también detecta scroll en contenedores y acciones que no se propagan.
  for (const evento of ['click', 'pointerdown', 'keydown', 'scroll', 'touchstart']) {
    document.addEventListener(evento, registrarActividad, { passive: true, capture: true })
  }

  window.addEventListener('pagehide', () => {
    suspendido = true
    detenerEspera()
    detenerCuentaRegresiva()
  })

  window.addEventListener('pageshow', (evento) => {
    if (!evento.persisted) return
    suspendido = false
    if (estado === 'inactivo') iniciarEspera()
    if (estado === 'visible') continuarCuentaRegresiva()
  })

  iniciarEspera()
}
