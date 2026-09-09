<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Análisis IA</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Análisis IA"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando en potentes herramientas de Inteligencia Artificial para evaluar tu progreso profesional."
      @volver="emit('volver')"
    />
  </div>
</template>
