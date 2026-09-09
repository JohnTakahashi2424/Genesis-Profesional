<script setup>
import { ref, computed } from 'vue'
import authService from '../services/authService'

const emit = defineEmits(['close', 'abrir-registro', 'login-exitoso', 'olvide-contrasena'])

// Datos del formulario
const form = ref({
  correo: '',
  contrasena: ''
})

// Validación reactiva de campos completos (activa el botón a azul marino #010c67 según diseño)
const formCompleto = computed(() => {
  return form.value.correo.trim().length > 0 && form.value.contrasena.length > 0
})

// Estados de interfaz
const mostrarPassword = ref(false)
const loading = ref(false)
const errorCorreo = ref('')
const errorContrasena = ref('')
const errorGeneral = ref('')

// Manejo del envío del formulario y consumo de la API
const handleLogin = async () => {
  // Limpiar errores previos
  errorCorreo.value = ''
  errorContrasena.value = ''
  errorGeneral.value = ''

  const correoLimpio = form.value.correo.trim().toLowerCase()
  const pass = form.value.contrasena

  // 1. Validación de campos obligatorios (según Imagen 1)
  if (!correoLimpio || !pass) {
    errorCorreo.value = 'Debe completar todos los campos para continuar'
    return
  }

  // 2. Validación sintáctica de correo electrónico
  const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!regexEmail.test(correoLimpio)) {
    errorCorreo.value = 'El formato del correo institucional es inválido.'
    return
  }

  loading.value = true

  try {
    // Consumo de la API POST /api/auth/login
    const res = await authService.login({
      correo: correoLimpio,
      contrasena: pass
    })

    emit('login-exitoso', res)

    // Redirección directa al panel institucional (ej. panel del pasante) o cerrar modal
    if (res.redireccion && window.location.pathname !== res.redireccion) {
      window.location.href = res.redireccion
    } else {
      emit('close')
    }

  } catch (err) {
    if (err.response && err.response.data) {
      const status = err.response.status
      const data = err.response.data

      if (status === 401) {
        // Credenciales incorrectas o usuario no encontrado (según Imagen 2)
        errorContrasena.value = data.mensaje || 'Correo o contraseña incorrectos. Intentalo de nuevo.'
      } else if (status === 422) {
        // Fallo de validación en backend (según Imagen 1)
        errorCorreo.value = data.mensaje || 'Debe completar todos los campos para continuar'
      } else if (status === 403) {
        // Cuenta inactiva
        errorGeneral.value = data.mensaje || 'La cuenta no se encuentra activa en el sistema.'
      } else {
        errorGeneral.value = data.mensaje || 'Error al procesar la solicitud. Intente de nuevo.'
      }
    } else {
      errorGeneral.value = 'Error al conectar con el servidor. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}

// Handler para olvido de contraseña
const handleOlvideContrasena = () => {
  emit('olvide-contrasena', form.value.correo)
}
</script>

<template>
  <div 
    class="login-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-cover bg-center overflow-y-auto"
    style="background-image: url('/images/Fondo_login.png');"
  >
    <!-- Capa de oscurecimiento suave -->
    <div class="fixed inset-0 bg-[#071329]/45 backdrop-blur-[2px] pointer-events-none"></div>

    <!-- TARJETA PRINCIPAL -->
    <div class="relative w-full max-w-[400px] sm:max-w-[420px] bg-white rounded-[26px] shadow-2xl px-7 py-8 sm:px-9 sm:py-9 my-auto z-10 text-center">
      
      <!-- Botón de regreso / cerrar (Flecha izquierda arriba) -->
      <button 
        type="button" 
        @click="emit('close')"
        class="absolute top-6 left-6 text-gray-800 hover:text-black p-1 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
        title="Regresar"
        id="btn-close-login"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="18" viewBox="0 0 25 18" fill="none">
          <path d="M1 9H24M7.57143 1L1 9L7.57143 17" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <!-- Logo UGB Centrado -->
      <div class="flex justify-center mb-2">
        <img 
          src="/images/logo_ugb.png" 
          alt="Universidad Gerardo Barrios" 
          class="h-16 w-auto object-contain"
        />
      </div>

      <!-- Título y Subtítulo -->
      <div class="text-center mb-5">
        <h2 class="text-[26px] font-bold text-gray-950 font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
          Iniciar sesión
        </h2>
        <p class="text-[13px] font-medium text-gray-800 mt-1 font-sans">
          ¡Bienvenido!
        </p>
        <p class="text-[12px] text-gray-600 font-sans">
          Ingresa tu correo y contraseña para iniciar
        </p>
      </div>

      <!-- Alerta general para errores del servidor o cuenta inactiva -->
      <div 
        v-if="errorGeneral" 
        class="mb-4 p-2.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2 text-left"
      >
        <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5 text-xs shrink-0"></i>
        <span>{{ errorGeneral }}</span>
      </div>

      <!-- Formulario de inicio de sesión -->
      <form @submit.prevent="handleLogin" class="text-left" novalidate>
        
        <!-- Campo: Correo* -->
        <div class="mb-4">
          <label for="input-login-correo" class="block text-sm font-semibold text-gray-900 mb-1">
            Correo<span class="text-red-500">*</span>
          </label>
          <input 
            id="input-login-correo"
            v-model="form.correo"
            type="email"
            placeholder="usss@000ugb.edu.sv"
            maxlength="100"
            :disabled="loading"
            @input="errorCorreo = ''; errorGeneral = ''"
            class="w-full px-3.5 py-2.5 rounded-[12px] bg-[#ebebeb] border text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none transition-colors disabled:opacity-60"
            :class="errorCorreo ? 'border-red-500' : 'border-gray-500'"
          />
          <!-- Mensaje de error (Imagen 1) -->
          <p v-if="errorCorreo" class="flex items-center gap-1 text-red-600 text-[11px] mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
            <span>{{ errorCorreo }}</span>
          </p>
        </div>

        <!-- Campo: Contraseña* -->
        <div class="mb-2">
          <label for="input-login-contrasena" class="block text-sm font-semibold text-gray-900 mb-1">
            Contraseña<span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input 
              id="input-login-contrasena"
              v-model="form.contrasena"
              :type="mostrarPassword ? 'text' : 'password'"
              placeholder="•••••••••"
              maxlength="100"
              :disabled="loading"
              @input="errorContrasena = ''; errorGeneral = ''"
              class="w-full px-3.5 py-2.5 pr-11 rounded-[12px] bg-[#ebebeb] border text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none transition-colors disabled:opacity-60"
              :class="errorContrasena ? 'border-red-500' : 'border-gray-500'"
            />
            <!-- Toggle de visibilidad de contraseña -->
            <button 
              type="button" 
              @click="mostrarPassword = !mostrarPassword"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
              tabindex="-1"
              title="Mostrar / ocultar contraseña"
            >
              <i :class="mostrarPassword ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
            </button>
          </div>
          <!-- Mensaje de error (Imagen 2) -->
          <p v-if="errorContrasena" class="flex items-center gap-1 text-red-600 text-[11px] mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
            <span>{{ errorContrasena }}</span>
          </p>
        </div>

        <!-- Enlace: ¿Olvidaste tu contraseña? Centrado -->
        <div class="text-center my-3">
          <button 
            type="button" 
            @click="handleOlvideContrasena" 
            class="text-[#2563eb] text-xs underline font-medium hover:text-[#1d4ed8] cursor-pointer"
          >
            ¿Olvidaste tu contraseña?
          </button>
        </div>

        <!-- Botón Iniciar Sesión Centrado con color azul marino #010c67 según diseño -->
        <div class="text-center mt-3 mb-5">
          <button 
            type="submit"
            id="btn-submit-login"
            :disabled="loading"
            class="w-[171px] h-[51px] rounded-[18px] border border-white text-white text-sm font-medium transition-all flex items-center justify-center gap-[10px] px-[10px] mx-auto cursor-pointer select-none disabled:cursor-not-allowed"
            :class="formCompleto 
              ? 'bg-[#010c67] hover:bg-[#01094f] active:scale-[0.98] shadow-md' 
              : 'bg-[#888eb8] shadow-sm'"
            style="font-family: 'Lora', Georgia, serif;"
          >
            <!-- Spinner al cargar -->
            <span 
              v-if="loading" 
              class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0"
            ></span>
            <span>{{ loading ? 'Iniciando...' : 'Iniciar sesión' }}</span>
          </button>
        </div>

        <!-- Footer: ¿No tienes cuenta? Crear una cuenta -->
        <div class="text-center mt-2">
          <p class="text-xs text-gray-900">
            ¿No tienes cuenta? 
            <button 
              type="button" 
              @click="emit('abrir-registro')" 
              class="text-[#2563eb] underline font-medium hover:text-[#1d4ed8] cursor-pointer ml-1"
            >
              Crear una cuenta
            </button>
          </p>
        </div>

      </form>

    </div>
  </div>
</template>
