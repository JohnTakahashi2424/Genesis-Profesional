<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  maxlength: {
    type: [Number, String],
    default: 100
  },
  onlyLetters: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'blur', 'focus'])

// Estado local para alternar visibilidad de contraseña
const mostrarPassword = ref(false)

// Bloqueo directo de teclado para solo letras
const bloquearNoLetras = (evento) => {
  if (!props.onlyLetters) return

  // Permitir teclas de navegación y control
  if (['Backspace', 'Tab', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Enter', 'Home', 'End'].includes(evento.key)) {
    return
  }
  // Permitir combinaciones con Ctrl / Cmd
  if (evento.ctrlKey || evento.metaKey || evento.altKey) {
    return
  }
  // Bloquear cualquier carácter que no sea letra o espacio
  if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(evento.key)) {
    evento.preventDefault()
  }
}

// Filtro en evento input
const manejarInput = (evento) => {
  let valor = evento.target.value
  if (props.onlyLetters) {
    valor = valor.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '')
    evento.target.value = valor
  }
  emit('update:modelValue', valor)
}

// Filtro al pegar contenido
const manejarPegado = (evento) => {
  if (!props.onlyLetters) return

  evento.preventDefault()
  const textoPegado = (evento.clipboardData || window.clipboardData).getData('text')
  const textoLimpio = textoPegado.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '')
  const target = evento.target
  const inicio = target.selectionStart || 0
  const fin = target.selectionEnd || 0
  const actual = props.modelValue || ''
  const max = Number(props.maxlength) || 50
  const nuevo = (actual.slice(0, inicio) + textoLimpio + actual.slice(fin)).slice(0, max)
  
  emit('update:modelValue', nuevo)
  target.value = nuevo
  target.setSelectionRange(inicio + textoLimpio.length, inicio + textoLimpio.length)
}
</script>

<template>
  <div class="base-input-group mb-4 text-left">
    <!-- Etiqueta con asterisco opcional -->
    <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-900 mb-1.5">
      {{ label }}<span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input 
        :id="id"
        :type="type === 'password' ? (mostrarPassword ? 'text' : 'password') : type"
        :value="modelValue"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :disabled="disabled"
        @keydown="bloquearNoLetras"
        @input="manejarInput"
        @paste="manejarPegado"
        @blur="emit('blur', $event)"
        @focus="emit('focus', $event)"
        class="w-full px-4 py-2.5 rounded-xl bg-[#eaecee] border border-[#ced4da] text-gray-900 placeholder-gray-400 text-sm focus:bg-white focus:border-[#0a1854] focus:outline-none focus:ring-1 focus:ring-[#0a1854] transition-all disabled:opacity-60 disabled:cursor-not-allowed"
        :class="{
          'pr-11': type === 'password',
          'border-red-400 bg-red-50/30': error
        }"
      />

      <!-- Botón de ojo para tipo password -->
      <button 
        v-if="type === 'password'"
        type="button" 
        @click="mostrarPassword = !mostrarPassword"
        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900 p-1 cursor-pointer"
        tabindex="-1"
      >
        <i :class="mostrarPassword ? 'bi bi-eye' : 'bi bi-eye-slash'" class="text-base"></i>
      </button>
    </div>

    <!-- Mensaje de error reactivo -->
    <p v-if="error" class="flex items-center gap-1.5 text-[#dc2626] text-xs mt-1.5 font-medium">
      <i class="bi bi-exclamation-circle text-[13px]"></i>
      <span>{{ error }}</span>
    </p>
  </div>
</template>
