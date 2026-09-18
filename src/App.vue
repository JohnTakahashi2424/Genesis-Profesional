<script setup>
import { ref, onMounted } from 'vue'
import LandingPage from './components/LandingPage.vue'
import RegistroModal from './components/RegistroModal.vue'
import LoginModal from './components/LoginModal.vue'
import RecuperarPasswordModal from './components/RecuperarPasswordModal.vue'
import PasantePanel from './components/PasantePanel.vue'
import authService from './services/authService'
import inactivityService from './services/inactivityService'

const vistaActual = ref('landing')
const tabLandingActual = ref('inicio')
const mostrarRegistro = ref(false)
const mostrarLogin = ref(false)
const mostrarRecuperar = ref(false)

const sincronizarRuta = () => {
  const path = window.location.pathname.toLowerCase()

  // Si existe token y sesión activa, redirigir directamente al panel si se ingresa a /login o a la Landing
  if (authService.isAuthenticated() && (path === '/login' || path === '/registro' || path === '/' || path === '')) {
    vistaActual.value = 'panel'
    mostrarLogin.value = false
    mostrarRegistro.value = false
    mostrarRecuperar.value = false
    if (window.location.pathname !== '/pasante') {
      window.history.replaceState({ vista: 'panel' }, '', '/pasante')
    }
    return
  }

  // Rutas del panel de usuario (pasante / estudiante / supervisor / vicedecano)
  if (
    path.includes('/pasante') || 
    path.includes('/estudiante') || 
    path.includes('/supervisor') || 
    path.includes('/vicedecano') || 
    path.includes('/panel-pasante') ||
    path.includes('/dashboard')
  ) {
    vistaActual.value = 'panel'
    mostrarLogin.value = false
    mostrarRegistro.value = false
    mostrarRecuperar.value = false
    return
  }

  // Rutas de la Landing Page y Modales
  vistaActual.value = 'landing'

  if (path === '/login') {
    mostrarLogin.value = true
    mostrarRegistro.value = false
    mostrarRecuperar.value = false
  } else if (path === '/registro') {
    mostrarRegistro.value = true
    mostrarLogin.value = false
    mostrarRecuperar.value = false
  } else if (path === '/recuperar' || path === '/recuperar-password' || path === '/olvide-contrasena') {
    mostrarRecuperar.value = true
    mostrarLogin.value = false
    mostrarRegistro.value = false
  } else {
    mostrarLogin.value = false
    mostrarRegistro.value = false
    mostrarRecuperar.value = false

    if (path.includes('/empresas')) tabLandingActual.value = 'empresas'
    else if (path.includes('/areas')) tabLandingActual.value = 'areas'
    else if (path.includes('/requisitos')) tabLandingActual.value = 'requisitos'
    else if (path.includes('/alcance')) tabLandingActual.value = 'alcance'
    else tabLandingActual.value = 'inicio'
  }
}

onMounted(() => {
  sincronizarRuta()
  window.addEventListener('popstate', sincronizarRuta)
  if (authService.isAuthenticated()) {
    inactivityService.iniciar(() => handleLogout())
  }
})

const handleLogin = () => {
  if (authService.isAuthenticated()) {
    vistaActual.value = 'panel'
    cerrarModales()
    if (window.location.pathname !== '/pasante') {
      window.history.pushState({ vista: 'panel' }, '', '/pasante')
    }
    return
  }
  mostrarRegistro.value = false
  mostrarRecuperar.value = false
  mostrarLogin.value = true
  if (window.location.pathname !== '/login') {
    window.history.pushState({ modal: 'login' }, '', '/login')
  }
}

const handleRegistro = () => {
  mostrarLogin.value = false
  mostrarRecuperar.value = false
  mostrarRegistro.value = true
  if (window.location.pathname !== '/registro') {
    window.history.pushState({ modal: 'registro' }, '', '/registro')
  }
}

const handleRecuperar = () => {
  mostrarLogin.value = false
  mostrarRegistro.value = false
  mostrarRecuperar.value = true
  if (window.location.pathname !== '/recuperar') {
    window.history.pushState({ modal: 'recuperar' }, '', '/recuperar')
  }
}

const cerrarModales = () => {
  mostrarRegistro.value = false
  mostrarLogin.value = false
  mostrarRecuperar.value = false
  const rutasModal = ['/login', '/registro', '/recuperar', '/recuperar-password', '/olvide-contrasena']
  if (rutasModal.includes(window.location.pathname)) {
    const baseTarget = tabLandingActual.value === 'inicio' ? '/' : `/${tabLandingActual.value}`
    window.history.pushState({}, '', baseTarget)
  }
}

const handleCambioTabLanding = (tab) => {
  tabLandingActual.value = tab
  const targetPath = tab === 'inicio' ? '/' : `/${tab}`
  if (window.location.pathname !== targetPath && !mostrarLogin.value && !mostrarRegistro.value && !mostrarRecuperar.value) {
    window.history.pushState({ tab }, '', targetPath)
  }
}

const handleRegistroExitoso = (respuesta) => {
  console.log('Registro exitoso:', respuesta)
}

const handleLoginExitoso = (respuesta) => {
  console.log('Login exitoso:', respuesta)
  cerrarModales()
  vistaActual.value = 'panel'
  let rutaDestino = '/pasante'
  if (respuesta?.redireccion && typeof respuesta.redireccion === 'string') {
    rutaDestino = respuesta.redireccion.replace('/dashboard', '')
    if (!rutaDestino.startsWith('/')) rutaDestino = '/' + rutaDestino
    if (rutaDestino === '') rutaDestino = '/pasante'
  }
  if (window.location.pathname !== rutaDestino) {
    window.history.pushState({ vista: 'panel' }, '', rutaDestino)
  }
  inactivityService.iniciar(() => handleLogout())
}

const handleLogout = async () => {
  inactivityService.detener()
  await authService.logout()
  vistaActual.value = 'landing'
  tabLandingActual.value = 'inicio'
  cerrarModales()
  if (window.location.pathname !== '/') {
    window.history.pushState({}, '', '/')
  }
}
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Vista Panel Pasante -->
    <PasantePanel 
      v-if="vistaActual === 'panel'" 
      @logout="handleLogout" 
    />

    <!-- Vista Landing Page -->
    <template v-else>
      <LandingPage 
        :ocultar-navbar="mostrarLogin || mostrarRegistro || mostrarRecuperar"
        :tab-actual="tabLandingActual"
        @login="handleLogin" 
        @registro="handleRegistro"
        @cambiar-tab="handleCambioTabLanding" 
      />

      <!-- Modal de Registro -->
      <RegistroModal 
        v-if="mostrarRegistro"
        @close="cerrarModales"
        @abrir-login="handleLogin"
        @registro-exitoso="handleRegistroExitoso"
      />

      <!-- Modal de Login -->
      <LoginModal
        v-if="mostrarLogin"
        @close="cerrarModales"
        @abrir-registro="handleRegistro"
        @olvide-contrasena="handleRecuperar"
        @login-exitoso="handleLoginExitoso"
      />

      <!-- Modal de Recuperación de Contraseña -->
      <RecuperarPasswordModal
        v-if="mostrarRecuperar"
        @close="cerrarModales"
        @volver-login="handleLogin"
      />
    </template>
  </div>
</template>

