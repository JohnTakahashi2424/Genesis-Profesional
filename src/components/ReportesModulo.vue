<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Módulo de Reportes e Informes</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Reportes"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando para habilitar el envío y seguimiento de reportes mensuales e informe final muy pronto."
      @volver="emit('volver')"
    />
  </div>
</template>
