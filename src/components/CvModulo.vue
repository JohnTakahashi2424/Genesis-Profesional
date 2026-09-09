<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Gestión de Currículum (CV)</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Currículum (CV)"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando para habilitar la creación y gestión de currículums en formato PDF muy pronto."
      @volver="emit('volver')"
    />
  </div>
</template>
