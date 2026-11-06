<script setup>
import { authStorage } from '../utils/storage'
import { logger } from '../utils/logger'

import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'navegar'])

// Datos del usuario autenticado
const usuario = ref(authStorage.getUser())

// Estados de control
const dropdownFiltroAbierto = ref(false)
const indicadoresAbierto = ref(false)

// Criterio de ordenamiento: 'recientes' | 'antiguos'
const ordenSeleccionado = ref('recientes')

// Catálogo de indicadores como guía y acceso directo al módulo correspondiente (NO es filtro)
const catalogoIndicadores = [
  {
    clave: 'cv',
    titulo: 'Actividad de creación / actualización de CV',
    borderClass: 'border-2 border-[#9E2A2B]',
    destino: 'cv'
  },
  {
    clave: 'reportes',
    titulo: 'Actividad de creación/envío y observaciones de reportes',
    borderClass: 'border-2 border-[#1E3A8A]',
    destino: 'reportes'
  },
  {
    clave: 'anexos',
    titulo: 'Actividad de anexos a reportes',
    borderClass: 'border-2 border-[#F59E0B]',
    destino: 'reportes'
  },
  {
    clave: 'reportes_rechazados',
    titulo: 'Actividad de reportes rechazados',
    borderClass: 'border-2 border-[#DC2626]',
    destino: 'reportes'
  },
  {
    clave: 'reportes_aprobados',
    titulo: 'Actividad de reportes aprobados',
    borderClass: 'border-2 border-[#16A34A]',
    destino: 'reportes'
  },
  {
    clave: 'cambio_estado',
    titulo: 'Actividad de cambio de estado',
    borderClass: 'border-2 border-[#0284C7]',
    destino: 'inicio'
  },
  {
    clave: 'perfil',
    titulo: 'Actividad de actualización de perfil',
    borderClass: 'border-2 border-[#9CA3AF]',
    destino: 'perfil'
  }
]

// Listado reactivo de actividades desde la base de datos (tiempo real)
const itemsHistorial = ref([])
const cargando = ref(false)
const nuevosItemsIds = ref(new Set())

let canalBroadcast = null
let intervalPolling = null

// Normalización matemática estricta del progreso según el modelo de pasantías:
// - Cada reporte mensual incrementa un 10% hasta un límite del 70% (7 reportes).
// - El informe final o reporte final autocompleta la barra al 100%.
// - Cada tarjeta histórica conserva el progreso que se tenía en ese momento exacto.
const normalizarProgresos = (lista) => {
  if (!Array.isArray(lista) || lista.length === 0) return []

  // Orden cronológico ascendente (antiguos primero) para calcular la acumulación cronológica
  const listaAsc = [...lista].sort((a, b) => {
    const tA = new Date(a.fecha_exacta_raw || 0).getTime()
    const tB = new Date(b.fecha_exacta_raw || 0).getTime()
    return tA - tB
  })

  const reportesVistos = {}
  let contadorReportes = 0
  let ultimoProgreso = 0

  listaAsc.forEach(item => {
    const textoCompleto = `${item.titulo || ''} ${item.descripcion || ''} ${item.accion_url || ''}`.toLowerCase()

    // 1. Reporte o Informe final -> 100%
    if (textoCompleto.includes('final')) {
      ultimoProgreso = 100
      item.progreso = 100
      return
    }

    // 2. ¿Es reporte mensual?
    const esReporte = ['reportes', 'reportes_aprobados', 'reportes_rechazados'].includes(item.categoria) ||
      textoCompleto.includes('reporte')

    if (esReporte) {
      let claveReporte = null
      const matchMes = textoCompleto.match(/mes de ([a-záéíóúñ]+)/i)
      const matchId = (item.accion_url || '').match(/reportes\/(\d+)/i)

      if (matchMes) {
        claveReporte = 'mes_' + matchMes[1].trim().toLowerCase()
      } else if (matchId) {
        claveReporte = 'id_' + matchId[1]
      } else {
        claveReporte = 'fecha_' + (item.fecha_agrupada || item.id)
      }

      if (!reportesVistos[claveReporte]) {
        contadorReportes++
        const prog = Math.min(70, Math.max(10, contadorReportes * 10))
        reportesVistos[claveReporte] = prog
      }

      item.progreso = reportesVistos[claveReporte]
      ultimoProgreso = item.progreso
    } else {
      // Actividades que no son reportes (CV, perfil, fotografía, etc.)
      item.progreso = ultimoProgreso > 0 ? ultimoProgreso : (Number(item.progreso) || 0)
    }
  })

  return listaAsc
}

