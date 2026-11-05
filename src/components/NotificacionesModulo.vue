<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'navegar', 'actualizar-contador'])

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

// Estado de carga y listado en tiempo real desde la BD
const notificaciones = ref([])
const cargando = ref(false)
const eliminandoId = ref(null)

// Helper para formatear fecha idéntica a la maqueta (Ej: 28 de feb. 2026 - 01:08:09 p.m.)
const formatearFecha = (fechaRaw, formateadaBack) => {
  if (formateadaBack && typeof formateadaBack === 'string' && formateadaBack.includes(' - ')) {
    return formateadaBack
  }
  if (!fechaRaw) return ''
  try {
    const d = new Date(fechaRaw)
    if (Number.isNaN(d.getTime())) return fechaRaw
    const meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.']
    const dia = d.getDate()
    const mes = meses[d.getMonth()]
    const anio = d.getFullYear()
    let horas = d.getHours()
    const minutos = String(d.getMinutes()).padStart(2, '0')
    const segundos = String(d.getSeconds()).padStart(2, '0')
    const ampm = horas >= 12 ? 'p.m.' : 'a.m.'
    horas = horas % 12
    horas = horas ? horas : 12
    const horasStr = String(horas).padStart(2, '0')
    return `${dia} de ${mes} ${anio} - ${horasStr}:${minutos}:${segundos} ${ampm}`
  } catch (e) {
    return fechaRaw
  }
}

// Cargar notificaciones desde la API de Laravel (Base de datos real)
const cargarNotificaciones = async (silencioso = false) => {
  try {
    if (!silencioso && notificaciones.value.length === 0) {
      cargando.value = true
    }
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
    if (res.data && typeof res.data.no_leidas_count === 'number') {
      emit('actualizar-contador', res.data.no_leidas_count)
    }
  } catch (e) {
    console.error('Error al cargar notificaciones:', e)
    if (!silencioso && notificaciones.value.length === 0) {
      notificaciones.value = []
    }
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

let canalNotifBroadcast = null
let intervaloNotifPolling = null

const handleEventoNotificaciones = () => {
  cargarNotificaciones(true)
}

const handleStorageNotif = (e) => {
  if (e.key === 'genesis_notificaciones_updated' || e.key === 'genesis_historial_updated') {
    cargarNotificaciones(true)
  }
}

onMounted(() => {
  cargarNotificaciones()
  window.addEventListener('click', handleClickAfuera)
  window.addEventListener('notificaciones-actualizadas', handleEventoNotificaciones)
  window.addEventListener('storage', handleStorageNotif)

  if (typeof BroadcastChannel !== 'undefined') {
    try {
      canalNotifBroadcast = new BroadcastChannel('genesis_notificaciones_sync')
      canalNotifBroadcast.onmessage = () => {
        cargarNotificaciones(true)
      }
    } catch (e) {
      console.warn('BroadcastChannel notificaciones no disponible:', e)
    }
  }

  // Comprobación periódica silenciosa en segundo plano (sin parpadeos ni recargas invasivas)
  intervaloNotifPolling = setInterval(() => {
    cargarNotificaciones(true)
  }, 10000)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickAfuera)
  window.removeEventListener('notificaciones-actualizadas', handleEventoNotificaciones)
  window.removeEventListener('storage', handleStorageNotif)
  if (canalNotifBroadcast) {
    canalNotifBroadcast.close()
  }
  if (intervaloNotifPolling) {
    clearInterval(intervaloNotifPolling)
  }
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
      const userId = usuario.value?.id || usuario.value?.user_id || ''
      const config = {
        params: userId ? { user_id: userId } : {}
      }
      if (token) {
        config.headers = { Authorization: `Bearer ${token}`, 'X-User-Id': userId }
      }
      const res = await axios.patch(`/api/notificaciones/${item.id}/leer`, {}, config)
      if (res.data && typeof res.data.no_leidas_count === 'number') {
        emit('actualizar-contador', res.data.no_leidas_count)
      } else {
        emit('actualizar-contador')
      }
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
      config.headers = { Authorization: `Bearer ${token}`, 'X-User-Id': userId }
    }
    const res = await axios.delete(`/api/notificaciones/${id}`, config)
    notificaciones.value = notificaciones.value.filter(n => n.id !== id)
    if (res.data && typeof res.data.no_leidas_count === 'number') {
      emit('actualizar-contador', res.data.no_leidas_count)
    } else {
      emit('actualizar-contador')
    }
  } catch (e) {
    console.error('Error al eliminar la notificación:', e?.message || '')
  } finally {
    eliminandoId.value = null
  }
}

