<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Perfil Profesional</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Perfil"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando para habilitar la edición de tu perfil institucional y datos de contacto muy pronto."
      @volver="emit('volver')"
    />
  </div>
</template>
