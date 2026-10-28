<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'navegar'])

// Datos del usuario autenticado
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Estados de control: POR DEFECTO AMBOS CERRADOS
const dropdownEstadoAbierto = ref(false)
const dropdownOrdenAbierto = ref(false)

// Filtros seleccionados según la maqueta
const filtroEstado = ref('Todos') // 'Todos' | 'No leídos' | 'Leídos'
const filtroOrden = ref('recientes') // 'recientes' | 'antiguos'

// Notificación seleccionada para modal de detalle
const notificacionSeleccionada = ref(null)
const modalDetalleAbierto = ref(false)

// Estado de carga y listado
const notificaciones = ref([])
const cargando = ref(false)
const eliminandoId = ref(null)

// Cargar notificaciones desde la API de Laravel (Base de datos real)
const cargarNotificaciones = async () => {
  try {
    cargando.value = true
    const userId = usuario.value?.id || usuario.value?.user_id || ''
    const token = localStorage.getItem('genesis_token')
    
    const config = {
      params: {
        orden: filtroOrden.value
      }
    }

    if (userId) {
      config.params.user_id = userId
    }
    
    // Mapear filtro de lectura
    if (filtroEstado.value === 'No leídos') {
      config.params.estado = 'no_leidos'
    } else if (filtroEstado.value === 'Leídos') {
      config.params.estado = 'leidos'
    } else {
      config.params.estado = 'todos'
    }

    if (token) {
      config.headers = { Authorization: `Bearer ${token}` }
    }

    const res = await axios.get('/api/notificaciones', config)
    if (res.data && Array.isArray(res.data.items)) {
      notificaciones.value = res.data.items
    } else {
      notificaciones.value = []
    }
  } catch (e) {
    console.error('Error al consultar notificaciones en la base de datos:', e?.message || '')
    notificaciones.value = []
  } finally {
    cargando.value = false
  }
}

// Escuchar cambios de filtros para recargar
watch([filtroEstado, filtroOrden], () => {
  cargarNotificaciones()
})

// Cerrar desplegables al hacer clic afuera
const handleClickAfuera = (e) => {
  if (!e.target.closest('#filtro-estado-container')) {
    dropdownEstadoAbierto.value = false
  }
  if (!e.target.closest('#filtro-orden-container')) {
    dropdownOrdenAbierto.value = false
  }
}

onMounted(() => {
  cargarNotificaciones()
  window.addEventListener('click', handleClickAfuera)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickAfuera)
})

// Acción del ojo (👁): Ver detalle y marcar como leída
const verDetalleNotificacion = async (item) => {
  notificacionSeleccionada.value = item
  modalDetalleAbierto.value = true

  // Si no estaba leída, marcarla en la base de datos
  if (!item.leido_at) {
    item.leido_at = new Date().toISOString()
    try {
      const token = localStorage.getItem('genesis_token')
      const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {}
      await axios.patch(`/api/notificaciones/${item.id}/leer`, {}, config)
    } catch (e) {
      console.warn('No se pudo marcar como leída en API:', e?.message)
    }
  }
}

// Acción del basurero rojo (🗑): Eliminar notificación
const eliminarNotificacion = async (id) => {
  eliminandoId.value = id
  try {
    const userId = usuario.value?.id || usuario.value?.user_id || ''
    const token = localStorage.getItem('genesis_token')
    const config = {
      params: userId ? { user_id: userId } : {}
    }
    if (token) {
      config.headers = { Authorization: `Bearer ${token}` }
    }
    await axios.delete(`/api/notificaciones/${id}`, config)
    notificaciones.value = notificaciones.value.filter(n => n.id !== id)
  } catch (e) {
    console.error('Error al eliminar la notificación:', e?.message || '')
  } finally {
    eliminandoId.value = null
  }
}

// Navegar al recurso asociado desde el modal (ej. al reporte)
const irAlRecurso = (enlace) => {
  modalDetalleAbierto.value = false
  if (!enlace) return

  if (enlace.includes('reporte')) {
    emit('navegar', 'reportes')
  } else if (enlace.includes('perfil') || enlace.includes('cv')) {
    emit('navegar', 'perfil')
  } else if (enlace.includes('historial')) {
    emit('navegar', 'historial')
  }
}
</script>

