<script setup>
import { ref } from 'vue'
import authService from '../services/authService'
import { BaseInput, BaseButton } from './common'

const emit = defineEmits(['close', 'abrir-registro', 'login-exitoso'])

const form = ref({
  correo: '',
  contrasena: ''
})

const loading = ref(false)
const errorMensaje = ref('')
const exitoMensaje = ref('')

const handleLogin = async () => {
  errorMensaje.value = ''
  exitoMensaje.value = ''

  if (!form.value.correo.trim() || !form.value.contrasena) {
    errorMensaje.value = 'Por favor complete todos los campos.'
    return
  }

  loading.value = true
  try {
    const res = await authService.login({
      correo: form.value.correo.trim().toLowerCase(),
      contrasena: form.value.contrasena
    })
    exitoMensaje.value = res.mensaje || '¡Sesión iniciada exitosamente!'
    emit('login-exitoso', res)
  } catch (err) {
    if (err.response && err.response.data) {
      errorMensaje.value = err.response.data.mensaje || 'Credenciales de acceso incorrectas o cuenta no autorizada.'
    } else {
      errorMensaje.value = 'Error al conectar con el servidor. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div 
    class="login-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-cover bg-center overflow-y-auto"
    style="background-image: url('/images/Fondo_login.png');"
  >
    <!-- Capa de oscurecimiento suave -->
    <div class="fixed inset-0 bg-[#071329]/40 backdrop-blur-[1px] pointer-events-none"></div>

    <!-- TARJETA PRINCIPAL -->
    <div class="relative w-full max-w-[440px] bg-white rounded-[26px] shadow-2xl p-7 sm:p-9 my-auto z-10 text-center">
      
      <!-- Botón de regreso / cerrar -->
      <button 
        type="button" 
        @click="emit('close')"
        class="absolute top-6 left-6 text-gray-800 hover:text-black p-1.5 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
        title="Cerrar"
        id="btn-close-login"
      >
        <i class="bi bi-arrow-left text-xl font-bold"></i>
      </button>

      <!-- Logo UGB Centrado -->
      <div class="flex justify-center mb-3">
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
        <p class="text-sm text-gray-600 mt-1 font-sans">
          Accede a tu cuenta de Génesis Profesional
        </p>
      </div>

      <!-- Alertas de Error y Éxito -->
      <div 
        v-if="errorMensaje" 
        class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2 text-left"
      >
        <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5 text-sm shrink-0"></i>
        <span>{{ errorMensaje }}</span>
      </div>

      <div 
        v-if="exitoMensaje" 
        class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs flex items-start gap-2 text-left"
      >
        <i class="bi bi-check-circle-fill text-emerald-500 mt-0.5 text-sm shrink-0"></i>
        <span>{{ exitoMensaje }}</span>
      </div>

      <!-- Formulario utilizando componentes reutilizables -->
      <form @submit.prevent="handleLogin" class="text-left" novalidate>
        <!-- Correo Institucional -->
        <BaseInput 
          id="input-login-correo"
          v-model="form.correo"
          type="email"
          label="Correo institucional"
          required
          placeholder="usss@000ugb.edu.sv"
        />

        <!-- Contraseña -->
        <div class="mb-3">
          <BaseInput 
            id="input-login-contrasena"
            v-model="form.contrasena"
            type="password"
            label="Contraseña"
            required
            placeholder="••••••"
          />
        </div>

        <!-- Botón Iniciar Sesión -->
        <div class="text-center pt-1">
          <BaseButton 
            type="submit"
            id="btn-submit-login"
            :loading="loading"
            loading-text="Ingresando..."
          >
            Iniciar sesión
          </BaseButton>
        </div>

        <!-- Enlace a Registro -->
        <div class="text-center mt-6">
          <p class="text-xs text-gray-800">
            ¿No tienes una cuenta? 
            <button 
              type="button" 
              @click="emit('abrir-registro')" 
              class="text-blue-600 underline font-medium hover:text-blue-800 cursor-pointer ml-1"
            >
              Regístrate
            </button>
          </p>
        </div>
      </form>

    </div>
  </div>
</template>
