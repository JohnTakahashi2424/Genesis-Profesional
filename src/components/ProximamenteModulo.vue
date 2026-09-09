<script setup>
defineProps({
  tituloModulo: {
    type: String,
    default: 'Módulo'
  },
  mensaje: {
    type: String,
    default: 'Módulo disponible próximamente'
  },
  descripcion: {
    type: String,
    default: 'Estamos trabajando continuamente para habilitar esta funcionalidad muy pronto.'
  }
})

const emit = defineEmits(['volver'])
</script>

<template>
  <div class="bg-white rounded-[20px] p-12 text-center shadow-sm border border-gray-100 flex flex-col items-center justify-center min-h-[450px] select-none">
    <!-- Icono de Reloj / Próximamente -->
    <div class="w-20 h-20 bg-blue-50 text-[#000B58] rounded-full flex items-center justify-center mb-6 shadow-xs">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>

    <!-- Título Principal -->
    <h2 class="text-2xl font-bold text-gray-900 mb-3 tracking-tight">
      {{ mensaje }}
    </h2>

    <!-- Subtítulo Informativo -->
    <p class="text-gray-500 max-w-md text-sm leading-relaxed mb-6">
      {{ descripcion }}
    </p>

    <!-- Botón Volver al Inicio -->
    <button 
      @click="emit('volver')"
      class="px-6 py-2.5 bg-[#000B58] text-white font-medium text-sm rounded-xl hover:bg-[#000940] transition-colors cursor-pointer flex items-center gap-2 shadow-sm"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      <span>Volver al inicio</span>
    </button>
  </div>
</template>