// Cargar actividades desde la base de datos asegurando que no se omita ningún elemento del diseño
// Soporta actualización silenciosa sin spinner molesto para tiempo real entre ventanas
const cargarHistorialDesdeApi = async (silencioso = false) => {
  try {
    if (!silencioso && itemsHistorial.value.length === 0) {
      cargando.value = true
    }
    const userId = usuario.value?.id || usuario.value?.user_id || usuario.value?.usuario_id
    const token = authStorage.getToken()
    
    const config = {}
    if (token) {
      config.headers = { Authorization: `Bearer ${token}` }
    }
    if (userId) {
      config.params = { user_id: userId }
    }

    const res = await axios.get('/api/historial-progreso', config)
    if (res.data && Array.isArray(res.data.items)) {
      const itemsMapeados = res.data.items.map(item => ({
        id: item.id,
        fecha_agrupada: item.fecha_agrupada || item.fecha,
        fecha_exacta_raw: item.fecha_exacta || item.created_at,
        titulo: item.titulo,
        descripcion: item.descripcion,
        fecha_exacta_formato: item.fecha_hora_formateada || item.fecha_exacta,
        accion_texto: item.accion_texto || 'Ver reporte',
        accion_url: item.accion_url,
        accion_destino: item.accion_url?.includes('perfil') ? 'perfil' : (item.accion_url?.includes('cv') ? 'cv' : 'reportes'),
        progreso: Number(item.progreso) || 0,
        categoria: item.categoria || 'reportes'
      }))

      // Detectar nuevos ítems para destacarlos visualmente en vivo
      if (itemsHistorial.value.length > 0) {
        const idsPrevios = new Set(itemsHistorial.value.map(i => i.id))
        const nuevos = itemsMapeados.filter(i => !idsPrevios.has(i.id))
        if (nuevos.length > 0) {
          nuevos.forEach(i => nuevosItemsIds.value.add(i.id))
          setTimeout(() => {
            nuevos.forEach(i => nuevosItemsIds.value.delete(i.id))
          }, 4500)
        }
      }

      // Aplicar cálculo de progreso progresivo
      itemsHistorial.value = normalizarProgresos(itemsMapeados)
    } else if (!silencioso) {
      itemsHistorial.value = []
    }
  } catch (e) {
    logger.error('Error al cargar historial desde la API:', e)
    if (!silencioso && itemsHistorial.value.length === 0) {
      itemsHistorial.value = []
    }
  } finally {
    cargando.value = false
  }
}

// Cerrar desplegable de filtro al hacer clic afuera
const handleClickAfuera = (e) => {
  if (!e.target.closest('#contenedor-filtro-dropdown')) {
    dropdownFiltroAbierto.value = false
  }
}

// Manejador de evento storage entre ventanas
const manejarStorageSync = (e) => {
  if (e.key === 'genesis_historial_evento' || e.key === 'genesis_historial_updated') {
    cargarHistorialDesdeApi(true)
  }
}

// Manejador al volver a la pestaña/ventana
const manejarFocoVentana = () => {
  cargarHistorialDesdeApi(true)
}

const manejarVisibilidad = () => {
  if (document.visibilityState === 'visible') {
    cargarHistorialDesdeApi(true)
  }
}

