<script setup>
defineProps({
  tipo: {
    type: String,
    default: 'validando' // 'validando' | 'exitoso'
  },
  titulo: {
    type: String,
    required: true
  },
  subtitulo: {
    type: String,
    required: true
  },
  botonTexto: {
    type: String,
    default: 'Aceptar'
  }
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <!-- ESTADO: VALIDANDO DATOS (Azul Marino con Spinner) -->
  <div 
    v-if="tipo === 'validando'"
    class="relative w-full max-w-[490px] bg-[#020b45] text-white rounded-[20px] shadow-2xl p-7 sm:p-8 my-auto z-10 text-left border border-blue-900/40"
  >
    <div class="flex items-center gap-2 mb-2 font-serif text-[20px] text-white font-normal" style="font-family: 'Lora', Georgia, serif;">
      <i class="bi bi-check-circle text-lg shrink-0"></i>
      <span>{{ titulo }}</span>
    </div>
    <p class="text-white/90 text-sm font-sans mb-8">
      {{ subtitulo }}
    </p>
    
    <!-- Spinner circular brillante centrado -->
    <div class="flex justify-center my-4">
      <div class="w-14 h-14 rounded-full border-2 border-white/20 border-t-white animate-spin"></div>
    </div>
  </div>

  <!-- ESTADO: ACCIÓN EXITOSA (Verde Esmeralda con botón Aceptar) -->
  <div 
    v-else-if="tipo === 'exitoso'"
    class="relative w-full max-w-[490px] bg-[#10a34b] text-white rounded-[20px] shadow-2xl p-7 sm:p-8 my-auto z-10 text-left"
  >
    <!-- Botón cerrar (X) en esquina superior derecha -->
    <button 
      type="button" 
      @click="emit('close')"
      class="absolute top-5 right-5 text-white/80 hover:text-white text-lg font-bold cursor-pointer"
      title="Cerrar"
    >
      ✕
    </button>

    <div class="flex items-center gap-2 mb-2 font-serif text-[20px] text-white font-normal" style="font-family: 'Lora', Georgia, serif;">
      <i class="bi bi-check-circle text-lg shrink-0"></i>
      <span>{{ titulo }}</span>
    </div>
    <p class="text-white font-medium text-sm font-sans mb-6">
      {{ subtitulo }}
    </p>

    <!-- Botón Aceptar centrado -->
    <div class="flex justify-center mt-6">
      <button 
        type="button" 
        @click="emit('confirm')"
        class="px-14 py-2 border border-white text-white rounded-full text-sm font-normal hover:bg-white/15 transition-all cursor-pointer shadow-sm active:scale-95"
      >
        {{ botonTexto }}
      </button>
    </div>
  </div>
</template>
