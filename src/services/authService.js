import axios from 'axios'

const apiClient = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Interceptor para inyectar token si existe
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('genesis_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

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
   * Cerrar sesión
   */
  logout() {
    localStorage.removeItem('genesis_token')
    localStorage.removeItem('genesis_usuario')
  },

  /**
   * Obtener datos del usuario autenticado en local
   */
  getUsuarioActual() {
    const raw = localStorage.getItem('genesis_usuario')
    return raw ? JSON.parse(raw) : null
  },

  /**
   * Comprobar si hay sesión activa
   */
  isAuthenticated() {
    return !!localStorage.getItem('genesis_usuario')
  }
}

export default authService
