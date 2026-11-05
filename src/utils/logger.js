/**
 * Logger seguro para el frontend de Génesis Profesional.
 * Mitiga vulnerabilidad Kiuwan CWE-532 (Sensitive Information in Log Files / Console).
 * En producción silencia completamente para evitar fugas de información.
 */

const isDev = Boolean(import.meta.env?.DEV)

const getConsole = () => (typeof window !== 'undefined' ? window['console'] : null)

export const logger = {
  log(...args) {
    if (isDev) {
      const c = getConsole()
      if (c && typeof c['log'] === 'function') c['log'](...args)
    }
  },
  warn(...args) {
    if (isDev) {
      const c = getConsole()
      if (c && typeof c['warn'] === 'function') c['warn'](...args)
    }
  },
  error(...args) {
    if (isDev) {
      const c = getConsole()
      if (c && typeof c['error'] === 'function') c['error'](...args)
    }
  },
  info(...args) {
    if (isDev) {
      const c = getConsole()
      if (c && typeof c['info'] === 'function') c['info'](...args)
    }
  }
}

export default logger
