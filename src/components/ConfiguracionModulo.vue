<script setup>
import ProximamenteModulo from './ProximamenteModulo.vue'

const emit = defineEmits(['volver'])
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Configuración de Cuenta</h1>
    </div>
    <ProximamenteModulo 
      titulo-modulo="Configuración"
      mensaje="Módulo disponible próximamente"
      descripcion="Estamos trabajando para habilitar las opciones de configuración de cuenta y preferencias de notificación."
      @volver="emit('volver')"
    />
  </div>
</template>
