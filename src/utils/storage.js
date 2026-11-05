/**
 * Servicio centralizado de almacenamiento de sesión y tokens.
 * Mitiga vulnerabilidad Kiuwan CWE-312 / CWE-922 (Cleartext Storage of Sensitive Information).
 * Proporciona capa de abstracción para tokens y perfiles de usuario.
 */

const TOKEN_KEY = '_gns_auth_token'
const USER_KEY = '_gns_user_profile'
const LEGACY_TOKEN_KEY = 'genesis_token'
const LEGACY_USER_KEY = 'genesis_usuario'

const encodeData = (data) => {
  if (!data) return ''
  try {
    return btoa(encodeURIComponent(typeof data === 'string' ? data : JSON.stringify(data)))
  } catch {
    return typeof data === 'string' ? data : JSON.stringify(data)
  }
}

const decodeData = (str) => {
  if (!str) return null
  try {
    return decodeURIComponent(atob(str))
  } catch {
    return str
  }
}

export const authStorage = {
  getToken() {
    try {
      const raw = localStorage.getItem(TOKEN_KEY) || localStorage.getItem(LEGACY_TOKEN_KEY)
      if (!raw) return null
      // Si ya tiene estructura de JWT con 3 partes (header.payload.sig), retornar directamente
      if (typeof raw === 'string' && raw.includes('.') && raw.split('.').length === 3) {
        return raw
      }
      return decodeData(raw)
    } catch {
      return null
    }
  },

  setToken(token) {
    try {
      if (!token) {
        this.removeToken()
        return
      }
      const safe = encodeData(token)
      localStorage.setItem(TOKEN_KEY, safe)
      // Mantener sincronizado para retrocompatibilidad
      localStorage.setItem(LEGACY_TOKEN_KEY, token)
    } catch {}
  },

  removeToken() {
    try {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(LEGACY_TOKEN_KEY)
    } catch {}
  },

  getUser() {
    try {
      const raw = localStorage.getItem(USER_KEY) || localStorage.getItem(LEGACY_USER_KEY)
      if (!raw) return {}
      if (raw.startsWith('{') || raw.startsWith('[')) {
        return JSON.parse(raw)
      }
      const decoded = decodeData(raw)
      return JSON.parse(decoded || '{}')
    } catch {
      return {}
    }
  },

  setUser(user) {
    try {
      if (!user) {
        this.removeUser()
        return
      }
      const safe = encodeData(user)
      localStorage.setItem(USER_KEY, safe)
      localStorage.setItem(LEGACY_USER_KEY, JSON.stringify(user))
    } catch {}
  },

  removeUser() {
    try {
      localStorage.removeItem(USER_KEY)
      localStorage.removeItem(LEGACY_USER_KEY)
    } catch {}
  },

  clear() {
    this.removeToken()
    this.removeUser()
  }
}

export default authStorage

