<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Historial de Progreso</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Historial"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando para habilitar la visualización del historial completo de tu pasantía muy pronto."
      @volver="emit('volver')"
    />
  </div>
</template>
