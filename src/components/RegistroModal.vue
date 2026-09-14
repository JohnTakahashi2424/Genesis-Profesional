<script setup>
import { ref, computed, watch } from 'vue'
import authService from '../services/authService'
import { 
  BaseInput, 
  BaseButton, 
  PasswordStrengthMeter, 
  StatusModal, 
  TermsModal 
} from './common'

const emit = defineEmits(['close', 'abrir-login', 'registro-exitoso'])

// Estado del flujo
// 1 = Datos iniciales (Nombres, Apellidos, Correo)
// 2 = Contraseña y confirmación
// 'validando' = Modal de carga "Datos enviados correctamente" (Screenshot 3)
// 'exitoso' = Modal de éxito "Creación de cuenta exitosa" (Screenshot 4)
const estadoFlujo = ref(1)
const loading = ref(false)
const errorGeneral = ref('')
const mostrarModalTerminos = ref(false)
const passwordEsValida = ref(false)

// Campos del formulario
const form = ref({
  nombres: '',
  apellidos: '',
  correo: '',
  contrasena: '',
  confirmarContrasena: '',
  aceptaTerminos: false
})

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

// Validación de coincidencia de contraseña
const contrasenasCoinciden = computed(() => {
  return form.value.contrasena && form.value.contrasena === form.value.confirmarContrasena
})

// Habilitación del botón "Siguiente" en Paso 2
const puedeEnviarPaso2 = computed(() => {
  return (
    passwordEsValida.value &&
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

// Validar correo en tiempo real contra la base de datos (debounced 400ms)
let debounceTimerCorreo = null
watch(() => form.value.correo, (nuevoCorreo) => {
  if (debounceTimerCorreo) clearTimeout(debounceTimerCorreo)

  // Si los campos previos no están válidos, no mostrar errores en correo
  if (!validarNombre(false) || !validarApellido(false)) {
    errores.value.correo = ''
    return
  }

  const correoLimpio = (nuevoCorreo || '').trim().toLowerCase()
  if (!correoLimpio) {
    if (tocados.value.correo) errores.value.correo = 'Este campo es requerido'
    return
  }

  const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!regexEmail.test(correoLimpio)) {
    if (tocados.value.correo) errores.value.correo = 'Ingrese un correo electrónico válido'
    return
  }

  if (!correoLimpio.endsWith('@ugb.edu.sv')) {
    if (tocados.value.correo) errores.value.correo = 'El correo debe pertenecer al dominio institucional (@ugb.edu.sv)'
    return
  }

  errores.value.correo = ''

  debounceTimerCorreo = setTimeout(async () => {
    try {
      const res = await authService.verificarCorreo(correoLimpio)
      if (res && res.disponible) {
        errores.value.correo = ''
      } else {
        errores.value.correo = res?.mensaje || 'No fue posible procesar el correo institucional'
      }
    } catch (err) {
      if (err.response && err.response.data) {
        const datos = err.response.data
        errores.value.correo = datos.mensaje || datos.errores?.correo?.[0] || 'Correo institucional no válido o no disponible'
      }
    }
  }, 400)
})

// --- VALIDACIONES PASO 1 INDIVIDUALES Y SECUENCIALES ---
const validarNombre = (mostrarError = true) => {
  const val = form.value.nombres.trim()
  if (!val) {
    if (mostrarError) {
      tocados.value.nombres = true
      errores.value.nombres = 'Este campo es requerido'
    }
    return false
  }
  if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]{2,50}$/u.test(val)) {
    if (mostrarError) {
      tocados.value.nombres = true
      errores.value.nombres = 'Ingrese un nombre válido (solo letras, mín. 2 caracteres)'
    }
    return false
  }
  errores.value.nombres = ''
  return true
}

const validarApellido = (mostrarError = true) => {
  // Si Nombres no es válido, Apellidos no debe mostrar error jamás
  if (!validarNombre(false)) {
    errores.value.apellidos = ''
    return false
  }

  const val = form.value.apellidos.trim()
  if (!val) {
    if (mostrarError) {
      tocados.value.apellidos = true
      errores.value.apellidos = 'Este campo es requerido'
    }
    return false
  }
  if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]{2,50}$/u.test(val)) {
    if (mostrarError) {
      tocados.value.apellidos = true
      errores.value.apellidos = 'Ingrese un apellido válido (solo letras, mín. 2 caracteres)'
    }
    return false
  }
  errores.value.apellidos = ''
  return true
}

