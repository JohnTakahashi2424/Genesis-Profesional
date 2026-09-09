<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue'
import authService from '../services/authService'
import { PasswordStrengthMeter } from './common'

const emit = defineEmits(['close', 'volver-login'])

// Control del paso actual (1: Correo, 2: Código 6 dígitos, 3: Nueva contraseña, 4: Éxito)
const paso = ref(1)

// Estados de carga y errores
const loading = ref(false)
const errorPaso1 = ref('')
const errorPaso2 = ref('')
const errorPaso3 = ref('')

// Paso 1: Correo
const correo = ref('')
const correoEnmascarado = ref('')

// Paso 2: 6 dígitos y temporizador
const digitos = ref(['', '', '', '', '', ''])
const segundosRestantes = ref(90)
let timerInterval = null

// Paso 3: Contraseñas
const nuevaContrasena = ref('')
const confirmarContrasena = ref('')
const mostrarPassword1 = ref(false)
const mostrarPassword2 = ref(false)
const passwordEsValida = ref(false)

// Iniciar temporizador de 90 segundos (1:30)
const iniciarTemporizador = (segundos = 90) => {
  if (timerInterval) clearInterval(timerInterval)
  segundosRestantes.value = segundos
  timerInterval = setInterval(() => {
    if (segundosRestantes.value > 0) {
      segundosRestantes.value--
    } else {
      clearInterval(timerInterval)
      timerInterval = null
    }
  }, 1000)
}

// Formatear segundos a M:SS (ej. 90 -> 1:30)
const formatearTiempo = (totalSegundos) => {
  const minutos = Math.floor(totalSegundos / 60)
  const segundos = totalSegundos % 60
  return `${minutos}:${segundos < 10 ? '0' : ''}${segundos}`
}

// Comprobación si los 6 dígitos están completos
const codigoCompleto = computed(() => {
  return digitos.value.every(d => d.trim().length === 1)
})

// Código concatenado
const codigoTexto = computed(() => {
  return digitos.value.join('')
})

// Validación del paso 1: correo no vacío
const correoEsValido = computed(() => correo.value.trim().length > 0)

// Validación del paso 3: coincidencia y robustez
const contrasenasCoinciden = computed(() => {
  return nuevaContrasena.value && nuevaContrasena.value === confirmarContrasena.value
})

const puedeCambiarContrasena = computed(() => {
  return passwordEsValida.value && contrasenasCoinciden.value
})

// Enmascarar correo de respaldo en cliente si no viene del backend
const enmascarar = (str) => {
  if (!str) return 'us*********'
  const partes = str.split('@')
  const prefijo = partes[0]
  const long = prefijo.length
  return prefijo.substring(0, 2) + '*'.repeat(Math.max(long - 2, 9))
}

