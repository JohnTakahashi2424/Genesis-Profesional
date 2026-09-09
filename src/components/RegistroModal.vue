<script setup>
import { ref, computed, watch } from 'vue'
import authService from '../services/authService'

const emit = defineEmits(['close', 'abrir-login', 'registro-exitoso'])

// Estado del flujo
// 1 = Datos iniciales (Nombres, Apellidos, Correo)
// 2 = Contraseña y confirmación
// 'validando' = Modal de carga "Datos enviados correctamente" (Screenshot 3)
// 'exitoso' = Modal de éxito "Creación de cuenta exitosa" (Screenshot 4)
const estadoFlujo = ref(1) // 1, 2, 'validando', 'exitoso'
const loading = ref(false)
const errorGeneral = ref('')
const mostrarModalTerminos = ref(false)

// Campos del formulario
const form = ref({
  nombres: '',
  apellidos: '',
  correo: '',
  contrasena: '',
  confirmarContrasena: '',
  aceptaTerminos: false
})

// Control de visibilidad de contraseñas
const mostrarContrasena = ref(false)
const mostrarConfirmContrasena = ref(false)

// Errores de validación por campo
const errores = ref({
  nombres: '',
  apellidos: '',
  correo: '',
  contrasena: '',
  confirmarContrasena: ''
})

// Campos tocados
const tocados = ref({
  nombres: false,
  apellidos: false,
  correo: false,
  contrasena: false,
  confirmarContrasena: false
})

// --- CRITERIOS DE FORTALEZA DE CONTRASEÑA ---
const tieneMinimo8 = computed(() => form.value.contrasena.length >= 8)
const tieneMayuscula = computed(() => /[A-Z]/.test(form.value.contrasena))
const tieneMinuscula = computed(() => /[a-z]/.test(form.value.contrasena))
const tieneNumeroOSimbolo = computed(() => /[0-9@$!%*?&._#\-]/.test(form.value.contrasena))

// Puntaje de fortaleza (0 a 4)
const puntajeFortaleza = computed(() => {
  if (!form.value.contrasena) return 0
  let score = 0
  if (tieneMinimo8.value) score++
  if (tieneMayuscula.value) score++
  if (tieneMinuscula.value) score++
  if (tieneNumeroOSimbolo.value) score++
  return score
})

// Estado visual de las 3 barras según las capturas:
// 1. Vacío: 3 barras grises, texto "Fortaleza de la contraseña"
// 2. Débil: 1 barra roja, texto "Débil" (#ef4444)
// 3. Media: 2 barras naranjas, texto "Media" (#f97316)
// 4. Segura: 3 barras azul marino (#0a1854), texto "Segura" (#0284c7)
const estadoFortaleza = computed(() => {
  const score = puntajeFortaleza.value
  if (!form.value.contrasena) {
    return { 
      nivel: 'vacio', 
      texto: 'Fortaleza de la contraseña', 
      colorTexto: 'text-gray-600', 
      barras: ['gray', 'gray', 'gray'] 
    }
  }
  if (score <= 1) {
    return { 
      nivel: 'debil', 
      texto: 'Débil', 
      colorTexto: 'text-[#ef4444]', 
      barras: ['red', 'gray', 'gray'] 
    }
  }
  if (score <= 3) {
    return { 
      nivel: 'media', 
      texto: 'Media', 
      colorTexto: 'text-[#f97316]', 
      barras: ['orange', 'orange', 'gray'] 
    }
  }
  // Score 4: "Segura" con las 3 barras azul marino (#0a1854)
  return { 
    nivel: 'segura', 
    texto: 'Segura', 
    colorTexto: 'text-[#0284c7]', 
    barras: ['navy', 'navy', 'navy'] 
  }
})

// Validación de coincidencia de contraseña
const contrasenasCoinciden = computed(() => {
  return form.value.contrasena && form.value.contrasena === form.value.confirmarContrasena
})

// Habilitación del botón "Siguiente" en Paso 2
const puedeEnviarPaso2 = computed(() => {
  return (
    tieneMinimo8.value &&
    tieneMayuscula.value &&
    tieneMinuscula.value &&
    tieneNumeroOSimbolo.value &&
    contrasenasCoinciden.value &&
    form.value.aceptaTerminos &&
    !loading.value
  )
})

// Validar coincidencia de contraseña reactivamente
watch(() => form.value.confirmarContrasena, (val) => {
  if (tocados.value.confirmarContrasena && val) {
    if (val !== form.value.contrasena) {
      errores.value.confirmarContrasena = 'Las contraseñas no coinciden'
    } else {
      errores.value.confirmarContrasena = ''
    }
  }
})

// --- VALIDACIONES PASO 1 ---
const validarPaso1 = () => {
  let valido = true
  errores.value.nombres = ''
  errores.value.apellidos = ''
  errores.value.correo = ''

  if (!form.value.nombres.trim()) {
    errores.value.nombres = 'Este campo es requerido'
    valido = false
  } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]{2,50}$/u.test(form.value.nombres.trim())) {
    errores.value.nombres = 'Ingrese un nombre válido (solo letras, mín. 2 caracteres)'
    valido = false
  }

  if (!form.value.apellidos.trim()) {
    errores.value.apellidos = 'Este campo es requerido'
    valido = false
  } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]{2,50}$/u.test(form.value.apellidos.trim())) {
    errores.value.apellidos = 'Ingrese un apellido válido (solo letras, mín. 2 caracteres)'
    valido = false
  }

  const correoNormalizado = form.value.correo.trim().toLowerCase()
  if (!correoNormalizado) {
    errores.value.correo = 'Este campo es requerido'
    valido = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoNormalizado)) {
    errores.value.correo = 'Ingrese un correo electrónico válido'
    valido = false
  }

  return valido
}