const validarCorreoSintaxis = (mostrarError = true) => {
  // Si Nombres o Apellidos no son válidos, Correo no debe mostrar error jamás
  if (!validarNombre(false) || !validarApellido(false)) {
    errores.value.correo = ''
    return false
  }

  const correoNormalizado = form.value.correo.trim().toLowerCase()
  if (!correoNormalizado) {
    if (mostrarError) {
      tocados.value.correo = true
      errores.value.correo = 'Este campo es requerido'
    }
    return false
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoNormalizado)) {
    if (mostrarError) {
      tocados.value.correo = true
      errores.value.correo = 'Ingrese un correo electrónico válido'
    }
    return false
  }
  if (!correoNormalizado.endsWith('@ugb.edu.sv')) {
    if (mostrarError) {
      tocados.value.correo = true
      errores.value.correo = 'El correo debe pertenecer al dominio institucional (@ugb.edu.sv)'
    }
    return false
  }
  errores.value.correo = ''
  return true
}

// Navegación con tecla Enter por campo (Muestra error ÚNICAMENTE en el primer campo inválido)
const manejarEnterNombres = () => {
  errores.value.apellidos = ''
  errores.value.correo = ''
  if (!validarNombre(true)) {
    document.getElementById('input-nombres')?.focus()
    return
  }
  document.getElementById('input-apellidos')?.focus()
}

const manejarEnterApellidos = () => {
  errores.value.correo = ''
  if (!validarNombre(true)) {
    document.getElementById('input-nombres')?.focus()
    return
  }
  if (!validarApellido(true)) {
    document.getElementById('input-apellidos')?.focus()
    return
  }
  document.getElementById('input-correo')?.focus()
}

const manejarEnterCorreo = () => {
  if (!validarNombre(true)) {
    document.getElementById('input-nombres')?.focus()
    return
  }
  if (!validarApellido(true)) {
    document.getElementById('input-apellidos')?.focus()
    return
  }
  if (!validarCorreoSintaxis(true)) {
    document.getElementById('input-correo')?.focus()
    return
  }
  continuarAPaso2()
}

// Validación Secuencial del Paso 1 (Solo muestra error en el primer campo incompleto)
const validarPaso1Secuencial = () => {
  if (!validarNombre(true)) {
    errores.value.apellidos = ''
    errores.value.correo = ''
    document.getElementById('input-nombres')?.focus()
    return false
  }
  if (!validarApellido(true)) {
    errores.value.nombres = ''
    errores.value.correo = ''
    document.getElementById('input-apellidos')?.focus()
    return false
  }
  if (!validarCorreoSintaxis(true)) {
    errores.value.nombres = ''
    errores.value.apellidos = ''
    document.getElementById('input-correo')?.focus()
    return false
  }
  return true
}