// Navegar al recurso asociado desde el detalle (ej. al reporte)
// Determinar si la notificación seleccionada debe mostrar el botón "ver reporte"
// Notificaciones de bienvenida o generales no tienen reporte asociado y solo muestran el mensaje
const debeMostrarBotonRecurso = computed(() => {
  const notif = notificacionSeleccionada.value
  if (!notif) return false

  const titulo = (notif.titulo || '').toLowerCase()
  const tipo = (notif.tipo || '').toLowerCase()
  const mensaje = (notif.mensaje || '').toLowerCase()

  // Si es de bienvenida o general sin reporte, no mostrar el botón
  if (
    titulo.includes('bienvenid') || 
    tipo === 'general' || 
    tipo === 'bienvenida' || 
    mensaje.includes('bienvenido') || 
    mensaje.includes('cuenta ha sido creada')
  ) {
    return false
  }

  // Solo mostrar si está explícitamente asociada a reportes, observaciones o informe final
  if (
    titulo.includes('reporte') || 
    titulo.includes('informe') || 
    titulo.includes('observaci') ||
    tipo === 'reporte' ||
    tipo === 'observacion' ||
    tipo === 'informe_final' ||
    (notif.enlace && notif.enlace.includes('reporte'))
  ) {
    return true
  }

  return false
})