// Avanzar al Paso 2 con verificación de correo institucional
const continuarAPaso2 = async () => {
  tocados.value.nombres = true
  tocados.value.apellidos = true
  tocados.value.correo = true
  errorGeneral.value = ''

  if (!validarPaso1()) return

  loading.value = true
  try {
    const res = await authService.verificarCorreo(form.value.correo.trim().toLowerCase())
    if (res && res.disponible) {
      errores.value.correo = ''
      estadoFlujo.value = 2
    } else {
      estadoFlujo.value = 2
    }
  } catch (err) {
    if (err.response && err.response.status === 422 && err.response.data) {
      errores.value.correo = err.response.data.mensaje || 'Correo institucional ya registrado'
    } else {
      // Si el endpoint aún no está desplegado en el backend (ej. 404), avanzar directamente al Paso 2
      estadoFlujo.value = 2
    }
  } finally {
    loading.value = false
  }
}

// Regresar de paso o cerrar
const regresarPaso = () => {
  if (estadoFlujo.value === 2) {
    estadoFlujo.value = 1
    errorGeneral.value = ''
  } else {
    emit('close')
  }
}

// Enviar formulario a la API y transicionar a "validando" y "exitoso"
const enviarRegistro = async () => {
  tocados.value.contrasena = true
  tocados.value.confirmarContrasena = true
  errorGeneral.value = ''

  if (!puedeEnviarPaso2.value) return

  // 1. Mostrar modal de carga/validación (Screenshot 3)
  estadoFlujo.value = 'validando'
  loading.value = true

  const tiempoInicio = Date.now()

  try {
    const payload = {
      nombres: form.value.nombres.trim(),
      apellidos: form.value.apellidos.trim(),
      correo: form.value.correo.trim().toLowerCase(),
      contrasena: form.value.contrasena
    }

    const respuesta = await authService.registro(payload)

    // Garantizar que la animación de validación sea visible (mínimo 1.5s)
    const transcurrido = Date.now() - tiempoInicio
    if (transcurrido < 1500) {
      await new Promise(r => setTimeout(r, 1500 - transcurrido))
    }

    // 2. Transicionar a modal de éxito (Screenshot 4)
    estadoFlujo.value = 'exitoso'
    emit('registro-exitoso', respuesta)
  } catch (err) {
    // Si ocurre un error, retornar al Paso 2 para mostrar los mensajes correspondientes
    estadoFlujo.value = 2

    if (err.response && err.response.data) {
      const datos = err.response.data
      if (datos.errores) {
        if (datos.errores.correo) {
          estadoFlujo.value = 1
          errores.value.correo = datos.errores.correo[0]
        }
        if (datos.errores.nombres) {
          estadoFlujo.value = 1
          errores.value.nombres = datos.errores.nombres[0]
        }
        if (datos.errores.apellidos) {
          estadoFlujo.value = 1
          errores.value.apellidos = datos.errores.apellidos[0]
        }
        if (datos.errores.contrasena) {
          errores.value.contrasena = datos.errores.contrasena[0]
        }
      }
      errorGeneral.value = datos.mensaje || 'No fue posible completar el registro. Intente nuevamente.'
    } else {
      errorGeneral.value = 'Error de conexión con el servidor. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}

// Cerrar e ir al inicio de sesión
const cerrarYIrALogin = () => {
  emit('close')
  emit('abrir-login')
}
</script>

<template>
  <div 
    class="registro-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-cover bg-center overflow-y-auto"
    style="background-image: url('/images/Fondo_login.png');"
  >
    <!-- Capa de oscurecimiento suave -->
    <div class="fixed inset-0 bg-[#071329]/40 backdrop-blur-[1px] pointer-events-none"></div>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- ESTADO: VALIDANDO DATOS (Screenshot 3)                       -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div 
      v-if="estadoFlujo === 'validando'"
      class="relative w-full max-w-[490px] bg-[#020b45] text-white rounded-[20px] shadow-2xl p-7 sm:p-8 my-auto z-10 text-left border border-blue-900/40"
    >
      <div class="flex items-center gap-2 mb-2 font-serif text-[20px] text-white font-normal">
        <i class="bi bi-check-circle text-lg shrink-0"></i>
        <span>Datos enviados correctamente</span>
      </div>
      <p class="text-white/90 text-sm font-sans mb-8">
        Tus datos están siendo validados, espera un momento
      </p>
      
      <!-- Spinner circular brillante centrado -->
      <div class="flex justify-center my-4">
        <div class="w-14 h-14 rounded-full border-2 border-white/20 border-t-white animate-spin"></div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- ESTADO: CREACIÓN DE CUENTA EXITOSA (Screenshot 4)            -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div 
      v-else-if="estadoFlujo === 'exitoso'"
      class="relative w-full max-w-[490px] bg-[#10a34b] text-white rounded-[20px] shadow-2xl p-7 sm:p-8 my-auto z-10 text-left"
    >
      <!-- Botón cerrar (X) en esquina superior derecha -->
      <button 
        type="button" 
        @click="cerrarYIrALogin"
        class="absolute top-5 right-5 text-white/80 hover:text-white text-lg font-bold cursor-pointer"
        title="Cerrar"
      >
        ✕
      </button>

      <div class="flex items-center gap-2 mb-2 font-serif text-[20px] text-white font-normal">
        <i class="bi bi-check-circle text-lg shrink-0"></i>
        <span>Creación de cuenta exitosa</span>
      </div>
      <p class="text-white font-medium text-sm font-sans mb-7">
        Los datos se han validado correctamentre.
      </p>

      <!-- Botón Aceptar centrado -->
      <div class="flex justify-center mt-6">
        <button 
          type="button" 
          @click="cerrarYIrALogin"
          class="px-14 py-2 border border-white text-white rounded-full text-sm font-normal hover:bg-white/15 transition-all cursor-pointer shadow-sm active:scale-95"
        >
          Aceptar
        </button>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- TARJETA PRINCIPAL DEL FORMULARIO (PASO 1 Y PASO 2)           -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div 
      v-else
      class="relative w-full max-w-[460px] bg-white rounded-[26px] shadow-2xl p-7 sm:p-9 my-auto z-10 transition-all"
    >
      <!-- Botón de regreso (flecha izquierda) -->
      <button 
        type="button" 
        @click="regresarPaso"
        class="absolute top-6 left-6 text-gray-800 hover:text-black p-1.5 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
        title="Volver"
        id="btn-back-registro"
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
        <h2 class="text-[26px] font-bold text-gray-950 font-serif tracking-tight">
          Crea una cuenta
        </h2>
        <p class="text-sm text-gray-600 mt-1 font-sans">
          <template v-if="estadoFlujo === 1">
            Ingresa tus datos para crear una cuenta
          </template>
          <template v-else>
            Protege tu cuenta con una contraseña segura
          </template>
        </p>
      </div>

      <!-- Alerta de Error General si ocurre en la API -->
      <div 
        v-if="errorGeneral" 
        class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2 text-left"
      >
        <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5 text-sm shrink-0"></i>
        <span>{{ errorGeneral }}</span>
      </div>

      <!-- ═══════════════════════════════════════ -->
      <!-- PASO 1: DATOS PERSONALES / CORREO     -->
      <!-- ═══════════════════════════════════════ -->
      <form v-if="estadoFlujo === 1" @submit.prevent="continuarAPaso2" novalidate>
        <!-- Campo: Nombres -->
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-900 mb-1.5">
            Nombres<span class="text-red-500">*</span>
          </label>
          <input 
            type="text"
            v-model="form.nombres"
            @blur="tocados.nombres = true; validarPaso1()"
            placeholder="Dallana Lucia"
            id="input-nombres"
            class="w-full px-4 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all"
            :class="{ 'border-red-400 bg-red-50/30': errores.nombres }"
          />
          <p v-if="errores.nombres" class="flex items-center gap-1.5 text-[#dc2626] text-xs mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[13px]"></i>
            <span>{{ errores.nombres }}</span>
          </p>
        </div>

        <!-- Campo: Apellidos -->
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-900 mb-1.5">
            Apellidos<span class="text-red-500">*</span>
          </label>
          <input 
            type="text"
            v-model="form.apellidos"
            @blur="tocados.apellidos = true; validarPaso1()"
            placeholder="Campos Garcia"
            id="input-apellidos"
            class="w-full px-4 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all"
            :class="{ 'border-red-400 bg-red-50/30': errores.apellidos }"
          />
          <p v-if="errores.apellidos" class="flex items-center gap-1.5 text-[#dc2626] text-xs mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[13px]"></i>
            <span>{{ errores.apellidos }}</span>
          </p>
        </div>

        <!-- Campo: Correo Institucional -->
        <div class="mb-6 text-left">
          <label class="block text-sm font-semibold text-gray-900 mb-1.5">
            Correo institucional<span class="text-red-500">*</span>
          </label>
          <input 
            type="email"
            v-model="form.correo"
            @blur="tocados.correo = true; validarPaso1()"
            placeholder="usss@000ugb.edu.sv"
            id="input-correo"
            class="w-full px-4 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all"
            :class="{ 'border-red-400 bg-red-50/30': errores.correo }"
          />
          <p v-if="errores.correo" class="flex items-center gap-1.5 text-[#dc2626] text-xs mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[13px]"></i>
            <span>{{ errores.correo }}</span>
          </p>
        </div>

        <!-- Botón Siguiente (Paso 1) -->
        <div class="text-center pt-2">
          <button 
            type="submit"
            :disabled="loading"
            id="btn-siguiente-paso1"
            class="w-44 mx-auto py-2.5 px-6 rounded-full bg-[#0a1854] hover:bg-[#07113d] text-white font-medium text-sm transition-all shadow-md active:scale-95 cursor-pointer disabled:opacity-75 disabled:cursor-wait flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ loading ? 'Verificando...' : 'Siguiente' }}</span>
          </button>
        </div>

        <!-- Enlace Iniciar Sesión -->
        <div class="text-center mt-6">
          <p class="text-xs text-gray-800">
            ¿Ya tienes una cuenta? 
            <button 
              type="button" 
              @click="emit('abrir-login')" 
              class="text-blue-600 underline font-medium hover:text-blue-800 cursor-pointer ml-1"
            >
              Iniciar sesión
            </button>
          </p>
        </div>
      </form>

      <!-- ═══════════════════════════════════════ -->
      <!-- PASO 2: CONTRASEÑA Y TÉRMINOS         -->
      <!-- ═══════════════════════════════════════ -->
      <form v-else-if="estadoFlujo === 2" @submit.prevent="enviarRegistro" novalidate>
        <!-- Campo: Contraseña -->
        <div class="mb-3 text-left">
          <label class="block text-sm font-semibold text-gray-900 mb-1.5">
            Contraseña<span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input 
              :type="mostrarContrasena ? 'text' : 'password'"
              v-model="form.contrasena"
              @blur="tocados.contrasena = true"
              placeholder="••••••••••"
              id="input-contrasena"
              class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all"
            />
            <button 
              type="button" 
              @click="mostrarContrasena = !mostrarContrasena"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
              id="btn-toggle-password"
            >
              <i :class="mostrarContrasena ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
            </button>
          </div>

          <!-- Medidor de 3 barras de fortaleza (Screenshot 1, 2, 4, 5) -->
          <div class="flex gap-2.5 mt-2.5 mb-1.5">
            <!-- Barra 1 -->
            <div 
              class="h-1.5 flex-1 rounded-full transition-colors duration-300"
              :class="{
                'bg-gray-300': estadoFortaleza.barras[0] === 'gray',
                'bg-[#ef4444]': estadoFortaleza.barras[0] === 'red',
                'bg-[#f97316]': estadoFortaleza.barras[0] === 'orange',
                'bg-[#0a1854]': estadoFortaleza.barras[0] === 'navy',
              }"
            ></div>
            <!-- Barra 2 -->
            <div 
              class="h-1.5 flex-1 rounded-full transition-colors duration-300"
              :class="{
                'bg-gray-300': estadoFortaleza.barras[1] === 'gray',
                'bg-[#f97316]': estadoFortaleza.barras[1] === 'orange',
                'bg-[#0a1854]': estadoFortaleza.barras[1] === 'navy',
              }"
            ></div>
            <!-- Barra 3 -->
            <div 
              class="h-1.5 flex-1 rounded-full transition-colors duration-300"
              :class="{
                'bg-gray-300': estadoFortaleza.barras[2] === 'gray',
                'bg-[#0a1854]': estadoFortaleza.barras[2] === 'navy',
              }"
            ></div>
          </div>

          <!-- Texto de Fortaleza / Estado -->
          <div class="mb-2">
            <span 
              class="text-xs font-medium transition-colors"
              :class="estadoFortaleza.colorTexto"
            >
              {{ estadoFortaleza.texto }}
            </span>
          </div>

          <!-- Requisitos de Contraseña -->
          <div class="space-y-1.5 mt-2 pt-1 border-t border-gray-100">
            <!-- Mínimo 8 caracteres -->
            <div class="flex items-center gap-2 text-xs text-gray-700">
              <i 
                :class="tieneMinimo8 ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
                class="text-sm transition-colors"
              ></i>
              <span>Mínimo 8 caracteres</span>
            </div>
            <!-- Una letra mayúscula -->
            <div class="flex items-center gap-2 text-xs text-gray-700">
              <i 
                :class="tieneMayuscula ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
                class="text-sm transition-colors"
              ></i>
              <span>Una letra mayúscula</span>
            </div>
            <!-- Una letra minúscula -->
            <div class="flex items-center gap-2 text-xs text-gray-700">
              <i 
                :class="tieneMinuscula ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
                class="text-sm transition-colors"
              ></i>
              <span>Una letra minúscula</span>
            </div>
            <!-- Un número o símbolo -->
            <div class="flex items-center gap-2 text-xs text-gray-700">
              <i 
                :class="tieneNumeroOSimbolo ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
                class="text-sm transition-colors"
              ></i>
              <span>Un numero o símbolo</span>
            </div>
          </div>
        </div>

        <!-- Campo: Confirmar Contraseña -->
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-900 mb-1.5">
            Confirmar contraseña<span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input 
              :type="mostrarConfirmContrasena ? 'text' : 'password'"
              v-model="form.confirmarContrasena"
              @blur="tocados.confirmarContrasena = true"
              placeholder="••••••"
              id="input-confirmar-contrasena"
              class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all"
              :class="{ 'border-red-400 bg-red-50/30': errores.confirmarContrasena }"
            />
            <button 
              type="button" 
              @click="mostrarConfirmContrasena = !mostrarConfirmContrasena"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
              id="btn-toggle-confirm-password"
            >
              <i :class="mostrarConfirmContrasena ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
            </button>
          </div>
          <p v-if="errores.confirmarContrasena" class="flex items-center gap-1.5 text-[#dc2626] text-xs mt-1.5 font-medium">
            <i class="bi bi-exclamation-circle text-[13px]"></i>
            <span>{{ errores.confirmarContrasena }}</span>
          </p>
        </div>

        <!-- Checkbox Términos y Condiciones -->
        <div class="mb-6 text-left">
          <label class="flex items-start gap-2.5 text-xs text-gray-800 cursor-pointer select-none leading-tight">
            <input 
              type="checkbox"
              v-model="form.aceptaTerminos"
              id="checkbox-terminos"
              class="mt-0.5 rounded border-gray-400 text-[#0a1854] focus:ring-[#0a1854] w-4 h-4 cursor-pointer"
            />
            <span>
              He leído y acepto los 
              <button 
                type="button" 
                @click.prevent="mostrarModalTerminos = true" 
                class="text-blue-600 underline font-medium hover:text-blue-800 cursor-pointer"
              >
                Términos y Condiciones de uso
              </button>
            </span>
          </label>
        </div>

        <!-- Botón Siguiente (Paso 2) -->
        <div class="text-center pt-1">
          <button 
            type="submit"
            :disabled="!puedeEnviarPaso2 || loading"
            id="btn-siguiente-paso2"
            class="w-44 mx-auto py-2.5 px-6 rounded-full font-medium text-sm transition-all shadow-md flex items-center justify-center gap-2"
            :class="puedeEnviarPaso2 ? 'bg-[#0a1854] hover:bg-[#07113d] text-white cursor-pointer active:scale-95' : 'bg-[#888eb8] text-white/90 cursor-not-allowed'"
          >
            <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ loading ? 'Registrando...' : 'Siguiente' }}</span>
          </button>
        </div>

        <!-- Enlace Iniciar Sesión -->
        <div class="text-center mt-6">
          <p class="text-xs text-gray-800">
            ¿Ya tienes una cuenta? 
            <button 
              type="button" 
              @click="emit('abrir-login')" 
              class="text-blue-600 underline font-medium hover:text-blue-800 cursor-pointer ml-1"
            >
              Iniciar sesión
            </button>
          </p>
        </div>
      </form>

    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- MODAL DE TÉRMINOS Y CONDICIONES       -->
    <!-- ═══════════════════════════════════════ -->
    <div 
      v-if="mostrarModalTerminos" 
      class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative max-h-[85vh] flex flex-col text-left">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
          <h3 class="text-lg font-bold text-gray-900 font-serif">Términos y Condiciones de Uso</h3>
          <button 
            type="button" 
            @click="mostrarModalTerminos = false" 
            class="text-gray-500 hover:text-gray-800 text-xl font-bold p-1 cursor-pointer"
          >
            ✕
          </button>
        </div>
        <div class="py-4 text-xs text-gray-700 overflow-y-auto space-y-3 leading-relaxed flex-1 pr-1">
          <p class="font-semibold text-gray-900">1. Aceptación del Sistema Académico</p>
          <p>El acceso a la plataforma Génesis Profesional de la Universidad Gerardo Barrios (UGB) está reservado para estudiantes activos, egresados, supervisores y tutores autorizados. El uso de la cuenta institucional es personal e intransferible.</p>
          
          <p class="font-semibold text-gray-900">2. Confidencialidad y Manejo de Credenciales</p>
          <p>El usuario es responsable de mantener la confidencialidad de sus credenciales de acceso y de toda la actividad que se desarrolle en su cuenta. La contraseña debe cumplir con los lineamientos de seguridad establecidos.</p>
          
          <p class="font-semibold text-gray-900">3. Veracidad de la Información</p>
          <p>El usuario declara que los datos suministrados son fidedignos y corresponden a su registro oficial en los padrones universitarios de la UGB.</p>
        </div>
        <div class="pt-3 border-t border-gray-200 text-right">
          <button 
            type="button" 
            @click="form.aceptaTerminos = true; mostrarModalTerminos = false" 
            class="py-2 px-5 rounded-full bg-[#0a1854] text-white text-xs font-semibold hover:bg-[#07113d] cursor-pointer"
          >
            Aceptar y Continuar
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Tipografía Lora para títulos serif */
h2, h3 {
  font-family: 'Lora', Georgia, serif;
}
</style>