onMounted(() => {
  cargarHistorialDesdeApi()
  window.addEventListener('click', handleClickAfuera)

  // 1. Sincronización en vivo mediante BroadcastChannel (comunicación instantánea entre pestañas/ventanas)
  if (typeof BroadcastChannel !== 'undefined') {
    try {
      canalBroadcast = new BroadcastChannel('genesis_historial_sync')
      canalBroadcast.onmessage = (event) => {
        if (event.data?.tipo === 'reporte_creado' || event.data?.tipo === 'informe_final_creado' || event.data?.tipo === 'historial_actualizado') {
          cargarHistorialDesdeApi(true)
        }
      }
    } catch (e) {
      logger.warn('BroadcastChannel no disponible:', e)
    }
  }

  // 2. Sincronización mediante evento de almacenamiento localStorage
  window.addEventListener('storage', manejarStorageSync)

  // 3. Sincronización reactiva al cambiar de ventana o regresar a la pestaña
  window.addEventListener('focus', manejarFocoVentana)
  document.addEventListener('visibilitychange', manejarVisibilidad)

  // 4. Polling periódico silencioso en segundo plano cada 3 segundos
  intervalPolling = setInterval(() => {
    cargarHistorialDesdeApi(true)
  }, 3000)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickAfuera)
  window.removeEventListener('storage', manejarStorageSync)
  window.removeEventListener('focus', manejarFocoVentana)
  document.removeEventListener('visibilitychange', manejarVisibilidad)

  if (canalBroadcast) {
    canalBroadcast.close()
  }
  if (intervalPolling) {
    clearInterval(intervalPolling)
  }
})

// Ordenamiento de actividades: 'recientes' (descendente) o 'antiguos' (ascendente)
const itemsProcesados = computed(() => {
  const list = [...itemsHistorial.value]

  list.sort((a, b) => {
    const timeA = new Date(a.fecha_exacta_raw || 0).getTime()
    const timeB = new Date(b.fecha_exacta_raw || 0).getTime()
    return ordenSeleccionado.value === 'antiguos' ? (timeA - timeB) : (timeB - timeA)
  })

  return list
})

// Agrupamiento dinámico por fecha (Ej: "01 de marzo de 2026", "20 de enero de 2026")
const gruposPorFecha = computed(() => {
  const grupos = {}
  itemsProcesados.value.forEach(item => {
    const fecha = item.fecha_agrupada || 'Otras fechas'
    if (!grupos[fecha]) {
      grupos[fecha] = []
    }
    grupos[fecha].push(item)
  })
  return grupos
})

// Obtener el color exacto del borde según el tipo de actividad
const obtenerBordeTarjeta = (categoria) => {
  switch (categoria) {
    case 'cv':
      return 'border-2 border-[#9E2A2B]'
    case 'reportes':
      return 'border-2 border-[#1E3A8A]'
    case 'anexos':
      return 'border-2 border-[#F59E0B]'
    case 'reportes_rechazados':
      return 'border-2 border-[#DC2626]'
    case 'reportes_aprobados':
      return 'border-2 border-[#16A34A]'
    case 'cambio_estado':
      return 'border-2 border-[#0284C7]'
    case 'perfil':
      return 'border-2 border-[#9CA3AF]'
    default:
      return 'border-2 border-[#1E3A8A]'
  }
}

// Formateador de texto de acción en dos líneas como en la imagen ("Ver" y "reporte" / "perfil")
const formatearAccionPartes = (accionTexto) => {
  if (!accionTexto) return { l1: 'Ver', l2: 'reporte' }
  const partes = accionTexto.trim().split(' ')
  if (partes.length >= 2) {
    return {
      l1: partes[0],
      l2: partes.slice(1).join(' ')
    }
  }
  return { l1: 'Ver', l2: accionTexto }
}

// Formateador de fecha y hora exacta en 2 líneas como en la imagen
const formatearFechaHoraPartes = (item) => {
  if (item.fecha_linea1 && item.fecha_linea2) {
    return { fecha: item.fecha_linea1, hora: item.fecha_linea2 }
  }

  const fechaStr = item.fecha_exacta_formato
  const rawDate = item.fecha_exacta_raw

  if (fechaStr && typeof fechaStr === 'string' && fechaStr.includes(' - ')) {
    const partes = fechaStr.split(' - ')
    return {
      fecha: `${partes[0].trim()} -`,
      hora: partes[1].trim()
    }
  }

  const d = new Date(rawDate || fechaStr)
  if (Number.isNaN(d.getTime())) {
    return { fecha: fechaStr || '', hora: '' }
  }

  const meses = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
  ]
  const dia = String(d.getDate()).padStart(2, '0')
  const mes = meses[d.getMonth()]
  const anio = d.getFullYear()
  const fecha = `${dia} de ${mes} de ${anio} -`

  let horas = d.getHours()
  const minutos = String(d.getMinutes()).padStart(2, '0')
  const segundos = String(d.getSeconds()).padStart(2, '0')
  const ampm = horas >= 12 ? 'p.m.' : 'a.m.'
  horas = horas % 12 || 12
  const hora = `${String(horas).padStart(2, '0')}:${minutos}:${segundos} ${ampm}`

  return { fecha, hora }
}