const irAlRecurso = (enlace) => {
  const notif = notificacionSeleccionada.value
  volverALista()

  const titulo = (notif?.titulo || '').toLowerCase()
  const mensaje = (notif?.mensaje || '').toLowerCase()
  const url = (enlace || notif?.enlace || '').toLowerCase()

  const textoCompleto = `${titulo} ${mensaje} ${url}`
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')

  // Detectar si la notificación es relativa a un Informe Final
  const esInformeFinal = textoCompleto.includes('informe final') ||
    textoCompleto.includes('reporte final') ||
    textoCompleto.includes('informe-final') ||
    textoCompleto.includes('informe_final') ||
    (textoCompleto.includes('final') && (textoCompleto.includes('informe') || textoCompleto.includes('reporte')))

  if (esInformeFinal) {
    let targetId = null
    const matchId = url.match(/informe-final\/(\d+)/) || url.match(/reportes\/(\d+)/) || url.match(/(\d+)/)
    if (matchId && matchId[1]) {
      targetId = matchId[1]
    } else if (textoCompleto.includes('cass') && !textoCompleto.includes('empresas cass')) {
      targetId = '3'
    } else if (textoCompleto.includes('empresas cass')) {
      targetId = '2'
    }

    localStorage.setItem('genesis_ver_informe_final', 'true')
    if (targetId) {
      localStorage.setItem('genesis_ver_informe_final_id', String(targetId))
    }
    emit('navegar', 'reportes', 'informe_final_detalle', targetId)
    return
  }

  const urlFinal = enlace || notif?.enlace || ''
  if (!urlFinal) {
    emit('navegar', 'reportes')
    return
  }

  if (urlFinal.includes('reporte')) {
    const matchId = urlFinal.match(/reportes\/(\d+)/)
    if (matchId && matchId[1]) {
      localStorage.setItem('genesis_ver_reporte_id', matchId[1])
      emit('navegar', 'reportes', 'detalle', matchId[1])
    } else {
      emit('navegar', 'reportes')
    }
  } else if (urlFinal.includes('perfil') || urlFinal.includes('cv')) {
    emit('navegar', 'perfil')
  } else if (urlFinal.includes('historial')) {
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

        <!-- BOTÓN VER REPORTE (Solo visible si la notificación está asociada a un reporte, oculto en bienvenidas) -->
        <div v-if="debeMostrarBotonRecurso" class="mt-5 flex items-center justify-start">
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
    <!-- ========================================================================= -->
    <!-- VISTA 2: LISTADO DE NOTIFICACIONES (DISPOSICIÓN EXACTA A LA IMAGEN 1)     -->
    <!-- ========================================================================= -->
    <div v-else class="space-y-4">
      
      <!-- TÍTULO LIMPIO (Sin ícono de campana al lado, calcado exactamente a la imagen 1) -->
      <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">
        Notificaciones
      </h1>

      <!-- CONTENEDOR PRINCIPAL: TARJETA A LA IZQUIERDA Y FILTROS A LA DERECHA -->
      <div class="flex flex-col lg:flex-row items-start gap-4 xl:gap-6">
        
        <!-- TARJETA PRINCIPAL BLANCA CON LA LISTA DE NOTIFICACIONES -->
        <div class="flex-1 w-full bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden">
          
          <!-- Estado de Carga -->
          <div v-if="cargando" class="flex flex-col items-center justify-center py-24 text-gray-500">
            <svg class="animate-spin h-8 w-8 text-[#000B58] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm">Cargando notificaciones...</span>
          </div>

          <!-- Estado Vacío -->
          <div v-else-if="notificaciones.length === 0" class="text-center py-20 text-gray-500 px-6">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-blue-50 flex items-center justify-center text-[#000B58]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 25 25" fill="none">
                <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" fill="currentColor"/>
              </svg>
            </div>
            <p class="text-base font-semibold text-gray-800">No hay notificaciones para mostrar.</p>
            <p class="text-xs text-gray-500 mt-1">Cuando recibas avisos de tus reportes o pasantías aparecerán aquí.</p>
          </div>

          <!-- LISTA DE FILAS (DISPOSICIÓN EXACTA CON LÍNEAS DIVISORIAS SUTILES) -->
          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="item in notificaciones"
              :key="item.id"
              @click="verDetalleNotificacion(item)"
              class="py-3.5 px-6 hover:bg-gray-50/60 transition-colors cursor-pointer flex items-center justify-between gap-4 text-sm"
              :class="item.leido_at ? 'text-gray-400' : 'text-gray-900'"
            >
              <!-- 1. ICONO + REMITENTE ("Notificación" o nombre de persona) -->
              <div class="w-40 sm:w-44 shrink-0 flex items-center gap-2.5">
                <!-- Icono Campana (Sistema) sin contenedor circular -->
                <svg
                  v-if="item.origen_tipo === 'sistema'"
                  xmlns="http://www.w3.org/2000/svg"
                  width="20"
                  height="20"
                  viewBox="0 0 25 25"
                  fill="none"
                  class="shrink-0"
                  :class="item.leido_at ? 'text-gray-400' : 'text-black'"
                >
                  <path d="M5 10.0001C4.99932 8.4418 5.48404 6.92198 6.38682 5.65182C7.28959 4.38166 8.56559 3.42425 10.0375 2.9126C9.9805 2.55549 10.0017 2.19026 10.0995 1.84212C10.1974 1.49397 10.3696 1.1712 10.6043 0.896075C10.839 0.620946 11.1306 0.40001 11.4589 0.248511C11.7873 0.0970111 12.1446 0.0185547 12.5063 0.0185547C12.8679 0.0185547 13.2252 0.0970111 13.5536 0.248511C13.882 0.40001 14.1735 0.620946 14.4082 0.896075C14.6429 1.1712 14.8152 1.49397 14.913 1.84212C15.0108 2.19026 15.032 2.55549 14.975 2.9126C16.4446 3.42634 17.7179 4.38466 18.6183 5.65464C19.5187 6.92462 20.0016 8.44332 20 10.0001V17.5001L23.75 20.0001V21.2501H1.25V20.0001L5 17.5001V10.0001ZM15 22.5001C15 23.1631 14.7366 23.799 14.2678 24.2679C13.7989 24.7367 13.163 25.0001 12.5 25.0001C11.837 25.0001 11.2011 24.7367 10.7322 24.2679C10.2634 23.799 10 23.1631 10 22.5001H15Z" fill="currentColor"/>
                </svg>

                <!-- Icono Usuario/Avatar estilo circulo (Jenny Flores) -->
                <svg
                  v-else
                  class="w-5 h-5 shrink-0"
                  :class="item.leido_at ? 'text-gray-400' : 'text-gray-500'"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="12" cy="12" r="10" />
                  <circle cx="12" cy="10" r="3.5" />
                  <path d="M6.2 18.5a7 7 0 0 1 11.6 0" />
                </svg>

                <!-- Nombre (Notificación o Jenny Flores) -->
                <span
                  class="text-sm truncate"
                  :class="item.leido_at ? 'font-normal text-gray-400' : 'font-medium text-gray-900'"
                >
                  {{ item.origen_tipo === 'sistema' ? 'Notificación' : (item.remitente_nombre || 'Notificación') }}
                </span>
              </div>

              <!-- 2. TÍTULO DE LA ACCIÓN (Reporte enviado, Observaciones, etc.) -->
              <div class="w-40 sm:w-44 shrink-0">
                <span
                  class="text-sm truncate block"
                  :class="item.leido_at ? 'font-normal text-gray-400' : 'font-semibold text-gray-900'"
                >
                  {{ item.titulo }}
                </span>
              </div>

              <!-- 3. MENSAJE RESUMIDO (Truncado con "...") -->
              <div class="flex-1 min-w-0 pr-4">
                <p
                  class="text-sm truncate"
                  :class="item.leido_at ? 'text-gray-400 font-normal' : 'text-gray-700 font-normal'"
                >
                  {{ item.mensaje }}
                </p>
              </div>

              <!-- 4. FECHA Y HORA (Formato: 28 de feb. 2026 - 01:08:09 p.m., alineada a la izquierda) -->
              <div class="w-56 shrink-0 text-left">
                <span
                  class="text-sm whitespace-nowrap"
                  :class="item.leido_at ? 'text-gray-400 font-normal' : 'text-gray-700 font-normal'"
                >
                  {{ formatearFecha(item.created_at, item.fecha_formateada) }}
                </span>
              </div>

              <!-- 5. ACCIONES (Ojo 👁 + Basurero rojo 🗑) -->
              <div class="flex items-center gap-3 shrink-0 pl-1">
                <!-- Botón OJO (Ver detalle) -->
                <button
                  @click.stop="verDetalleNotificacion(item)"
                  class="p-1 transition rounded-md cursor-pointer"
                  :class="item.leido_at ? 'text-gray-300 hover:text-gray-500' : 'text-gray-600 hover:text-black'"
                  title="Ver notificación"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>

                <!-- Botón BASURERO ROJO (Siempre rojo como en la imagen 1) -->
                <button
                  @click.stop="eliminarNotificacion(item.id)"
                  :disabled="eliminandoId === item.id"
                  class="p-1 hover:opacity-80 transition rounded-md cursor-pointer disabled:opacity-40"
                  title="Eliminar notificación"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 19 21" fill="none">
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

        <!-- COLUMNA DE BOTONES A LA DERECHA (FUERA DE LA TARJETA, APILADOS VERTICALMENTE) -->
        <div class="w-full sm:w-28 md:w-32 shrink-0 flex flex-row sm:flex-col gap-3">
          
          <!-- BOTÓN 1: ESTADO (Todos ▾) -->
          <div id="filtro-estado-container" class="relative w-full">
            <button
              @click.stop="dropdownEstadoAbierto = !dropdownEstadoAbierto; dropdownOrdenAbierto = false"
              class="w-full flex items-center justify-between gap-2 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-sm font-normal text-gray-800 shadow-2xs hover:bg-gray-50 transition cursor-pointer"
            >
              <span class="truncate">{{ filtroEstado }}</span>
              <svg 
                class="w-3.5 h-3.5 text-gray-700 transition-transform duration-200 shrink-0" 
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
                @click="filtroEstado = 'Todos'; dropdownEstadoAbierto = false"
                class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
                :class="filtroEstado === 'Todos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
              >
                <span>Todos</span>
                <span v-if="filtroEstado === 'Todos'" class="text-[#000B58]">✓</span>
              </button>
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
            </div>
          </div>

          <!-- BOTÓN 2: FILTRAR (Filtrar ▾) -->
          <div id="filtro-orden-container" class="relative w-full">
            <button
              @click.stop="dropdownOrdenAbierto = !dropdownOrdenAbierto; dropdownEstadoAbierto = false"
              class="w-full flex items-center justify-between gap-2 px-3.5 py-2 bg-white border border-gray-300 rounded-xl text-sm font-normal text-gray-800 shadow-2xs hover:bg-gray-50 transition cursor-pointer"
            >
              <span>Filtrar</span>
              <svg 
                class="w-3.5 h-3.5 text-gray-700 transition-transform duration-200 shrink-0" 
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
                @click="filtroOrden = 'recientes'; dropdownOrdenAbierto = false"
                class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
                :class="filtroOrden === 'recientes' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
              >
                <span>Recientes primero</span>
                <span v-if="filtroOrden === 'recientes'" class="text-[#000B58]">✓</span>
              </button>
              <button
                @click="filtroOrden = 'antiguos'; dropdownOrdenAbierto = false"
                class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
                :class="filtroOrden === 'antiguos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
              >
                <span>Antiguos primero</span>
                <span v-if="filtroOrden === 'antiguos'" class="text-[#000B58]">✓</span>
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>
  </div>

</template>