// --- ACCIONES DEL PASO 1 ---
const handleEnviarCodigo = async () => {
  errorPaso1.value = ''
  const correoLimpio = correo.value.trim().toLowerCase()

  if (!correoLimpio) {
    errorPaso1.value = 'Debe ingresar su correo institucional.'
    return
  }

  const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!regexEmail.test(correoLimpio)) {
    errorPaso1.value = 'El formato del correo institucional es inválido.'
    return
  }

  loading.value = true
  try {
    const res = await authService.enviarCodigo(correoLimpio)
    correoEnmascarado.value = res.correo_enmascarado || enmascarar(correoLimpio)
    paso.value = 2
    iniciarTemporizador(res.tiempo_expiracion_segundos || 90)

    // Enfocar automáticamente primera casilla en paso 2
    nextTick(() => {
      const primerInput = document.getElementById('digito-0')
      if (primerInput) primerInput.focus()
    })
  } catch (err) {
    if (err.response && err.response.data) {
      errorPaso1.value = err.response.data.mensaje || 'No fue posible procesar la solicitud.'
    } else {
      errorPaso1.value = 'Error al conectar con el servidor. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}

// --- ACCIONES DEL PASO 2: Manejo de casillas de dígitos ---
const handleInputDigito = (index, evento) => {
  const valor = evento.target.value
  errorPaso2.value = ''

  // Filtrar solo números
  const soloNumero = valor.replace(/[^0-9]/g, '')
  digitos.value[index] = soloNumero ? soloNumero[soloNumero.length - 1] : ''

  // Avanzar al siguiente input automáticamente si se escribió un número
  if (digitos.value[index] && index < 5) {
    nextTick(() => {
      const siguiente = document.getElementById(`digito-${index + 1}`)
      if (siguiente) siguiente.focus()
    })
  }
}

const handleKeydownDigito = (index, evento) => {
  if (evento.key === 'Backspace' && !digitos.value[index] && index > 0) {
    nextTick(() => {
      const anterior = document.getElementById(`digito-${index - 1}`)
      if (anterior) {
        anterior.focus()
        digitos.value[index - 1] = ''
      }
    })
  }
}

const handlePasteCodigo = (evento) => {
  evento.preventDefault()
  const pegado = (evento.clipboardData || window.clipboardData).getData('text')
  const numeros = pegado.replace(/[^0-9]/g, '').slice(0, 6)

  for (let i = 0; i < 6; i++) {
    digitos.value[i] = numeros[i] || ''
  }

  const ultimoIndex = Math.min(numeros.length, 5)
  nextTick(() => {
    const inputFocus = document.getElementById(`digito-${ultimoIndex}`)
    if (inputFocus) inputFocus.focus()
  })
}

// Reenviar código
const handleReenviarCodigo = async () => {
  if (segundosRestantes.value > 0 || loading.value) return
  errorPaso2.value = ''
  loading.value = true

  try {
    const res = await authService.enviarCodigo(correo.value.trim().toLowerCase())
    iniciarTemporizador(res.tiempo_expiracion_segundos || 90)
    digitos.value = ['', '', '', '', '', '']
    nextTick(() => {
      const primerInput = document.getElementById('digito-0')
      if (primerInput) primerInput.focus()
    })
  } catch (err) {
    errorPaso2.value = err.response?.data?.mensaje || 'Error al reenviar el código. Intente de nuevo.'
  } finally {
    loading.value = false
  }
}

// Verificar código
const handleVerificarCodigo = async () => {
  if (!codigoCompleto.value || loading.value) return
  errorPaso2.value = ''
  loading.value = true

  try {
    const res = await authService.verificarCodigo(
      correo.value.trim().toLowerCase(),
      codigoTexto.value
    )
    if (res.valido || res.status === 'success') {
      paso.value = 3
    } else {
      errorPaso2.value = res.mensaje || 'Código de verificación incorrecto.'
    }
  } catch (err) {
    if (err.response && err.response.data) {
      errorPaso2.value = err.response.data.mensaje || 'Código de verificación incorrecto. Inténtalo de nuevo.'
    } else {
      errorPaso2.value = 'Error al verificar el código. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}

// Cancelar y volver al login
const handleCancelar = () => {
  emit('volver-login')
}

// --- ACCIONES DEL PASO 3: Restablecer contraseña ---
const handleCambiarContrasena = async () => {
  errorPaso3.value = ''

  if (!nuevaContrasena.value || !confirmarContrasena.value) {
    errorPaso3.value = 'Debe completar ambos campos de contraseña.'
    return
  }

  if (!passwordEsValida.value) {
    errorPaso3.value = 'La contraseña debe cumplir con todos los requisitos de seguridad.'
    return
  }

  if (!contrasenasCoinciden.value) {
    errorPaso3.value = 'Las contraseñas no coinciden.'
    return
  }

  loading.value = true
  try {
    await authService.recuperar({
      correo: correo.value.trim().toLowerCase(),
      codigo: codigoTexto.value,
      contrasena: nuevaContrasena.value,
      confirmar_contrasena: confirmarContrasena.value
    })

    paso.value = 4
  } catch (err) {
    if (err.response && err.response.data) {
      errorPaso3.value = err.response.data.mensaje || 'No fue posible actualizar la contraseña.'
    } else {
      errorPaso3.value = 'Error de conexión con el servidor. Intente más tarde.'
    }
  } finally {
    loading.value = false
  }
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
  <div
    class="recuperar-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-cover bg-center overflow-y-auto"
    style="background-image: url('/images/Fondo_login.png');"
  >
    <!-- Capa de oscurecimiento suave -->
    <div class="fixed inset-0 bg-[#071329]/45 backdrop-blur-[2px] pointer-events-none"></div>

    <!-- TARJETA PRINCIPAL — mismo ancho que LoginModal -->
    <div class="relative w-full max-w-[400px] sm:max-w-[420px] bg-white rounded-[26px] shadow-2xl px-7 py-8 sm:px-9 sm:py-9 my-auto z-10 text-center">

      <!-- Botón de regreso — flecha izquierda arriba a la izquierda (igual que Login) -->
      <button
        type="button"
        @click="paso > 1 ? (paso === 2 ? (paso = 1) : (paso === 3 ? (paso = 2) : emit('close'))) : emit('close')"
        class="absolute top-6 left-6 text-gray-800 hover:text-black p-1 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
        title="Regresar"
        id="btn-back-recuperar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="18" viewBox="0 0 25 18" fill="none">
          <path d="M1 9H24M7.57143 1L1 9L7.57143 17" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <!-- Logo UGB — presente en todos los pasos (igual que Login) -->
      <div class="flex justify-center mb-2">
        <img
          src="/images/logo_ugb.png"
          alt="Universidad Gerardo Barrios"
          class="h-16 w-auto object-contain"
        />
      </div>

      <!-- ========================================== -->
      <!-- PASO 1: INGRESAR CORREO                    -->
      <!-- ========================================== -->
      <div v-if="paso === 1">
        <!-- Título + subtítulo — mismo patrón que Login -->
        <div class="text-center mb-5">
          <h2 class="text-[26px] font-bold text-gray-950 tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            Restablecer contraseña
          </h2>
          <p class="text-[13px] font-medium text-gray-800 mt-1 font-sans">
            ¿Olvidaste tu contraseña?
          </p>
          <p class="text-[12px] text-gray-600 font-sans">
            Ingresa tu correo y te enviaremos un código de verificación
          </p>
        </div>

        <form @submit.prevent="handleEnviarCodigo" class="text-left" novalidate>
          <!-- Campo Correo* — misma estructura que Login -->
          <div class="mb-4">
            <label for="input-recuperar-correo" class="block text-sm font-semibold text-gray-900 mb-1">
              Correo<span class="text-red-500">*</span>
            </label>
            <input
              id="input-recuperar-correo"
              v-model="correo"
              type="email"
              placeholder="usss@000ugb.edu.sv"
              maxlength="100"
              :disabled="loading"
              @input="errorPaso1 = ''"
              class="w-full px-3.5 py-2.5 rounded-[12px] bg-[#ebebeb] border text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none transition-colors disabled:opacity-60"
              :class="errorPaso1 ? 'border-red-500' : 'border-gray-500'"
            />
            <p v-if="errorPaso1" class="flex items-center gap-1 text-red-600 text-[11px] mt-1.5 font-medium">
              <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
              <span>{{ errorPaso1 }}</span>
            </p>
          </div>

          <!-- Botón Enviar código — mismo estilo y comportamiento que Login -->
          <div class="text-center mt-3 mb-5">
            <button
              type="submit"
              id="btn-enviar-codigo"
              :disabled="loading"
              class="w-[171px] h-[51px] rounded-[18px] border border-white text-white text-sm font-medium transition-all flex items-center justify-center gap-[10px] px-[10px] mx-auto cursor-pointer select-none disabled:cursor-not-allowed"
              :class="correoEsValido
                ? 'bg-[#010c67] hover:bg-[#01094f] active:scale-[0.98] shadow-md'
                : 'bg-[#888eb8] shadow-sm'"
              style="font-family: 'Lora', Georgia, serif;"
            >
              <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0"></span>
              <span>{{ loading ? 'Enviando...' : 'Enviar código' }}</span>
            </button>
          </div>

          <!-- Footer — volver al login -->
          <div class="text-center mt-2">
            <p class="text-xs text-gray-900">
              ¿Ya recuerdas tu contraseña?
              <button
                type="button"
                @click="emit('volver-login')"
                class="text-[#2563eb] underline font-medium hover:text-[#1d4ed8] cursor-pointer ml-1"
              >
                Iniciar sesión
              </button>
            </p>
          </div>
        </form>
      </div>

      <!-- ========================================== -->
      <!-- PASO 2: INGRESAR 6 DÍGITOS                 -->
      <!-- ========================================== -->
      <div v-else-if="paso === 2">
        <div class="text-center mb-5">
          <h2 class="text-[26px] font-bold text-gray-950 tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            Verifica tu correo
          </h2>
          <p class="text-[13px] font-medium text-gray-800 mt-1 font-sans">
            Introduce el código de verificación
          </p>
          <p class="text-[12px] text-gray-600 font-sans">
            enviado al correo <strong class="text-gray-900">{{ correoEnmascarado }}</strong>
          </p>
        </div>

        <!-- 6 Casillas individuales para el código -->
        <div class="flex justify-center items-center gap-2 sm:gap-2.5 my-5">
          <input
            v-for="(digito, index) in digitos"
            :key="index"
            :id="'digito-' + index"
            v-model="digitos[index]"
            type="text"
            inputmode="numeric"
            maxlength="1"
            :disabled="loading"
            @input="handleInputDigito(index, $event)"
            @keydown="handleKeydownDigito(index, $event)"
            @paste="handlePasteCodigo"
            class="w-10 h-12 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-bold bg-[#ebebeb] border rounded-[10px] text-gray-900 focus:bg-white focus:border-[#010c67] focus:outline-none transition-all shadow-sm disabled:opacity-60"
            :class="errorPaso2 ? 'border-red-500 bg-red-50/20' : 'border-gray-500'"
          />
        </div>

        <!-- Mensaje de error paso 2 -->
        <p v-if="errorPaso2" class="flex items-center justify-center gap-1 text-red-600 text-[11px] mb-3 font-medium">
          <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
          <span>{{ errorPaso2 }}</span>
        </p>

        <!-- Temporizador y reenvío -->
        <div class="my-3 text-xs text-gray-600 text-center">
          ¿Aún no recibes el código?
          <button
            v-if="segundosRestantes === 0"
            type="button"
            @click="handleReenviarCodigo"
            class="text-[#2563eb] underline font-medium hover:text-[#1d4ed8] cursor-pointer ml-1"
          >
            Reenviar código
          </button>
          <span v-else class="ml-1">
            Reenviar código en
            <span class="font-semibold text-gray-800">{{ formatearTiempo(segundosRestantes) }} seg.</span>
          </span>
        </div>

        <!-- Botones Cancelar (vino) y Verificar (lavanda→azul marino) -->
        <div class="flex items-center justify-center gap-3 sm:gap-4 mt-5 mb-2">
          <button
            type="button"
            @click="handleCancelar"
            class="w-[130px] h-[51px] rounded-[18px] bg-[#5b0612] hover:bg-[#48040d] active:scale-[0.98] text-white text-sm font-medium transition-all cursor-pointer"
            style="font-family: 'Lora', Georgia, serif;"
          >
            Cancelar
          </button>

          <button
            type="button"
            id="btn-verificar-codigo"
            :disabled="!codigoCompleto || loading"
            @click="handleVerificarCodigo"
            class="w-[130px] h-[51px] rounded-[18px] border border-white text-white text-sm font-medium transition-all flex items-center justify-center gap-[10px] px-[10px] select-none disabled:cursor-not-allowed"
            :class="codigoCompleto
              ? 'bg-[#010c67] hover:bg-[#01094f] active:scale-[0.98] cursor-pointer'
              : 'bg-[#888eb8] opacity-90'"
            style="font-family: 'Lora', Georgia, serif;"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0"></span>
            <span>{{ loading ? 'Verificando...' : 'Verificar' }}</span>
          </button>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- PASO 3: NUEVA CONTRASEÑA                   -->
      <!-- ========================================== -->
      <div v-else-if="paso === 3">
        <div class="text-center mb-5">
          <h2 class="text-[26px] font-bold text-gray-950 tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            Nueva contraseña
          </h2>
          <p class="text-[13px] font-medium text-gray-800 mt-1 font-sans">
            Protege tu cuenta con una contraseña segura
          </p>
        </div>

        <form @submit.prevent="handleCambiarContrasena" class="text-left" novalidate>

          <!-- Contraseña* -->
          <div class="mb-2">
            <label for="input-nueva-pass" class="block text-sm font-semibold text-gray-900 mb-1">
              Contraseña<span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                id="input-nueva-pass"
                v-model="nuevaContrasena"
                :type="mostrarPassword1 ? 'text' : 'password'"
                placeholder="••••••••"
                maxlength="100"
                :disabled="loading"
                @input="errorPaso3 = ''"
                class="w-full px-3.5 py-2.5 pr-11 rounded-[12px] bg-[#ebebeb] border border-gray-500 text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none transition-colors disabled:opacity-60"
              />
              <button
                type="button"
                @click="mostrarPassword1 = !mostrarPassword1"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
                tabindex="-1"
              >
                <i :class="mostrarPassword1 ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
              </button>
            </div>
          </div>

          <!-- Medidor de fortaleza (3 barras y checklist de 4 puntos) -->
          <PasswordStrengthMeter
            :password="nuevaContrasena"
            @update:isValid="val => passwordEsValida = val"
          />

          <!-- Confirmar Contraseña* -->
          <div class="mb-4">
            <label for="input-confirmar-nueva-pass" class="block text-sm font-semibold text-gray-900 mb-1">
              Confirmar contraseña<span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                id="input-confirmar-nueva-pass"
                v-model="confirmarContrasena"
                :type="mostrarPassword2 ? 'text' : 'password'"
                placeholder="••••••••"
                maxlength="100"
                :disabled="loading"
                @input="errorPaso3 = ''"
                class="w-full px-3.5 py-2.5 pr-11 rounded-[12px] bg-[#ebebeb] border text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none transition-colors disabled:opacity-60"
                :class="confirmarContrasena && !contrasenasCoinciden ? 'border-red-500' : 'border-gray-500'"
              />
              <button
                type="button"
                @click="mostrarPassword2 = !mostrarPassword2"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
                tabindex="-1"
              >
                <i :class="mostrarPassword2 ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
              </button>
            </div>
            <p v-if="confirmarContrasena && !contrasenasCoinciden" class="flex items-center gap-1 text-red-600 text-[11px] mt-1.5 font-medium">
              <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
              <span>Las contraseñas no coinciden.</span>
            </p>
          </div>

          <!-- Error general paso 3 -->
          <p v-if="errorPaso3" class="flex items-center gap-1.5 text-red-600 text-[11px] mb-3 font-medium">
            <i class="bi bi-exclamation-circle text-[12px] shrink-0"></i>
            <span>{{ errorPaso3 }}</span>
          </p>

          <!-- Botón Cambiar — lavanda cuando incompleto, azul marino cuando válido -->
          <div class="text-center mt-3 mb-5">
            <button
              type="submit"
              id="btn-cambiar-pass"
              :disabled="!puedeCambiarContrasena || loading"
              class="w-[171px] h-[51px] rounded-[18px] border border-white text-white text-sm font-medium transition-all flex items-center justify-center gap-[10px] px-[10px] mx-auto select-none disabled:cursor-not-allowed"
              :class="puedeCambiarContrasena
                ? 'bg-[#010c67] hover:bg-[#01094f] active:scale-[0.98] shadow-md cursor-pointer'
                : 'bg-[#888eb8] shadow-sm'"
              style="font-family: 'Lora', Georgia, serif;"
            >
              <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0"></span>
              <span>{{ loading ? 'Cambiando...' : 'Cambiar' }}</span>
            </button>
          </div>

        </form>
      </div>

      <!-- ========================================== -->
      <!-- PASO 4: ÉXITO CONFIRMADO                   -->
      <!-- ========================================== -->
      <div v-else-if="paso === 4" class="py-4">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="bi bi-check-lg text-3xl"></i>
        </div>
        <h2 class="text-[26px] font-bold text-gray-950 tracking-tight mb-2" style="font-family: 'Lora', Georgia, serif;">
          ¡Contraseña restablecida!
        </h2>
        <p class="text-[12px] text-gray-600 font-sans mb-6">
          Tu contraseña ha sido actualizada exitosamente.<br>Ahora puedes iniciar sesión con tus nuevas credenciales.
        </p>
        <button
          type="button"
          @click="emit('volver-login')"
          class="w-[171px] h-[51px] rounded-[18px] border border-white bg-[#010C67] hover:bg-[#01094f] active:scale-[0.98] text-white text-sm font-medium transition-all shadow-md mx-auto flex items-center justify-center cursor-pointer"
          style="font-family: 'Lora', Georgia, serif;"
        >
          Iniciar sesión
        </button>
      </div>

    </div>
  </div>
</template>
