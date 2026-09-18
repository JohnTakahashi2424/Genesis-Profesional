import axios from 'axios'

const apiClient = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Función para inyectar el token Bearer en las solicitudes HTTP
const setBearerToken = config => {
  const token = localStorage.getItem('genesis_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}

// Función para capturar respuestas 401 Unauthorized (Token expirado o destruido)
const handleResponseError = error => {
  if (error.response && error.response.status === 401) {
    const url = error.config?.url || ''
    const esEndpointAuth = url.includes('/auth/login') || url.includes('/auth/verificar-codigo')
    if (!esEndpointAuth) {
      localStorage.removeItem('genesis_token')
      localStorage.removeItem('genesis_usuario')
      if (window.location.pathname !== '/' && window.location.pathname !== '') {
        window.location.href = '/'
      }
    }
  }
  return Promise.reject(error)
}

// Aplicar interceptores a la instancia global de axios y a apiClient
axios.interceptors.request.use(setBearerToken)
axios.interceptors.response.use(response => response, handleResponseError)

apiClient.interceptors.request.use(setBearerToken)
apiClient.interceptors.response.use(response => response, handleResponseError)

export const authService = {
  /**
   * Iniciar sesión
   * @param {Object} credenciales - { correo, contrasena }
   * @returns {Promise<Object>} { mensaje, usuario, redireccion, token? }
   */
  async login(credenciales) {
    const response = await apiClient.post('/auth/login', {
      correo: credenciales.correo,
      contrasena: credenciales.contrasena
    })

    if (response.data.token) {
      localStorage.setItem('genesis_token', response.data.token)
    }
    if (response.data.usuario) {
      localStorage.setItem('genesis_usuario', JSON.stringify(response.data.usuario))
    }

    return response.data
  },

  /**
   * Registrar nuevo usuario (Estudiante / Pasante)
   * @param {Object} datos - { nombres, apellidos, correo, contrasena }
   * @returns {Promise<Object>} { mensaje, usuario }
   */
  async registro(datos) {
    const response = await apiClient.post('/auth/registro', {
      nombres: datos.nombres,
      apellidos: datos.apellidos,
      correo: datos.correo,
      contrasena: datos.contrasena
    })
    return response.data
  },

  /**
   * Comprobar disponibilidad preliminar del correo institucional
   * @param {string} correo
   * @returns {Promise<Object>} { status, disponible, mensaje }
   */
  async verificarCorreo(correo) {
    const response = await apiClient.post('/auth/verificar-correo', {
      correo
    })
    return response.data
  },

  /**
   * Comprobar existencia del correo para recuperación de contraseña
   * @param {string} correo
   */
  async verificarCorreoRecuperacion(correo) {
    const response = await apiClient.post('/auth/verificar-correo-recuperacion', {
      correo
    })
    return response.data
  },

  /**
   * Enviar código de recuperación de contraseña
   * @param {string} correo
   */
  async enviarCodigo(correo) {
    const response = await apiClient.post('/auth/enviar-codigo', { correo })
    return response.data
  },

  /**
   * Verificar código de 6 dígitos
   * @param {string} correo
   * @param {string} codigo
   */
  async verificarCodigo(correo, codigo) {
    const response = await apiClient.post('/auth/verificar-codigo', { correo, codigo })
    return response.data
  },

  /**
   * Restablecer contraseña con código
   * @param {Object} datos - { correo, codigo, contrasena }
   */
  async recuperar(datos) {
    const response = await apiClient.post('/auth/recuperar', datos)
    return response.data
  },

  /**
   * Cerrar sesión e invalidar/destruir el token JWT en servidor
   */
  async logout() {
    try {
      await apiClient.post('/auth/logout')
    } catch (e) {
      // Ignorar errores en caso de que el token ya haya expirado
    } finally {
      localStorage.removeItem('genesis_token')
      localStorage.removeItem('genesis_usuario')
    }
  },

  /**
   * Obtener datos del usuario autenticado en local
   */
  getUsuarioActual() {
    const raw = localStorage.getItem('genesis_usuario')
    return raw ? JSON.parse(raw) : null
  },

  /**
   * Comprobar si hay sesión activa (token y datos de usuario presentes)
   */
  isAuthenticated() {
    return !!localStorage.getItem('genesis_usuario') && !!localStorage.getItem('genesis_token')
  }
}

export default authService