<template>
  <div class="space-y-6">
    
    <!-- ENCABEZADO Y FILTROS SUPERIORES (IDÉNTICOS A LA MAQUETA) -->
    <div class="flex items-center justify-between">
      
      <!-- Título de la vista -->
      <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight pl-2">
        Notificaciones
      </h1>

      <!-- CONTENEDOR DE LOS 2 DESPLEGABLES (DERECHA) -->
      <div class="flex items-center gap-3">
        
        <!-- DESPLEGABLE 1: ESTADO DE LECTURA (Todos / No leídos / Leídos) -->
        <div id="filtro-estado-container" class="relative">
          <button
            @click.stop="dropdownEstadoAbierto = !dropdownEstadoAbierto; dropdownOrdenAbierto = false"
            class="flex items-center justify-between gap-4 w-32 md:w-36 px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-800 shadow-xs hover:bg-gray-50 transition cursor-pointer"
          >
            <span class="truncate">{{ filtroEstado }}</span>
            <svg 
              class="w-4 h-4 text-gray-600 transition-transform duration-200 shrink-0" 
              :class="{ 'rotate-180': dropdownEstadoAbierto }" 
              viewBox="0 0 20 20" 
              fill="currentColor"
            >
              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Menú opciones de estado -->
          <div
            v-if="dropdownEstadoAbierto"
            class="absolute right-0 mt-1.5 w-36 bg-white border border-gray-200 rounded-xl shadow-lg z-30 py-1 overflow-hidden"
          >
            <button
              @click="filtroEstado = 'No leídos'; dropdownEstadoAbierto = false"
              class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="filtroEstado === 'No leídos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
            >
              <span>No leídos</span>
              <span v-if="filtroEstado === 'No leídos'" class="text-[#000B58]">✓</span>
            </button>
            <button
              @click="filtroEstado = 'Leídos'; dropdownEstadoAbierto = false"
              class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="filtroEstado === 'Leídos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
            >
              <span>Leídos</span>
              <span v-if="filtroEstado === 'Leídos'" class="text-[#000B58]">✓</span>
            </button>
            <button
              @click="filtroEstado = 'Todos'; dropdownEstadoAbierto = false"
              class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="filtroEstado === 'Todos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
            >
              <span>Todos</span>
              <span v-if="filtroEstado === 'Todos'" class="text-[#000B58]">✓</span>
            </button>
          </div>
        </div>

        <!-- DESPLEGABLE 2: FILTRAR POR ORDEN (Antiguos / Recientes) -->
        <div id="filtro-orden-container" class="relative">
          <button
            @click.stop="dropdownOrdenAbierto = !dropdownOrdenAbierto; dropdownEstadoAbierto = false"
            class="flex items-center justify-between gap-4 w-32 md:w-36 px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-800 shadow-xs hover:bg-gray-50 transition cursor-pointer"
          >
            <span>Filtrar</span>
            <svg 
              class="w-4 h-4 text-gray-600 transition-transform duration-200 shrink-0" 
              :class="{ 'rotate-180': dropdownOrdenAbierto }" 
              viewBox="0 0 20 20" 
              fill="currentColor"
            >
              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Menú opciones de orden -->
          <div
            v-if="dropdownOrdenAbierto"
            class="absolute right-0 mt-1.5 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-30 py-1 overflow-hidden"
          >
            <button
              @click="filtroOrden = 'antiguos'; dropdownOrdenAbierto = false"
              class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="filtroOrden === 'antiguos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
            >
              <span>Antiguos primero</span>
              <span v-if="filtroOrden === 'antiguos'" class="text-[#000B58]">✓</span>
            </button>
            <button
              @click="filtroOrden = 'recientes'; dropdownOrdenAbierto = false"
              class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="filtroOrden === 'recientes' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
            >
              <span>Recientes primero</span>
              <span v-if="filtroOrden === 'recientes'" class="text-[#000B58]">✓</span>
            </button>
          </div>
        </div>

      </div>

    </div>

    <!-- TARJETA PRINCIPAL BLANCA CON LA LISTA DE NOTIFICACIONES (CALCADA A LA CAPTURA) -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8 shadow-xs min-h-[550px]">
      
      <!-- Estado de Carga -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-24 text-gray-500">
        <svg class="animate-spin h-8 w-8 text-[#000B58] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm">Cargando notificaciones...</span>
      </div>

      <!-- Estado Vacío -->
      <div v-else-if="notificaciones.length === 0" class="text-center py-24 text-gray-500">
        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-blue-50 flex items-center justify-center text-[#000B58]">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
        </div>
        <p class="text-base font-semibold text-gray-800">No hay notificaciones para mostrar.</p>
        <p class="text-xs text-gray-500 mt-1">Cuando recibas avisos de tus reportes o pasantías aparecerán aquí.</p>
      </div>

      <!-- LISTA DE FILAS (IDÉNTICA A LA MAQUETA) -->
      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="item in notificaciones"
          :key="item.id"
          class="py-4 px-2 hover:bg-gray-50/70 transition-colors duration-150 rounded-xl flex items-center justify-between gap-4"
        >
          <!-- 1. ICONO + REMITENTE (Notificación o Jenny Flores) -->
          <div class="flex items-center gap-3 w-48 shrink-0">
            <!-- Icono Campana (Sistema) -->
            <div v-if="item.origen_tipo === 'sistema'" class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
            </div>
            
            <!-- Icono Usuario/Avatar (Jenny Flores) -->
            <div v-else class="w-9 h-9 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1E3A8A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>

            <!-- Nombre de quien emite -->
            <span class="text-sm font-medium text-gray-800 truncate">
              {{ item.remitente_nombre || 'Notificaciones' }}
            </span>
          </div>

          <!-- 2. TÍTULO DE LA ACCIÓN (Reporte enviado, Observaciones, etc.) -->
          <div class="w-44 shrink-0">
            <span class="text-sm font-semibold text-gray-900">
              {{ item.titulo }}
            </span>
          </div>

          <!-- 3. MENSAJE RESUMIDO (Truncado con "...") -->
          <div class="flex-1 min-w-0 pr-4">
            <p class="text-xs md:text-sm text-gray-500 truncate font-normal">
              {{ item.mensaje }}
            </p>
          </div>

          <!-- 4. FECHA Y HORA EXACTA (Formato: 28 de mar. 2026 - 01:08:09 p.m.) -->
          <div class="w-56 shrink-0 text-right pr-2">
            <span class="text-xs text-gray-500 whitespace-nowrap">
              {{ item.fecha_formateada || item.created_at }}
            </span>
          </div>

          <!-- 5. ACCIONES (Ojo 👁 + Basurero rojo 🗑) -->
          <div class="flex items-center gap-3 shrink-0 pl-2">
            
            <!-- Botón OJO (Ver detalle completo y marcar leída) -->
            <button
              @click="verDetalleNotificacion(item)"
              class="p-1.5 text-gray-400 hover:text-[#000B58] transition rounded-lg hover:bg-gray-100 cursor-pointer"
              title="Ver notificación"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>

            <!-- Botón BASURERO (Eliminar) -->
            <button
              @click="eliminarNotificacion(item.id)"
              :disabled="eliminandoId === item.id"
              class="p-1.5 text-red-500 hover:text-red-700 transition rounded-lg hover:bg-red-50 cursor-pointer disabled:opacity-40"
              title="Eliminar notificación"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>

          </div>

        </div>
      </div>

    </div>

    <!-- MODAL DE DETALLE DE NOTIFICACIÓN -->
    <div
      v-if="modalDetalleAbierto && notificacionSeleccionada"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs transition-opacity duration-200"
    >
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 space-y-4">
        
        <!-- Cabecera del modal -->
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
              :class="notificacionSeleccionada.origen_tipo === 'usuario' ? 'bg-blue-50 text-[#1E3A8A]' : 'bg-gray-100 text-gray-700'"
            >
              <svg v-if="notificacionSeleccionada.origen_tipo === 'usuario'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
              </svg>
            </div>
            <div>
              <h3 class="text-base font-bold text-gray-900 leading-tight">
                {{ notificacionSeleccionada.titulo }}
              </h3>
              <p class="text-xs text-gray-500">
                De: {{ notificacionSeleccionada.remitente_nombre || 'Notificaciones' }}
              </p>
            </div>
          </div>
          
          <button
            @click="modalDetalleAbierto = false"
            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Fecha -->
        <div class="text-xs text-gray-400 border-b border-gray-100 pb-2">
          {{ notificacionSeleccionada.fecha_formateada || notificacionSeleccionada.created_at }}
        </div>

        <!-- Mensaje Completo -->
        <div class="text-sm text-gray-700 leading-relaxed py-2">
          {{ notificacionSeleccionada.mensaje }}
        </div>

        <!-- Botones de Acción del Modal -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button
            @click="modalDetalleAbierto = false"
            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition cursor-pointer"
          >
            Cerrar
          </button>

          <!-- Botón opcional para ir al reporte o enlace -->
          <button
            v-if="notificacionSeleccionada.enlace"
            @click="irAlRecurso(notificacionSeleccionada.enlace)"
            class="px-4 py-2 text-xs font-semibold text-white bg-[#000B58] hover:bg-[#00083e] rounded-xl transition shadow-xs cursor-pointer"
          >
            Ver reporte asociado
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

