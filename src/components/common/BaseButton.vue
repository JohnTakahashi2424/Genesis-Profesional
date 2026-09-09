<script setup>
defineProps({
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Cargando...'
  },
  variant: {
    type: String,
    default: 'primary' // 'primary', 'outline-white', 'secondary'
  },
  block: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])
</script>

<template>
  <button 
    :type="type"
    :disabled="disabled || loading"
    @click="emit('click', $event)"
    class="font-medium text-sm transition-all rounded-full py-2.5 px-6 flex items-center justify-center gap-2 select-none shadow-md"
    :class="[
      block ? 'w-full' : 'w-44 mx-auto',
      variant === 'primary' 
        ? (!disabled && !loading 
            ? 'bg-[#0a1854] hover:bg-[#07113d] text-white cursor-pointer active:scale-95' 
            : 'bg-[#888eb8] text-white/90 cursor-not-allowed shadow-none')
        : '',
      variant === 'outline-white'
        ? 'border border-white text-white hover:bg-white/15 cursor-pointer active:scale-95'
        : ''
    ]"
  >
    <!-- Spinner de carga -->
    <span 
      v-if="loading" 
      class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin shrink-0"
    ></span>

    <!-- Texto o Slot -->
    <span>
      <template v-if="loading">{{ loadingText }}</template>
      <slot v-else></slot>
    </span>
  </button>
</template>
