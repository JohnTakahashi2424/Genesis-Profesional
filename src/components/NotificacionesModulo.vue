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

// Notificación seleccionada y vista ('lista' | 'detalle')
const notificacionSeleccionada = ref(null)
const vista = ref('lista')

// Notificaciones base de ejemplo para la interfaz (conforme a la captura)
const notificacionesBaseMock = [
  {
    id: 1,
    origen_tipo: 'sistema',
    remitente_nombre: 'Notificación',
    titulo: 'Reporte enviado',
    mensaje: 'El reporte del mes de febrero ha sido creado y enviado correctamente',
    fecha_formateada: '28 de feb. 2026 - 01:08:09 p.m.',
    leido_at: null,
    enlace: '/reportes'
  },
  {
    id: 2,
    origen_tipo: 'sistema',
    remitente_nombre: 'Notificación',
    titulo: 'Reporte enviado',
    mensaje: 'El reporte del mes de enero ha sido creado y enviado correctamente',
    fecha_formateada: '1 de feb. 2026 - 01:08:09 p.m.',
    leido_at: null,
    enlace: '/reportes'
  },
  {
    id: 3,
    origen_tipo: 'usuario',
    remitente_nombre: 'Jenny Flores',
    titulo: 'Observaciones',
    mensaje: 'Agregar 2 o 3 anexos más en el informe para validar la evidencia.',
    fecha_formateada: '1 de feb. 2026 - 08:11:06 a.m.',
    leido_at: null,
    enlace: '/reportes'
  }
]

