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
    class="font-medium text-sm transition-all flex items-center justify-center gap-[10px] select-none"
    :class="[
      block ? 'w-full rounded-[18px] h-[51px] px-[10px]' : 'w-[171px] h-[51px] rounded-[18px] px-[10px] mx-auto',
      variant === 'primary'
        ? (!disabled && !loading
            ? 'bg-[#010C67] border border-white text-white cursor-pointer hover:bg-[#01094f] active:scale-95 shadow-md'
            : 'bg-[#888eb8] border border-white/40 text-white/90 cursor-not-allowed shadow-none')
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