// Avanzar al Paso 2 con verificación de correo institucional
const continuarAPaso2 = async () => {
  errorGeneral.value = ''

  if (!validarPaso1Secuencial()) return

  loading.value = true
  try {
    const res = await authService.verificarCorreo(form.value.correo.trim().toLowerCase())
    if (res && res.disponible) {
      errores.value.correo = ''
      estadoFlujo.value = 2
    } else {
      errores.value.correo = res?.mensaje || 'No fue posible procesar el correo institucional'
      document.getElementById('input-correo')?.focus()
    }
  } catch (err) {
    if (err.response && err.response.data) {
      const datos = err.response.data
      errores.value.correo = datos.mensaje || datos.errores?.correo?.[0] || 'Correo institucional no válido o no disponible'
    } else {
      errores.value.correo = 'No se pudo conectar con el servidor para verificar el correo'
    }
    document.getElementById('input-correo')?.focus()
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

    estadoFlujo.value = 'exitoso'
    emit('registro-exitoso', respuesta)
  } catch (err) {
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
    <StatusModal 
      v-if="estadoFlujo === 'validando'"
      tipo="validando"
      titulo="Datos enviados correctamente"
      subtitulo="Tus datos están siendo validados, espera un momento"
    />

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- ESTADO: CREACIÓN DE CUENTA EXITOSA (Screenshot 4)            -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <StatusModal 
      v-else-if="estadoFlujo === 'exitoso'"
      tipo="exitoso"
      titulo="Creación de cuenta exitosa"
      subtitulo="Los datos se han validado correctamentre."
      boton-texto="Aceptar"
      @close="cerrarYIrALogin"
      @confirm="cerrarYIrALogin"
    />

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- TARJETA PRINCIPAL DEL FORMULARIO (PASO 1 Y PASO 2)           -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <div 
      v-else
      class="relative w-full max-w-[502px] min-h-[586px] bg-white rounded-[18px] shadow-2xl p-7 sm:p-9 my-auto z-10 transition-all flex flex-col justify-between"
      style="width: 502px; min-height: 586px; border-radius: 18px; background: #FFFFFF;"
    >
      <!-- Botón de regreso (flecha izquierda) -->
      <button 
        type="button" 
        @click="regresarPaso"
        class="absolute top-6 left-6 text-gray-800 hover:text-black p-1.5 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
        title="Volver"
        id="btn-back-registro"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="18" viewBox="0 0 25 18" fill="none">
          <path d="M1 9H24M7.57143 1L1 9L7.57143 17" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <!-- Logo UGB Centrado -->
      <div class="flex justify-center mb-3">
        <img 
          src="/images/logo_ugb.png" 
          alt="Universidad Gerardo Barrios" 
          class="w-[181px] h-[103px] object-contain mx-auto"
          style="width: 181px; height: 103px; aspect-ratio: 181/103;"
        />
      </div>

      <!-- Título y Subtítulo -->
      <div class="text-center mb-5">
        <h2 class="text-[26px] font-bold text-gray-950 font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
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
        <!-- Campo: Nombres (Solo Letras) -->
        <BaseInput 
          id="input-nombres"
          v-model="form.nombres"
          label="Nombres"
          required
          placeholder="Dallana Lucia"
          maxlength="50"
          only-letters
          :error="errores.nombres"
          @blur="validarNombre(true)"
          @enter="manejarEnterNombres"
        />

        <!-- Campo: Apellidos (Solo Letras) -->
        <BaseInput 
          id="input-apellidos"
          v-model="form.apellidos"
          label="Apellidos"
          required
          placeholder="Campos Garcia"
          maxlength="50"
          only-letters
          :error="errores.apellidos"
          @blur="validarApellido(true)"
          @enter="manejarEnterApellidos"
        />

        <!-- Campo: Correo Institucional -->
        <div class="mb-2">
          <BaseInput 
            id="input-correo"
            v-model="form.correo"
            type="email"
            label="Correo institucional"
            required
            placeholder="usss@000ugb.edu.sv"
            maxlength="100"
            :error="errores.correo"
            @blur="validarCorreoSintaxis(true)"
            @enter="manejarEnterCorreo"
          />
        </div>

        <!-- Botón Siguiente (Paso 1) -->
        <div class="text-center pt-2">
          <BaseButton 
            type="submit"
            id="btn-siguiente-paso1"
            :loading="loading"
            loading-text="Verificando..."
          >
            Siguiente
          </BaseButton>
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
        <!-- Campo: Contraseña con show/hide toggle -->
        <div class="mb-1">
          <BaseInput 
            id="input-contrasena"
            v-model="form.contrasena"
            type="password"
            label="Contraseña"
            required
            placeholder="••••••••••"
            maxlength="100"
            :error="errores.contrasena"
            @blur="tocados.contrasena = true"
            @enter="document.getElementById('input-confirmar-contrasena')?.focus()"
          />
        </div>

        <!-- Medidor de 3 barras y checklist de requisitos -->
        <PasswordStrengthMeter 
          :password="form.contrasena"
          @update:is-valid="passwordEsValida = $event"
        />

        <!-- Campo: Confirmar Contraseña -->
        <BaseInput 
          id="input-confirmar-contrasena"
          v-model="form.confirmarContrasena"
          type="password"
          label="Confirmar contraseña"
          required
          placeholder="••••••"
          maxlength="100"
          :error="errores.confirmarContrasena"
          @blur="tocados.confirmarContrasena = true"
          @enter="enviarRegistro"
        />

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
          <BaseButton 
            type="submit"
            id="btn-siguiente-paso2"
            :disabled="!puedeEnviarPaso2"
            :loading="loading"
            loading-text="Registrando..."
          >
            Siguiente
          </BaseButton>
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

    <!-- Modal de Términos y Condiciones Reutilizable -->
    <TermsModal 
      :is-open="mostrarModalTerminos"
      @close="mostrarModalTerminos = false"
      @accept="form.aceptaTerminos = true; mostrarModalTerminos = false"
    />

  </div>
</template>