// Estado de carga y listado
const notificaciones = ref([...notificacionesBaseMock])
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
    if (res.data && Array.isArray(res.data.items) && res.data.items.length > 0) {
      notificaciones.value = res.data.items
    } else if (res.data && Array.isArray(res.data.items)) {
      // Si la BD no tiene registros para este filtro, mantener los base si estamos en 'Todos'
      if (filtroEstado.value === 'Todos') {
        notificaciones.value = [...notificacionesBaseMock]
      } else {
        notificaciones.value = res.data.items
      }
    } else {
      notificaciones.value = [...notificacionesBaseMock]
    }
  } catch (e) {
    notificaciones.value = [...notificacionesBaseMock]
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
  vista.value = 'detalle'
  window.scrollTo({ top: 0, behavior: 'smooth' })

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

const volverALista = () => {
  vista.value = 'lista'
  notificacionSeleccionada.value = null
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

// Navegar al recurso asociado desde el detalle (ej. al reporte)
const irAlRecurso = (enlace) => {
  volverALista()
  if (!enlace) {
    emit('navegar', 'reportes')
    return
  }

  if (enlace.includes('reporte')) {
    const matchId = enlace.match(/reportes\/(\d+)/)
    if (matchId && matchId[1]) {
      localStorage.setItem('genesis_ver_reporte_id', matchId[1])
    }
    emit('navegar', 'reportes')
  } else if (enlace.includes('perfil') || enlace.includes('cv')) {
    emit('navegar', 'perfil')
  } else if (enlace.includes('historial')) {
    emit('navegar', 'historial')
  } else {
    emit('navegar', 'reportes')
  }
}
</script>

<template>
  <div class="space-y-6">

    <!-- ========================================================================= -->
    <!-- VISTA 1: DETALLE DE NOTIFICACIÓN (CALCADA EXACTAMENTE A LA CAPTURA)        -->
    <!-- ========================================================================= -->
    <div v-if="vista === 'detalle' && notificacionSeleccionada" class="space-y-5">
      
      <!-- BOTÓN VOLVER: ← Notificaciones (Exactamente como en la captura) -->
      <div class="flex items-center">
        <button 
          @click="volverALista"
          class="inline-flex items-center gap-2 text-sm font-medium text-slate-800 hover:text-[#000B58] transition cursor-pointer"
        >
          <span class="text-base font-bold">←</span>
          <span>Notificaciones</span>
        </button>
      </div>

      <!-- TARJETA CON BORDE AZUL CLARO (Calcada exactamente a la captura) -->
      <div class="relative bg-white border border-[#38bdf8] rounded-2xl p-6 sm:p-8 shadow-xs max-w-4xl">
        
        <!-- ENCABEZADO: TÍTULO Y BOTÓN X DE CIERRE -->
        <div class="flex items-start justify-between pb-3">
          <h2 class="text-base sm:text-lg font-bold text-slate-800 tracking-tight">
            {{ notificacionSeleccionada.titulo || 'Reporte enviado' }}
          </h2>
          <button
            @click="volverALista"
            class="text-slate-400 hover:text-slate-600 transition p-1 cursor-pointer"
            title="Cerrar"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- FILA DE METADATOS: REMITENTE CON CAMPANA + FECHA CON RELOJ -->
        <div class="flex items-center justify-between py-2 text-xs sm:text-sm text-slate-700 font-medium">
          <!-- Izquierda: Campana + Notificación -->
          <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 25 25" fill="none" class="shrink-0">
              <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" fill="black"/>
            </svg>
            <span>{{ notificacionSeleccionada.remitente_nombre || 'Notificación' }}</span>
          </div>

          <!-- Derecha: Reloj + Fecha -->
          <div class="flex items-center gap-1.5 text-xs text-slate-600 font-normal">
            <svg class="w-4 h-4 text-black shrink-0" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm1 5a1 1 0 10-2 0v5a1 1 0 00.4.8l3 2a1 1 0 101.2-1.6L13 11.6V7z" clip-rule="evenodd" />
            </svg>
            <span>{{ notificacionSeleccionada.fecha_formateada || notificacionSeleccionada.created_at }}</span>
          </div>
        </div>

        <!-- CUADRO DEL MENSAJE (CAJA CON BORDE AZUL CLARO) -->
        <div class="mt-4 rounded-xl border border-blue-200/90 bg-white p-5 min-h-[160px] text-xs sm:text-sm text-slate-800 leading-relaxed font-normal whitespace-pre-line">
          {{ notificacionSeleccionada.mensaje }}
        </div>

        <!-- BOTÓN VER REPORTE (AZUL CON ÍCONO DE OJO EN LA ESQUINA INFERIOR IZQUIERDA) -->
        <div class="mt-5 flex items-center justify-start">
          <button
            @click="irAlRecurso(notificacionSeleccionada.enlace || 'reportes')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#00529C] hover:bg-[#004280] text-white rounded-lg text-sm font-medium shadow-xs transition cursor-pointer"
          >
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>ver reporte</span>
          </button>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 2: LISTADO DE NOTIFICACIONES (TABLA CON FILTROS)                    -->
    <!-- ========================================================================= -->
    <div v-else class="space-y-6">
      
      <!-- ENCABEZADO Y FILTROS SUPERIORES (IDÉNTICOS A LA MAQUETA) -->
      <div class="flex items-center justify-between">
        
        <!-- Título de la vista con icono campana -->
        <div class="flex items-center gap-3 pl-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 25" fill="none" class="shrink-0 text-[#000B58]">
            <g clip-path="url(#clip0_8495_654_title)">
              <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" fill="currentColor"/>
            </g>
            <defs>
              <clipPath id="clip0_8495_654_title">
                <rect width="25" height="25" fill="white"/>
              </clipPath>
            </defs>
          </svg>
          <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">
            Notificaciones
          </h1>
        </div>

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

      <!-- TARJETA PRINCIPAL BLANCA CON LA LISTA DE NOTIFICACIONES -->
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
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 25" fill="none">
              <g clip-path="url(#clip0_8495_654_empty)">
                <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" fill="currentColor"/>
              </g>
              <defs>
                <clipPath id="clip0_8495_654_empty">
                  <rect width="25" height="25" fill="white"/>
                </clipPath>
              </defs>
            </svg>
          </div>
          <p class="text-base font-semibold text-gray-800">No hay notificaciones para mostrar.</p>
          <p class="text-xs text-gray-500 mt-1">Cuando recibas avisos de tus reportes o pasantías aparecerán aquí.</p>
        </div>

        <!-- LISTA DE FILAS (CON ANIMACIÓN DE SOMBRA GRIS EXCLUSIVAMENTE AL PASAR EL CURSOR) -->
        <div v-else class="space-y-0.5">
          <div
            v-for="item in notificaciones"
            :key="item.id"
            @click="verDetalleNotificacion(item)"
            class="group relative py-3.5 px-4 rounded-lg bg-transparent border border-transparent hover:z-10 hover:bg-white hover:border-gray-200 hover:shadow-[0_6px_18px_rgba(0,0,0,0.12),0_2px_6px_rgba(0,0,0,0.06)] transition-all duration-150 cursor-pointer flex items-center justify-between gap-4"
          >
            <!-- Separador sutil (se oculta al pasar el cursor) -->
            <div class="absolute bottom-0 left-3 right-3 h-[1px] bg-gray-100 group-hover:opacity-0 transition-opacity pointer-events-none"></div>

            <!-- 1. ICONO + REMITENTE (Notificación o Jenny Flores) -->
            <div class="flex items-center gap-3 w-48 shrink-0">
              <!-- Icono Campana (Sistema) -->
              <div v-if="item.origen_tipo === 'sistema'" class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 25 25" fill="none">
                  <g clip-path="url(#clip0_8495_654_item)">
                    <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" :fill="item.id === 1 ? '#374151' : (item.leido_at ? '#6B7280' : '#000000')"/>
                  </g>
                  <defs>
                    <clipPath id="clip0_8495_654_item">
                      <rect width="25" height="25" fill="white"/>
                    </clipPath>
                  </defs>
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
                @click.stop="verDetalleNotificacion(item)"
                class="p-1.5 text-gray-400 hover:text-[#000B58] transition rounded-lg hover:bg-gray-100 cursor-pointer"
                title="Ver notificación"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>

              <!-- Botón BASURERO (Eliminar con icono exacto) -->
              <button
                @click.stop="eliminarNotificacion(item.id)"
                :disabled="eliminandoId === item.id"
                class="p-1.5 hover:opacity-80 transition rounded-lg hover:bg-red-50 cursor-pointer disabled:opacity-40"
                title="Eliminar notificación"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="21" viewBox="0 0 19 21" fill="none">
                  <g clip-path="url(#clip0_8495_2356)">
                    <path d="M6.90909 17.0625V7.4375C6.90909 7.3099 6.86861 7.20508 6.78764 7.12305C6.70668 7.04102 6.60322 7 6.47727 7H5.61364C5.48769 7 5.38423 7.04102 5.30327 7.12305C5.2223 7.20508 5.18182 7.3099 5.18182 7.4375V17.0625C5.18182 17.1901 5.2223 17.2949 5.30327 17.377C5.38423 17.459 5.48769 17.5 5.61364 17.5H6.47727C6.60322 17.5 6.70668 17.459 6.78764 17.377C6.86861 17.2949 6.90909 17.1901 6.90909 17.0625ZM10.3636 17.0625V7.4375C10.3636 7.3099 10.3232 7.20508 10.2422 7.12305C10.1612 7.04102 10.0578 7 9.93182 7H9.06818C8.94223 7 8.83878 7.04102 8.75781 7.12305C8.67685 7.20508 8.63636 7.3099 8.63636 7.4375V17.0625C8.63636 17.1901 8.67685 17.2949 8.75781 17.377C8.83878 17.459 8.94223 17.5 9.06818 17.5H9.93182C10.0578 17.5 10.1612 17.459 10.2422 17.377C10.3232 17.2949 10.3636 17.1901 10.3636 17.0625ZM13.8182 17.0625V7.4375C13.8182 7.3099 13.7777 7.20508 13.6967 7.12305C13.6158 7.04102 13.5123 7 13.3864 7H12.5227C12.3968 7 12.2933 7.04102 12.2124 7.12305C12.1314 7.20508 12.0909 7.3099 12.0909 7.4375V17.0625C12.0909 17.1901 12.1314 17.2949 12.2124 17.377C12.2933 17.459 12.3968 17.5 12.5227 17.5H13.3864C13.5123 17.5 13.6158 17.459 13.6967 17.377C13.7777 17.2949 13.8182 17.1901 13.8182 17.0625ZM6.47727 3.5H12.5227L11.875 1.90039C11.812 1.81836 11.7356 1.76823 11.6456 1.75H7.3679C7.27794 1.76823 7.20147 1.81836 7.13849 1.90039L6.47727 3.5ZM19 3.9375V4.8125C19 4.9401 18.9595 5.04492 18.8786 5.12695C18.7976 5.20898 18.6941 5.25 18.5682 5.25H17.2727V18.2109C17.2727 18.9674 17.0613 19.6214 16.6385 20.1729C16.2157 20.7243 15.7074 21 15.1136 21H3.88636C3.29261 21 2.78433 20.7334 2.36151 20.2002C1.93868 19.667 1.72727 19.0221 1.72727 18.2656V5.25H0.431818C0.305871 5.25 0.202415 5.20898 0.121449 5.12695C0.040483 5.04492 0 4.9401 0 4.8125V3.9375C0 3.8099 0.040483 3.70508 0.121449 3.62305C0.202415 3.54102 0.305871 3.5 0.431818 3.5H4.60156L5.54616 1.2168C5.68111 0.879557 5.92401 0.592448 6.27486 0.355469C6.62571 0.11849 6.98106 0 7.34091 0H11.6591C12.0189 0 12.3743 0.11849 12.7251 0.355469C13.076 0.592448 13.3189 0.879557 13.4538 1.2168L14.3984 3.5H18.5682C18.6941 3.5 18.7976 3.54102 18.8786 3.62305C18.9595 3.70508 19 3.8099 19 3.9375Z" style="fill: var(--color-error, rgba(223, 0, 0, 0.88));" fill="#DF0000" fill-opacity="0.88"/>
                  </g>
                  <defs>
                    <clipPath id="clip0_8495_2356">
                      <rect width="19" height="21" fill="white"/>
                    </clipPath>
                  </defs>
                </svg>
              </button>

            </div>

          </div>
        </div>

      </div>

    </div>

  </div>
</template>

