<script setup>
import { ref, onMounted } from 'vue'
import LandingPage from './components/LandingPage.vue'
import RegistroModal from './components/RegistroModal.vue'
import LoginModal from './components/LoginModal.vue'
import RecuperarPasswordModal from './components/RecuperarPasswordModal.vue'
import PasantePanel from './components/PasantePanel.vue'

const vistaActual = ref('landing')
const mostrarRegistro = ref(false)
const mostrarLogin = ref(false)
const mostrarRecuperar = ref(false)

onMounted(() => {
  const path = window.location.pathname
  const usuario = localStorage.getItem('genesis_usuario')
  if (path.includes('/dashboard') || path.includes('/panel') || path.includes('/panel-pasante') || usuario) {
    vistaActual.value = 'dashboard'
  }
})

const handleLogin = () => {
  mostrarRegistro.value = false
  mostrarRecuperar.value = false
  mostrarLogin.value = true
}

const handleRegistro = () => {
  mostrarLogin.value = false
  mostrarRecuperar.value = false
  mostrarRegistro.value = true
}

const handleRecuperar = () => {
  mostrarLogin.value = false
  mostrarRegistro.value = false
  mostrarRecuperar.value = true
}

const cerrarModales = () => {
  mostrarRegistro.value = false
  mostrarLogin.value = false
  mostrarRecuperar.value = false
}

const handleRegistroExitoso = (respuesta) => {
  console.log('Registro exitoso:', respuesta)
}

const handleLoginExitoso = (respuesta) => {
  console.log('Login exitoso:', respuesta)
  cerrarModales()
  vistaActual.value = 'dashboard'
}

const handleLogout = () => {
  vistaActual.value = 'landing'
}
</script>

<template>
  <div class="relative min-h-screen">
    <!-- Vista Panel Pasante -->
    <PasantePanel 
      v-if="vistaActual === 'dashboard'" 
      @logout="handleLogout" 
    />

    <!-- Vista Landing Page -->
    <template v-else>
      <LandingPage @login="handleLogin" @registro="handleRegistro" />

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

