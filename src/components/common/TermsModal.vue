<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'accept'])
</script>

<template>
  <div 
    v-if="isOpen" 
    class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
  >
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative max-h-[85vh] flex flex-col text-left">
      <!-- Cabecera -->
      <div class="flex items-center justify-between pb-3 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 font-serif" style="font-family: 'Lora', Georgia, serif;">
          Términos y Condiciones de Uso
        </h3>
        <button 
          type="button" 
          @click="emit('close')" 
          class="text-gray-500 hover:text-gray-800 text-xl font-bold p-1 cursor-pointer"
        >
          ✕
        </button>
      </div>

      <!-- Contenido scrolleable -->
      <div class="py-4 text-xs text-gray-700 overflow-y-auto space-y-3 leading-relaxed flex-1 pr-1">
        <p class="font-semibold text-gray-900">1. Aceptación del Sistema Académico</p>
        <p>El acceso a la plataforma Génesis Profesional de la Universidad Gerardo Barrios (UGB) está reservado para estudiantes activos, egresados, supervisores y tutores autorizados. El uso de la cuenta institucional es personal e intransferible.</p>
        
        <p class="font-semibold text-gray-900">2. Confidencialidad y Manejo de Credenciales</p>
        <p>El usuario es responsable de mantener la confidencialidad de sus credenciales de acceso y de toda la actividad que se desarrolle en su cuenta. La contraseña debe cumplir con los lineamientos de seguridad establecidos.</p>
        
        <p class="font-semibold text-gray-900">3. Veracidad de la Información</p>
        <p>El usuario declara que los datos suministrados son fidedignos y corresponden a su registro oficial en los padrones universitarios de la UGB.</p>
      </div>

      <!-- Pie con acción de aceptación -->
      <div class="pt-3 border-t border-gray-200 text-right">
        <button 
          type="button" 
          @click="emit('accept')" 
          class="py-2 px-5 rounded-full bg-[#0a1854] text-white text-xs font-semibold hover:bg-[#07113d] cursor-pointer"
        >
          Aceptar y Continuar
        </button>
      </div>
    </div>
  </div>
</template>
