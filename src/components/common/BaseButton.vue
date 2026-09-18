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
    class="font-medium text-base transition-all flex items-center justify-center gap-[10px] select-none"
    :class="[
      block ? 'w-full' : 'w-[171px] mx-auto',
      variant === 'primary'
        ? (!disabled && !loading
            ? 'bg-[#010C67] border border-white text-white cursor-pointer hover:bg-[#01094f] active:scale-95 shadow-md'
            : 'bg-[#888eb8] border border-white/40 text-white/90 cursor-not-allowed shadow-none')
        : '',
      variant === 'outline-white'
        ? 'border border-white text-white hover:bg-white/15 cursor-pointer active:scale-95'
        : ''
    ]"
    :style="{
      display: 'flex',
      width: block ? '100%' : '171px',
      height: '51.286px',
      padding: '10px',
      justifyContent: 'center',
      alignItems: 'center',
      gap: '10px',
      borderRadius: '18px',
      fontFamily: `'Lora', Georgia, serif`,
      ...(variant === 'primary' && !disabled && !loading ? { background: '#010C67' } : {})
    }"
  >
    <!-- Texto o Slot -->
    <span>
      <slot></slot>
    </span>
  </button>
</template>
