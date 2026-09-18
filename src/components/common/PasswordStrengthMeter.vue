<script setup>
import { computed, watch } from 'vue'

const props = defineProps({
  password: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:isValid', 'score-change'])

// Criterios individuales
const tieneMinimo8 = computed(() => props.password.length >= 8)
const tieneMayuscula = computed(() => /[A-Z]/.test(props.password))
const tieneMinuscula = computed(() => /[a-z]/.test(props.password))
const tieneNumeroOSimbolo = computed(() => /[0-9@$!%*?&._#\-]/.test(props.password))

// Criterios cumplidos en total (0 a 4)
const puntaje = computed(() => {
  if (!props.password) return 0
  let score = 0
  if (tieneMinimo8.value) score++
  if (tieneMayuscula.value) score++
  if (tieneMinuscula.value) score++
  if (tieneNumeroOSimbolo.value) score++
  return score
})

// Estado de las 3 barras y texto
const estado = computed(() => {
  const score = puntaje.value
  if (!props.password) {
    return {
      texto: 'Fortaleza de la contraseña',
      colorTexto: 'text-gray-600',
      barras: ['gray', 'gray', 'gray']
    }
  }
  if (score <= 1) {
    return {
      texto: 'Débil',
      colorTexto: 'text-[#ef4444]',
      barras: ['red', 'gray', 'gray']
    }
  }
  if (score <= 3) {
    return {
      texto: 'Media',
      colorTexto: 'text-[#f97316]',
      barras: ['orange', 'orange', 'gray']
    }
  }
  return {
    texto: 'Segura',
    colorTexto: 'text-[#0284c7]',
    barras: ['navy', 'navy', 'navy']
  }
})

// Todos los requisitos cumplidos
const esValida = computed(() => {
  return tieneMinimo8.value && tieneMayuscula.value && tieneMinuscula.value && tieneNumeroOSimbolo.value
})

watch(esValida, (val) => {
  emit('update:isValid', val)
}, { immediate: true })

watch(puntaje, (score) => {
  emit('score-change', score)
})
</script>

<template>
  <div class="password-strength-meter mb-3 text-left">
    <!-- Medidor de 3 barras horizontales -->
    <div class="flex gap-2.5 mt-2.5 mb-1.5">
      <!-- Barra 1 -->
      <div 
        class="h-1.5 flex-1 rounded-full transition-colors duration-300"
        :class="{
          'bg-gray-300': estado.barras[0] === 'gray',
          'bg-[#ef4444]': estado.barras[0] === 'red',
          'bg-[#f97316]': estado.barras[0] === 'orange',
          'bg-[#0a1854]': estado.barras[0] === 'navy',
        }"
      ></div>
      <!-- Barra 2 -->
      <div 
        class="h-1.5 flex-1 rounded-full transition-colors duration-300"
        :class="{
          'bg-gray-300': estado.barras[1] === 'gray',
          'bg-[#f97316]': estado.barras[1] === 'orange',
          'bg-[#0a1854]': estado.barras[1] === 'navy',
        }"
      ></div>
      <!-- Barra 3 -->
      <div 
        class="h-1.5 flex-1 rounded-full transition-colors duration-300"
        :class="{
          'bg-gray-300': estado.barras[2] === 'gray',
          'bg-[#0a1854]': estado.barras[2] === 'navy',
        }"
      ></div>
    </div>

    <!-- Etiqueta de fortaleza / estado -->
    <div class="mb-2">
      <span 
        class="text-xs font-medium transition-colors"
        :class="estado.colorTexto"
      >
        {{ estado.texto }}
      </span>
    </div>

    <!-- Checklist de requisitos -->
    <div class="space-y-1.5 mt-2 pt-1 border-t border-gray-100">
      <!-- Mínimo 8 caracteres -->
      <div class="flex items-center gap-2 text-xs text-gray-700">
        <i 
          :class="tieneMinimo8 ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
          class="text-sm transition-colors shrink-0"
        ></i>
        <span>Mínimo 8 caracteres</span>
      </div>
      <!-- Una letra mayúscula -->
      <div class="flex items-center gap-2 text-xs text-gray-700">
        <i 
          :class="tieneMayuscula ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
          class="text-sm transition-colors shrink-0"
        ></i>
        <span>Una letra mayúscula</span>
      </div>
      <!-- Una letra minúscula -->
      <div class="flex items-center gap-2 text-xs text-gray-700">
        <i 
          :class="tieneMinuscula ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
          class="text-sm transition-colors shrink-0"
        ></i>
        <span>Una letra minúscula</span>
      </div>
      <!-- Un número o símbolo -->
      <div class="flex items-center gap-2 text-xs text-gray-700">
        <i 
          :class="tieneNumeroOSimbolo ? 'bi bi-check-circle-fill text-[#16a34a]' : 'bi bi-check-circle text-gray-400'"
          class="text-sm transition-colors shrink-0"
        ></i>
        <span>Un numero o símbolo</span>
      </div>
    </div>
  </div>
</template>