// Navegación directa hacia el módulo al hacer clic en un indicador (NO es filtro)
const ingresarASeccion = (ind) => {
  if (ind && ind.destino) {
    emit('navegar', ind.destino)
  }
}

// Obtener descripción de la actividad (para informe final siempre muestra exactamente "Se ha enviado el informe final")
const obtenerDescripcionItem = (item) => {
  const txt = `${item?.titulo || ''} ${item?.descripcion || ''} ${item?.accion_texto || ''} ${item?.accion_url || ''}`
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
  if (txt.includes('informe final') || txt.includes('reporte final')) {
    return 'Se ha enviado el informe final'
  }
  return item?.descripcion || ''
}

// Acción de navegación al hacer clic en "Ver reporte", "Ver perfil", etc.
const ejecutarAccion = (item) => {
  const titulo = (item.titulo || '').toLowerCase()
  const descripcion = (item.descripcion || '').toLowerCase()
  const url = (item.accion_url || '').toLowerCase()
  const accion = (item.accion_texto || '').toLowerCase()
  const categoria = (item.categoria || '').toLowerCase()

  const textoCompleto = `${titulo} ${descripcion} ${url} ${accion} ${categoria}`
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')

  // Detectar si la acción corresponde a un Informe Final
  const esInformeFinal = textoCompleto.includes('informe final') ||
    textoCompleto.includes('reporte final') ||
    textoCompleto.includes('informe-final') ||
    textoCompleto.includes('informe_final') ||
    (textoCompleto.includes('final') && (textoCompleto.includes('informe') || textoCompleto.includes('reporte'))) ||
    Number(item.progreso) === 100

  if (esInformeFinal) {
    localStorage.setItem('genesis_ver_informe_final', 'true')
    let matchId = null
    const m = url.match(/informe-final\/(\d+)/) || url.match(/reportes\/(\d+)/) || url.match(/(\d+)/) || descripcion.match(/(\d+)/)
    if (m && m[1]) {
      matchId = m[1]
      localStorage.setItem('genesis_ver_informe_final_id', matchId)
    }
    emit('navegar', 'reportes', 'informe_final_detalle', matchId)
    return
  }

  if (item.accion_url && item.accion_url.includes('reporte')) {
    const match = item.accion_url.match(/reportes\/(\d+)/)
    if (match && match[1]) {
      localStorage.setItem('genesis_ver_reporte_id', match[1])
      emit('navegar', 'reportes', 'detalle', match[1])
    } else {
      emit('navegar', 'reportes')
    }
  } else if (item.accion_url && (item.accion_url.includes('perfil') || item.accion_url.includes('cv'))) {
    emit('navegar', item.accion_destino || 'perfil')
  } else if (item.accion_destino) {
    emit('navegar', item.accion_destino)
  } else {
    emit('navegar', 'reportes')
  }
}

// Seleccionar ordenamiento desde el menú "Filtrar"
const seleccionarOrden = (orden) => {
  ordenSeleccionado.value = orden
  dropdownFiltroAbierto.value = false
}
</script>

