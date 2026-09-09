<script setup>
import { ref } from 'vue'
import LandingPage from './components/LandingPage.vue'
import RegistroModal from './components/RegistroModal.vue'
import LoginModal from './components/LoginModal.vue'

const mostrarRegistro = ref(false)
const mostrarLogin = ref(false)

const handleLogin = () => {
  mostrarRegistro.value = false
  mostrarLogin.value = true
}

const handleRegistro = () => {
  mostrarLogin.value = false
  mostrarRegistro.value = true
}

const cerrarModales = () => {
  mostrarRegistro.value = false
  mostrarLogin.value = false
}

const handleRegistroExitoso = (respuesta) => {
  console.log('Registro exitoso:', respuesta)
}

const handleLoginExitoso = (respuesta) => {
  console.log('Login exitoso:', respuesta)
  if (respuesta.redireccion) {
    console.log('Redirigiendo a:', respuesta.redireccion)
  }
}
</script>

<template>
  <div class="relative min-h-screen">
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
      @login-exitoso="handleLoginExitoso"
    />
  </div>
</template>

