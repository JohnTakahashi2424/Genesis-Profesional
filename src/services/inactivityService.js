import authService from './authService'

const TIEMPO_INACTIVIDAD_MS = 3 * 60 * 1000 // 3 minutos (180,000 ms)

let timerInactividad = null
let callbackExpiracion = null
let escuchando = false
let ultimoReseteo = Date.now()

const EVENTOS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click']

function resetearTimer() {
  const ahora = Date.now()
  // Throttle para evitar reseteos continuos en milisegundos durante mousemove
  if (ahora - ultimoReseteo < 1000) return
  ultimoReseteo = ahora

  if (timerInactividad) {
    clearTimeout(timerInactividad)
  }

  // Si hay sesión activa en local
  if (authService.isAuthenticated()) {
    timerInactividad = setTimeout(async () => {
      console.warn('Cerrando sesión por inactividad de 3 minutos.')
      try {
        await authService.logout()
      } catch (e) {
        // Ignorar
      }
      if (typeof callbackExpiracion === 'function') {
        callbackExpiracion()
      } else {
        alert('Tu sesión ha finalizado por inactividad (3 minutos).')
        window.location.href = '/'
      }
    }, TIEMPO_INACTIVIDAD_MS)
  }
}

export const inactivityService = {
  /**
   * Iniciar el monitoreo de inactividad de pantalla
   * @param {Function} onTimeout Callback opcional al caducar la sesión
   */
  iniciar(onTimeout) {
    if (onTimeout) {
      callbackExpiracion = onTimeout
    }

    if (!escuchando) {
      EVENTOS.forEach(evento => {
        window.addEventListener(evento, resetearTimer, { passive: true })
      })
      escuchando = true
    }

    resetearTimer()
  },

  /**
   * Detener el monitoreo de inactividad
   */
  detener() {
    if (timerInactividad) {
      clearTimeout(timerInactividad)
      timerInactividad = null
    }

    if (escuchando) {
      EVENTOS.forEach(evento => {
        window.removeEventListener(evento, resetearTimer)
      })
      escuchando = false
    }
  },

  /**
   * Reiniciar manualmente el contador de 3 minutos
   */
  reiniciar() {
    resetearTimer()
  }
}

export default inactivityService