<template>
  <div class="space-y-4">

    <!-- CONTENEDOR GENERAL CON ESTRUCTURA IDÉNTICA A LA CAPTURA -->
    <div class="flex flex-col lg:flex-row items-start gap-4 sm:gap-6">

      <!-- LADO IZQUIERDO / PRINCIPAL: ENCABEZADOS Y TARJETA DEL HISTORIAL -->
      <div class="flex-1 min-w-0 w-full">
        
        <!-- ENCABEZADOS DE COLUMNAS SUPERIORES (COLOR AZUL OSCURO Y ALINEACIÓN EXACTA A LA CAPTURA) -->
        <div class="grid grid-cols-12 gap-3 px-8 pb-3 text-sm font-bold text-[#000B58]">
          <div class="col-span-12 md:col-span-5 pl-2">Fecha</div>
          <div class="col-span-6 md:col-span-3 text-left">Día y hora exacta</div>
          <div class="col-span-3 md:col-span-2 text-left">Acción</div>
          <div class="col-span-3 md:col-span-2 text-left">Progreso</div>
        </div>

        <!-- TARJETA CONTENEDORA PRINCIPAL CON BORDE SUAVE REDONDEADO -->
        <div class="bg-white border border-gray-300 rounded-2xl p-6 sm:p-8 shadow-xs min-h-[540px]">
          
          <!-- Estado de carga -->
          <div v-if="cargando" class="flex flex-col items-center justify-center py-20 text-gray-500">
            <svg class="animate-spin h-8 w-8 text-[#000B58] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium">Cargando historial de actividades...</span>
          </div>

          <!-- Estado vacío si el usuario no tiene actividades registradas aún -->
          <div v-else-if="itemsHistorial.length === 0" class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center text-[#000B58] mb-3">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800 mb-1">Sin actividades registradas aún</h3>
            <p class="text-xs text-slate-500 max-w-sm">
              Cada acción que realices en el sistema (actualizar tu perfil, crear tu currículum o enviar reportes) se reflejará automáticamente aquí en tiempo real.
            </p>
          </div>

          <!-- Estado de actividades agrupadas por fecha con línea vertical de tiempo -->
          <div v-else class="space-y-8">
            <div 
              v-for="(items, fecha) in gruposPorFecha" 
              :key="fecha" 
              class="space-y-3.5"
            >
              <!-- TÍTULO DE LA FECHA (Ej: 01 de marzo de 2026, 20 de enero de 2026) -->
              <h2 class="text-sm sm:text-base font-bold text-[#000B58] tracking-tight pl-1">
                {{ fecha }}
              </h2>

              <!-- CONTENEDOR CON LÍNEA VERTICAL A LA IZQUIERDA (TIMELINE TAL COMO EN LA IMAGEN) -->
              <div class="relative pl-5 sm:pl-6">
                <!-- LÍNEA VERTICAL CONTINUA BAJO LA FECHA -->
                <div class="absolute left-1.5 top-0 bottom-0 w-[2px] bg-slate-900 rounded-full"></div>

                <!-- LISTADO DE TARJETAS BAJO ESTA FECHA -->
                <div class="space-y-3">
                  <div
                    v-for="item in items"
                    :key="item.id"
                    class="bg-white rounded-xl px-5 py-3.5 transition-all duration-500 shadow-2xs hover:shadow-xs relative"
                    :class="[
                      obtenerBordeTarjeta(item.categoria),
                      nuevosItemsIds.has(item.id) ? 'ring-2 ring-[#00529C] bg-blue-50/30 shadow-md animate-pulse' : ''
                    ]"
                  >
                    <div class="grid grid-cols-12 gap-3 items-center">
                      
                      <!-- 1. Título y descripción de la actividad -->
                      <div class="col-span-12 md:col-span-5">
                        <div class="flex items-center gap-2">
                          <h3 class="text-sm font-semibold text-slate-800 leading-snug">
                            {{ item.titulo }}
                          </h3>
                          <span 
                            v-if="nuevosItemsIds.has(item.id)" 
                            class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-bold bg-[#000B58] text-white rounded-md tracking-wider uppercase shadow-xs animate-bounce"
                          >
                            Nuevo
                          </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 leading-tight">
                          {{ obtenerDescripcionItem(item) }}
                        </p>
                      </div>

                      <!-- 2. Día y hora exacta (en 2 líneas como en la imagen) -->
                      <div class="col-span-6 md:col-span-3 text-xs text-slate-600 leading-tight">
                        <div>{{ formatearFechaHoraPartes(item).fecha }}</div>
                        <div class="text-slate-600 text-xs mt-0.5">{{ formatearFechaHoraPartes(item).hora }}</div>
                      </div>

                      <!-- 3. Acción (Ver reporte / Ver perfil en dos líneas) -->
                      <div class="col-span-3 md:col-span-2 text-xs">
                        <button
                          @click="ejecutarAccion(item)"
                          class="text-xs text-slate-600 hover:text-[#000B58] transition cursor-pointer text-left leading-tight hover:underline underline-offset-2"
                        >
                          <div>{{ formatearAccionPartes(item.accion_texto).l1 }}</div>
                          <div>{{ formatearAccionPartes(item.accion_texto).l2 }}</div>
                        </button>
                      </div>

                      <!-- 4. Barra de Progreso Cápsula con acumulado progresivo -->
                      <div 
                        class="col-span-3 md:col-span-2 flex items-center justify-start"
                        :title="`Progreso acumulado: ${item.progreso}%`"
                      >
                        <div class="w-28 sm:w-32 h-3.5 bg-white rounded-full border border-gray-300 p-[1px] overflow-hidden shadow-2xs">
                          <div
                            class="h-full bg-[#00529C] rounded-full transition-all duration-700 ease-out"
                            :style="{ width: `${item.progreso > 0 ? Math.max(6, Math.min(100, Number(item.progreso) || 0)) : 0}%` }"
                          ></div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>

      <!-- LADO DERECHO: ACCIONES (FILTRAR E INDICADORES) -->
      <div class="w-full lg:w-48 shrink-0 flex flex-col gap-3 pt-0 lg:pt-1">
        
        <!-- BOTÓN 1: FILTRAR -->
        <div id="contenedor-filtro-dropdown" class="relative w-full">
          <button
            @click.stop="dropdownFiltroAbierto = !dropdownFiltroAbierto"
            class="w-full flex items-center justify-between px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-slate-800 shadow-xs hover:bg-gray-50 transition cursor-pointer h-[38px]"
          >
            <span>Filtrar</span>
            <span class="text-[10px] text-slate-900 font-bold transition-transform" :class="{ 'rotate-180': dropdownFiltroAbierto }">▼</span>
          </button>

          <!-- Menú desplegable de ordenamiento -->
          <div
            v-if="dropdownFiltroAbierto"
            class="absolute right-0 mt-1.5 w-full bg-white border border-gray-200 rounded-xl shadow-lg z-30 py-1.5 overflow-hidden"
          >
            <button
              @click="seleccionarOrden('recientes')"
              class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="ordenSeleccionado === 'recientes' ? 'text-[#000B58] font-bold bg-blue-50/50' : 'text-slate-700'"
            >
              <span>Recientes primero</span>
              <span v-if="ordenSeleccionado === 'recientes'" class="text-[#000B58]">✓</span>
            </button>
            <button
              @click="seleccionarOrden('antiguos')"
              class="w-full text-left px-4 py-2 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
              :class="ordenSeleccionado === 'antiguos' ? 'text-[#000B58] font-bold bg-blue-50/50' : 'text-slate-700'"
            >
              <span>Antiguos primero</span>
              <span v-if="ordenSeleccionado === 'antiguos'" class="text-[#000B58]">✓</span>
            </button>
          </div>
        </div>

        <!-- BOTÓN 2: INDICADORES (COLAPSADO O EXPANDIDO) -->
        <div id="contenedor-indicadores" class="w-full">
          <!-- ESTADO COLAPSADO: BOTÓN IDÉNTICO A LA CAPTURA -->
          <button
            v-if="!indicadoresAbierto"
            @click="indicadoresAbierto = true"
            class="w-full flex items-center justify-between px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-[#000B58] shadow-xs hover:bg-gray-50 transition cursor-pointer h-[38px]"
          >
            <span>Indicadores</span>
            <span class="text-[10px] text-slate-900 font-bold">▼</span>
          </button>

          <!-- ESTADO EXPANDIDO: TARJETA COMPLETA CON CABECERA Y LOS 7 INDICADORES -->
          <div
            v-else
            class="w-full bg-white border border-gray-300 rounded-2xl p-3.5 shadow-xs transition-all"
          >
            <!-- CABECERA EXPANDIDA CON FLECHA ARRIBA -->
            <button
              @click="indicadoresAbierto = false"
              type="button"
              class="w-full flex items-center justify-between px-1 text-sm font-bold text-[#000B58] cursor-pointer select-none pb-2.5"
            >
              <span>Indicadores</span>
              <span class="text-xs text-black font-black">▲</span>
            </button>

            <!-- LISTA DE LOS 7 INDICADORES ORIENTATIVOS (GUÍA VISUAL ESTÁTICA) -->
            <div class="space-y-3">
              <div
                v-for="ind in catalogoIndicadores"
                :key="ind.clave"
                class="w-full text-center px-2 py-2.5 bg-white rounded-xl text-[11px] leading-tight font-medium text-slate-800 select-none block shadow-2xs"
                :class="ind.borderClass"
              >
                {{ ind.titulo }}
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>
