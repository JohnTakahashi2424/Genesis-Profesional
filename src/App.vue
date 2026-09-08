<script setup>
import LandingPage from './components/LandingPage.vue'

const handleLogin = () => {
  console.log('Navegar a login')
}

const handleRegistro = () => {
  console.log('Navegar a registro')
}
</script>

<template>
  <LandingPage @login="handleLogin" @registro="handleRegistro" />
</template>

