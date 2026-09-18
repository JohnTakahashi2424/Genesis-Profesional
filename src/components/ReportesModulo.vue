<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver'])

// Estado del usuario activo
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Vistas del módulo: 'tabla', 'formulario', 'actividad_form', 'detalle'
const vistaActual = ref('tabla')

// Lista de reportes y estado de carga
const reportes = ref([])
const cargando = ref(false)
const mensajeFeedback = ref('')
const tipoFeedback = ref('exito') // 'exito' | 'error'

// Funciones helper para calcular fechas actuales dinámicamente
const getFechaActualString = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getFechaInicioMesActualString = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  return `${year}-${month}-01`
}

const getNombreMesActual = () => {
  const now = new Date()
  const mes = now.toLocaleString('es', { month: 'long' })
  return mes.toUpperCase()
}

const getFechaCreacionFormateada = () => {
  const now = new Date()
  const day = String(now.getDate()).padStart(2, '0')
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const year = now.getFullYear()
  return `${day}/${month}/${year}`
}

// Filtros de la tabla principal
const busqueda = ref('')
const estadoFiltro = ref('Todos')
const fechaCreacionFiltro = ref(getFechaActualString())
const dropdownEstadoAbierto = ref(false)

// Estado del Formulario de Reporte (Crear / Editar)
const esEdicion = ref(false)
const reporteIdEditando = ref(null)
const reporteForm = ref({
  nombre_reporte: '',
  fecha_inicio: '',
  fecha_fin: '',
  horas_registradas: 0,
  actividades: [],
  evidencias: []
})

// Validación de formulario
const intentoGuardar = ref(false)
const intentoAgregarActividad = ref(false)

const errorHorasMensaje = computed(() => {
  if (!intentoAgregarActividad.value && !intentoGuardar.value) return ''
  const val = Number(reporteForm.value.horas_registradas)
  if (isNaN(val) || val <= 0) {
    return 'Las horas registradas deben ser mayor a cero'
  }
  return ''
})

const onCambioHoras = () => {
  if (Number(reporteForm.value.horas_registradas) > 0) {
    intentoAgregarActividad.value = false
  }
}

// Estado del Subformulario de Actividad
const actividadIndexEditando = ref(-1)
const intentoGuardarActividad = ref(false)
const actividadForm = ref({
  fecha_actividad: getFechaInicioMesActualString(),
  objetivo: '',
  actividad_realizada: '',
  logros_obtenidos: ''
})

const errorFechaActividad = computed(() => {
  return intentoGuardarActividad.value && !actividadForm.value.fecha_actividad
})

const errorObjetivo = computed(() => {
  return intentoGuardarActividad.value && !actividadForm.value.objetivo?.trim()
})

const errorActividadRealizada = computed(() => {
  return intentoGuardarActividad.value && !actividadForm.value.actividad_realizada?.trim()
})

const errorLogros = computed(() => {
  return intentoGuardarActividad.value && !actividadForm.value.logros_obtenidos?.trim()
})

// Estado de la Vista de Detalle
const reporteDetalle = ref(null)

// ----------------------------------------------------
// Métodos API
// ----------------------------------------------------

const cargarReportes = async () => {
  cargando.value = true
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    if (!userId) {
      reportes.value = []
      cargando.value = false
      return
    }
    const res = await axios.get('/api/reportes', {
      headers: { 'X-User-Id': userId },
      params: {
        user_id: userId,
        search: busqueda.value,
        estado: estadoFiltro.value
      }
    })
    if (res.data && res.data.data) {
      reportes.value = res.data.data
    } else {
      reportes.value = []
    }
  } catch (err) {
    console.error('Error al cargar reportes:', err)
    reportes.value = []
  } finally {
    cargando.value = false
  }
}

const guardarReporteAPI = async () => {
  intentoGuardar.value = true

  if (!reporteForm.value.nombre_reporte?.trim()) {
    return
  }

  const horas = Number(reporteForm.value.horas_registradas)
  if (isNaN(horas) || horas <= 0) {
    return
  }

  cargando.value = true
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const payload = {
      user_id: userId,
      nombre_reporte: reporteForm.value.nombre_reporte,
      fecha_inicio: reporteForm.value.fecha_inicio || getFechaInicioMesActualString(),
      fecha_fin: reporteForm.value.fecha_fin || getFechaActualString(),
      horas_registradas: reporteForm.value.horas_registradas,
      estado: 'En revisión',
      actividades: reporteForm.value.actividades,
      evidencias: reporteForm.value.evidencias
    }

    if (esEdicion.value && reporteIdEditando.value) {
      await axios.put(`/api/reportes/${reporteIdEditando.value}`, payload, {
        headers: { 'X-User-Id': userId }
      })
      mostrarFeedback('Reporte actualizado correctamente.', 'exito')
    } else {
      await axios.post('/api/reportes', payload, {
        headers: { 'X-User-Id': userId }
      })
      mostrarFeedback('Reporte creado y enviado a revisión correctamente.', 'exito')
    }

    await cargarReportes()
    vistaActual.value = 'tabla'
  } catch (err) {
    console.error('Error al guardar reporte:', err)
    mostrarFeedback(err.response?.data?.mensaje || 'Error al guardar el reporte.', 'error')
  } finally {
    cargando.value = false
  }
}

// ----------------------------------------------------
// Navegación y Acciones UI
// ----------------------------------------------------

const abrirNuevoReporte = () => {
  esEdicion.value = false
  reporteIdEditando.value = null
  intentoGuardar.value = false
  intentoAgregarActividad.value = false
  reporteForm.value = {
    nombre_reporte: 'ACTIVIDADES DE ' + getNombreMesActual(),
    fecha_inicio: getFechaInicioMesActualString(),
    fecha_fin: getFechaActualString(),
    horas_registradas: 0,
    actividades: [],
    evidencias: []
  }
  vistaActual.value = 'formulario'
}

const verDetalle = async (item) => {
  try {
    cargando.value = true
    const res = await axios.get(`/api/reportes/${item.id}`)
    if (res.data && res.data.data) {
      reporteDetalle.value = res.data.data
    } else {
      reporteDetalle.value = item
    }
    vistaActual.value = 'detalle'
  } catch (e) {
    reporteDetalle.value = item
    vistaActual.value = 'detalle'
  } finally {
    cargando.value = false
  }
}

// ----------------------------------------------------
// Gestión de Actividades en Formulario
// ----------------------------------------------------

const abrirAgregarActividad = () => {
  const val = Number(reporteForm.value.horas_registradas)
  if (isNaN(val) || val <= 0) {
    intentoAgregarActividad.value = true
    return
  }
  intentoAgregarActividad.value = false
  mensajeFeedback.value = ''
  intentoGuardarActividad.value = false
  actividadIndexEditando.value = -1
  actividadForm.value = {
    fecha_actividad: getFechaInicioMesActualString(),
    objetivo: '',
    actividad_realizada: '',
    logros_obtenidos: ''
  }
  vistaActual.value = 'actividad_form'
}

const editarActividad = (index) => {
  mensajeFeedback.value = ''
  intentoGuardarActividad.value = false
  actividadIndexEditando.value = index
  const act = reporteForm.value.actividades[index]
  actividadForm.value = { ...act }
  vistaActual.value = 'actividad_form'
}

const eliminarActividad = (index) => {
  reporteForm.value.actividades.splice(index, 1)
}

const guardarActividad = () => {
  intentoGuardarActividad.value = true

  if (
    !actividadForm.value.fecha_actividad ||
    !actividadForm.value.objetivo?.trim() ||
    !actividadForm.value.actividad_realizada?.trim() ||
    !actividadForm.value.logros_obtenidos?.trim()
  ) {
    return
  }

  if (actividadIndexEditando.value >= 0) {
    reporteForm.value.actividades[actividadIndexEditando.value] = { ...actividadForm.value }
  } else {
    reporteForm.value.actividades.push({ ...actividadForm.value })
  }

  intentoGuardarActividad.value = false
  vistaActual.value = 'formulario'
}

const cancelarActividad = () => {
  intentoGuardarActividad.value = false
  vistaActual.value = 'formulario'
}

// ----------------------------------------------------
// Gestión de Imágenes / Evidencias
// ----------------------------------------------------

const handleFileUpload = (event) => {
  const files = event.target.files
  if (!files || files.length === 0) return

  const fotosActuales = reporteForm.value.evidencias.length
  if (fotosActuales >= 4) {
    event.target.value = ''
    return
  }

  const espacioDisponible = 4 - fotosActuales
  const cantidadACargar = Math.min(files.length, espacioDisponible)

  for (let i = 0; i < cantidadACargar; i++) {
    const file = files[i]
    const reader = new FileReader()
    reader.onload = (e) => {
      if (reporteForm.value.evidencias.length < 4) {
        reporteForm.value.evidencias.push(e.target.result)
      }
    }
    reader.readAsDataURL(file)
  }

  event.target.value = ''
}

const eliminarEvidencia = (index) => {
  reporteForm.value.evidencias.splice(index, 1)
}

// ----------------------------------------------------
// Helpers y Utilidades
// ----------------------------------------------------

const mostrarFeedback = (mensaje, tipo = 'exito') => {
  mensajeFeedback.value = mensaje
  tipoFeedback.value = tipo
  setTimeout(() => {
    mensajeFeedback.value = ''
  }, 4000)
}

const formatearMes = (fechaStr) => {
  if (!fechaStr) return 'Agosto'
  try {
    const date = new Date(fechaStr)
    const mes = date.toLocaleString('es', { month: 'long' })
    return mes.charAt(0).toUpperCase() + mes.slice(1)
  } catch (e) {
    return 'Agosto'
  }
}

const formatearPeriodo = (inicio, fin) => {
  if (!inicio || !fin) return '01 Agt – 27 agt 2026'
  try {
    const d1 = new Date(inicio)
    const d2 = new Date(fin)
    const p1 = d1.getDate().toString().padStart(2, '0') + ' ' + d1.toLocaleString('es', { month: 'short' })
    const p2 = d2.getDate().toString().padStart(2, '0') + ' ' + d2.toLocaleString('es', { month: 'short' }) + ' ' + d2.getFullYear()
    return `${p1} – ${p2}`
  } catch (e) {
    return `${inicio} – ${fin}`
  }
}

const normalizarTexto = (str) => {
  if (!str && str !== 0) return ''
  return String(str)
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
}

const reportesFiltrados = computed(() => {
  const queryLimpia = normalizarTexto(busqueda.value)
  const tokens = queryLimpia.split(/\s+/).filter(Boolean)

  return reportes.value.filter(item => {
    // 1. Construir un mega-texto con todos los datos y campos del reporte
    const nombre = item.nombre_reporte || ''
    const mesCreacion = item.created_at ? formatearMes(item.created_at) : (item.fecha_inicio ? formatearMes(item.fecha_inicio) : '')
    const periodo = item.periodo || formatearPeriodo(item.fecha_inicio, item.fecha_fin)
    const horas = String(item.horas_registradas || item.horas || 0)
    const estado = item.estado || ''
    const fechaInicioStr = item.fecha_inicio || ''
    const fechaFinStr = item.fecha_fin || ''
    const createdAtStr = item.created_at || ''

    // Concatenar texto de actividades si existen
    const actividadesText = Array.isArray(item.actividades)
      ? item.actividades.map(a => `${a.objetivo || ''} ${a.actividad_realizada || ''} ${a.logros_obtenidos || ''}`).join(' ')
      : ''

    const blobTextoCompleto = normalizarTexto(
      `${nombre} ${mesCreacion} ${periodo} ${horas} ${horas}h ${horas} horas ${estado} ${fechaInicioStr} ${fechaFinStr} ${createdAtStr} ${actividadesText}`
    )

    // Verificar que TODOS los términos ingresados existan en cualquier orden
    if (tokens.length > 0) {
      const coincideTodosLosTokens = tokens.every(token => blobTextoCompleto.includes(token))
      if (!coincideTodosLosTokens) return false
    }

    // 2. Filtro por Dropdown de Estado
    if (estadoFiltro.value && estadoFiltro.value !== 'Todos') {
      if (item.estado !== estadoFiltro.value) return false
    }

    // 3. Filtro por Calendario de Fecha de Creación (reportes anteriores o iguales a la fecha seleccionada)
    if (fechaCreacionFiltro.value) {
      const fechaReporte = item.created_at || item.fecha_inicio
      if (fechaReporte) {
        try {
          let limitDate
          if (fechaCreacionFiltro.value.includes('-')) {
            const [y, m, d] = fechaCreacionFiltro.value.split('-').map(Number)
            limitDate = new Date(y, m - 1, d, 23, 59, 59, 999)
          } else if (fechaCreacionFiltro.value.includes('/')) {
            const [d, m, y] = fechaCreacionFiltro.value.split('/').map(Number)
            limitDate = new Date(y, m - 1, d, 23, 59, 59, 999)
          } else {
            limitDate = new Date(fechaCreacionFiltro.value)
            limitDate.setHours(23, 59, 59, 999)
          }

          const repDate = new Date(fechaReporte)
          if (!isNaN(limitDate.getTime()) && !isNaN(repDate.getTime())) {
            if (repDate > limitDate) return false
          }
        } catch (e) {
          // Ignorar error de parseo de fecha
        }
      }
    }

    return true
  })
})

onMounted(() => {
  // Cargar lista inicial
  cargarReportes()
})
</script>

<template>
  <div class="max-w-6xl mx-auto font-sans antialiased text-gray-800">
    


    <!-- ========================================================================= -->
    <!-- VISTA 1: TABLA PRINCIPAL DE REPORTES (Capturas 1 y 2) -->
    <!-- ========================================================================= -->
    <div v-if="vistaActual === 'tabla'">
      
      <!-- HEADER CON FILTROS Y BOTÓN NUEVO REPORTE -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        
        <!-- BARRA DE BÚSQUEDA -->
        <div class="relative flex-1 min-w-[240px] max-w-md">
          <input 
            v-model="busqueda"
            @input="cargarReportes"
            type="text" 
            placeholder="Buscar..."
            class="w-full pl-5 pr-10 py-2.5 bg-white border border-cyan-700/60 rounded-full text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs placeholder-gray-400 font-normal"
          />
          <svg class="w-5 h-5 absolute right-3.5 top-1/2 -translate-y-1/2 text-cyan-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- CONTROLES DERECHOS: DROPDOWN ESTADO + FECHA CREACIÓN + NUEVO REPORTE -->
        <div class="flex items-center gap-4 flex-wrap">
          
          <!-- DROPDOWN ESTADO -->
          <div class="relative">
            <button 
              @click="dropdownEstadoAbierto = !dropdownEstadoAbierto"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-700 rounded-md text-sm font-medium text-gray-800 hover:bg-gray-50 shadow-xs cursor-pointer min-w-[140px] justify-between"
            >
              <span>Estado: {{ estadoFiltro }}</span>
              <svg class="w-4 h-4 text-gray-700 transition-transform" :class="{ 'rotate-180': dropdownEstadoAbierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- MENÚ DESPLEGABLE CON LAS OPCIONES DE LA MAQUETA (Captura 2) -->
            <div 
              v-if="dropdownEstadoAbierto" 
              class="absolute right-0 mt-1 w-36 bg-white border border-slate-400 rounded-md shadow-lg z-20 overflow-hidden py-1 text-center"
            >
              <button 
                @click="estadoFiltro = 'Enviado'; dropdownEstadoAbierto = false; cargarReportes()"
                class="w-full px-4 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-100 block text-center cursor-pointer"
              >
                Enviado
              </button>
              <button 
                @click="estadoFiltro = 'En revisión'; dropdownEstadoAbierto = false; cargarReportes()"
                class="w-full px-4 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-100 block text-center cursor-pointer"
              >
                En revisión
              </button>
              <button 
                @click="estadoFiltro = 'Aprobado'; dropdownEstadoAbierto = false; cargarReportes()"
                class="w-full px-4 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-100 block text-center cursor-pointer"
              >
                Aprobado
              </button>
              <button 
                @click="estadoFiltro = 'Todos'; dropdownEstadoAbierto = false; cargarReportes()"
                class="w-full px-4 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-100 block text-center cursor-pointer"
              >
                Todos
              </button>
            </div>
          </div>

          <!-- FECHA DE CREACIÓN -->
          <div class="flex items-center gap-2 text-xs text-gray-600">
            <span>Fecha de creación</span>
            <div class="relative flex items-center">
              <input 
                type="date" 
                v-model="fechaCreacionFiltro"
                class="w-32 pl-7 pr-1 py-1.5 bg-white border border-cyan-500 rounded-md text-xs text-cyan-800 font-medium text-center focus:outline-none shadow-xs cursor-pointer"
              />
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 21 24" fill="none" class="absolute left-2 pointer-events-none">
                <g clip-path="url(#clip0_7350_1304_filtro)">
                  <path d="M7.06183 1.11503C7.06183 0.497116 6.56472 0 5.94681 0C5.3289 0 4.83178 0.497116 4.83178 1.11503V2.9734H2.9734C1.33339 2.9734 0 4.30679 0 5.94681V20.8138C0 22.4538 1.33339 23.7872 2.9734 23.7872H17.8404C19.4804 23.7872 20.8138 22.4538 20.8138 20.8138V5.94681C20.8138 4.30679 19.4804 2.9734 17.8404 2.9734H15.982V1.11503C15.982 0.497116 15.4849 0 14.867 0C14.2491 0 13.752 0.497116 13.752 1.11503V2.9734H7.06183V1.11503ZM2.23005 8.92021H5.94681V11.5219H2.23005V8.92021ZM2.23005 13.752H5.94681V16.7254H2.23005V13.752ZM8.17686 13.752H12.637V16.7254H8.17686V13.752ZM14.867 13.752H18.5838V16.7254H14.867V13.752ZM18.5838 11.5219H14.867V8.92021H18.5838V11.5219ZM18.5838 18.9555V20.8138C18.5838 21.2227 18.2493 21.5572 17.8404 21.5572H14.867V18.9555H18.5838ZM12.637 18.9555V21.5572H8.17686V18.9555H12.637ZM5.94681 18.9555V21.5572H2.9734C2.56456 21.5572 2.23005 21.2227 2.23005 20.8138V18.9555H5.94681ZM12.637 11.5219H8.17686V8.92021H12.637V11.5219Z" fill="black"/>
                </g>
                <rect x="0.5" y="0.5" width="19.8138" height="22.7872" stroke="black" stroke-opacity="0.17"/>
                <defs>
                  <clipPath id="clip0_7350_1304_filtro">
                    <rect width="20.8138" height="23.7872" fill="white"/>
                  </clipPath>
                </defs>
              </svg>
            </div>
          </div>

          <!-- BOTÓN + NUEVO REPORTE -->
          <button 
            @click="abrirNuevoReporte"
            class="flex items-center gap-2.5 px-5 py-2 bg-[#000B58] text-white rounded-full text-sm font-medium hover:bg-[#000840] shadow-md transition-all cursor-pointer font-serif tracking-wide"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none" class="w-[25px] h-[24px] shrink-0">
              <path d="M25 12C25 8.8174 23.683 5.76515 21.3388 3.51472C18.9946 1.26428 15.8152 0 12.5 0C9.18479 0 6.00537 1.26428 3.66116 3.51472C1.31696 5.76515 0 8.8174 0 12C0 15.1826 1.31696 18.2348 3.66116 20.4853C6.00537 22.7357 9.18479 24 12.5 24C15.8152 24 18.9946 22.7357 21.3388 20.4853C23.683 18.2348 25 15.1826 25 12Z" fill="white"/>
              <path d="M11.1995 6.7513C11.1995 6.39768 11.3458 6.05854 11.6063 5.80849C11.8667 5.55844 12.22 5.41797 12.5884 5.41797C12.9567 5.41797 13.31 5.55844 13.5704 5.80849C13.8309 6.05854 13.9772 6.39768 13.9772 6.7513V17.418C13.9772 17.7716 13.8309 18.1107 13.5704 18.3608C13.31 18.6108 12.9567 18.7513 12.5884 18.7513C12.22 18.7513 11.8667 18.6108 11.6063 18.3608C11.3458 18.1107 11.1995 17.7716 11.1995 17.418V6.7513Z" fill="#000B58"/>
              <path d="M18.1355 10.7219C18.5038 10.7198 18.858 10.8583 19.12 11.1068C19.3821 11.3554 19.5305 11.6937 19.5327 12.0473C19.5349 12.4009 19.3907 12.7409 19.1318 12.9924C18.8729 13.244 18.5205 13.3865 18.1522 13.3886L7.04105 13.4473C6.67269 13.4494 6.31854 13.311 6.05651 13.0624C5.79448 12.8139 5.64603 12.4756 5.64382 12.1219C5.64161 11.7683 5.78582 11.4283 6.04473 11.1768C6.30363 10.9252 6.65602 10.7827 7.02438 10.7806L18.1355 10.7219Z" fill="#000B58"/>
            </svg>
            <span>Nuevo reporte</span>
          </button>

        </div>
      </div>

      <!-- CONTENEDOR DE LA TABLA DE REPORTES -->
      <div class="bg-gray-50/50 rounded-2xl p-6 shadow-xs border border-gray-100">
        
        <!-- ENCABEZADOS DE COLUMNA -->
        <div class="grid grid-cols-12 gap-4 px-4 py-2 text-xs font-semibold text-gray-400 mb-2">
          <div class="col-span-3 text-left">Nombre del reporte</div>
          <div class="col-span-2 text-center">Fecha de creación</div>
          <div class="col-span-2 text-center">Periodo</div>
          <div class="col-span-2 text-center">Horas registradas</div>
          <div class="col-span-2 text-center">Estado</div>
          <div class="col-span-1 text-right">Acciones</div>
        </div>

        <!-- INDICADOR DE CARGA -->
        <div v-if="cargando" class="py-12 text-center text-gray-500 text-sm">
          Cargando reportes...
        </div>

        <!-- LISTA VACÍA -->
        <div v-else-if="reportesFiltrados.length === 0" class="py-12 text-center bg-white rounded-xl border border-dashed border-gray-300">
          <p class="text-gray-500 text-sm mb-3">No hay reportes registrados aún.</p>
          <button 
            @click="abrirNuevoReporte"
            class="px-5 py-2 bg-[#000B58] text-white text-xs font-medium rounded-full hover:bg-[#000840]"
          >
            Crear primer reporte
          </button>
        </div>

        <!-- FILAS DE REPORTES (Coincide idénticamente con la maquetación) -->
        <div v-else class="space-y-3">
          <div 
            v-for="item in reportesFiltrados" 
            :key="item.id"
            class="grid grid-cols-12 gap-4 items-center bg-white px-6 py-4 rounded-xl border border-gray-100 shadow-2xs hover:shadow-xs transition-shadow"
          >
            <!-- NOMBRE DEL REPORTE CON ÍCONO -->
            <div class="col-span-3 flex items-start gap-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" class="shrink-0 mt-0.5">
                <g clip-path="url(#clip0_7008_852_reporte)">
                  <path d="M14.7782 0H1.64203C0.738912 0 0 0.738912 0 1.64203V15.8729C0 16.776 0.738912 17.515 1.64203 17.515H14.7782C15.6814 17.515 16.4203 16.776 16.4203 15.8729V1.64203C16.4203 0.738912 15.6814 0 14.7782 0ZM14.2309 15.3256H2.18937V2.18937H14.2309V15.3256ZM4.37874 9.85217H12.0415V10.9469H4.37874V9.85217ZM4.37874 12.0415H12.0415V13.1362H4.37874V12.0415ZM5.47343 4.92608C5.4735 4.71038 5.51605 4.4968 5.59867 4.29754C5.68128 4.09828 5.80233 3.91724 5.95491 3.76477C6.10749 3.61229 6.28861 3.49136 6.48792 3.40888C6.68723 3.3264 6.90084 3.28398 7.11655 3.28406C7.33225 3.28413 7.54583 3.32668 7.74509 3.4093C7.94435 3.49191 8.12539 3.61296 8.27786 3.76554C8.43034 3.91812 8.55127 4.09924 8.63375 4.29855C8.63375 4.49786 8.75865 4.71147 8.75858 4.92718C8.75843 5.36282 8.58523 5.78055 8.27709 6.08849C7.96894 6.39643 7.55109 6.56935 7.11545 6.56921C6.67982 6.56906 6.26208 6.39586 5.95414 6.08772C5.6462 5.77957 5.47328 5.36172 5.47343 4.92608ZM8.21014 6.56811H6.02077C5.11765 6.56811 4.37874 7.06072 4.37874 7.6628V8.75748H9.85217V7.6628C9.85217 7.06072 9.11325 6.56811 8.21014 6.56811Z" fill="black"/>
                </g>
                <defs>
                  <clipPath id="clip0_7008_852_reporte">
                    <rect width="17.515" height="17.515" fill="white"/>
                  </clipPath>
                </defs>
              </svg>
              <span class="text-xs font-bold text-black uppercase tracking-wide leading-snug">
                {{ item.nombre_reporte || 'REPORTE' }}
              </span>
            </div>

            <!-- FECHA DE CREACIÓN -->
            <div class="col-span-2 text-center text-xs font-medium text-gray-700">
              {{ formatearMes(item.created_at || item.fecha_inicio) }}
            </div>

            <!-- PERIODO -->
            <div class="col-span-2 text-center text-xs font-medium text-gray-700">
              {{ item.periodo || formatearPeriodo(item.fecha_inicio, item.fecha_fin) }}
            </div>

            <!-- HORAS REGISTRADAS -->
            <div class="col-span-2 text-center text-xs font-medium text-gray-800">
              {{ item.horas_registradas || item.horas || 0 }}
            </div>

            <!-- ESTADO BADGE (Con insignias exactas de la captura) -->
            <div class="col-span-2 flex justify-center">
              <!-- En revisión (Pill #FFC071) -->
              <span 
                v-if="item.estado === 'En revisión' || item.estado === 'en_espera'"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#FFC071] text-gray-900 shadow-xs"
              >
                <svg class="w-3.5 h-3.5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                En revisión
              </span>

              <!-- Aprobado (Pill #FFC071 con check de aprobado) -->
              <span 
                v-else-if="item.estado === 'Aprobado' || item.estado === 'aprobado'"
                class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold bg-[#FFC071] text-gray-900 shadow-xs"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                  <path d="M2.02698 4.86492L4.054 6.89194L8.10806 2.83789" stroke="black" stroke-opacity="0.6" stroke-width="1.57895" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Aprobado
              </span>

              <!-- Enviado (Pill azul) -->
              <span 
                v-else
                class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 shadow-xs"
              >
                Enviado
              </span>
            </div>

            <!-- ACCIONES: VER INFORME -->
            <div class="col-span-1 flex justify-end">
              <button 
                @click="verDetalle(item)"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 hover:text-[#000B58] transition-colors cursor-pointer"
              >
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Ver informe</span>
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 2: FORMULARIO "NUEVO REPORTE" (Captura 3) -->
    <!-- ========================================================================= -->
    <div v-else-if="vistaActual === 'formulario'">
      
      <!-- NAVEGACIÓN BREADCRUMB -->
      <div class="flex items-center gap-2 mb-6">
        <button 
          @click="vistaActual = 'tabla'"
          class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#000B58] cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Reportes/ Nuevo reporte</span>
        </button>
      </div>

      <!-- CAMPOS DEL FORMULARIO PRINCIPAL -->
      <div class="space-y-6 bg-white p-2 rounded-2xl">
        
        <!-- PRIMERA FILA: NOMBRE REPORTE + FECHAS -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
          
          <!-- Nombre del reporte* -->
          <div class="md:col-span-6">
            <label class="block text-sm font-medium text-gray-800 mb-2">
              Nombre del reporte<span class="text-rose-500">*</span>
            </label>
            <input 
              v-model="reporteForm.nombre_reporte"
              type="text" 
              placeholder="ACTIVIDADES DE AGOSTO"
              class="w-full px-4 py-2.5 bg-white border rounded-lg text-sm text-gray-800 uppercase focus:ring-2 focus:ring-[#000B58] focus:border-transparent outline-none"
              :class="intentoGuardar && !reporteForm.nombre_reporte?.trim() ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            />
            <p v-if="intentoGuardar && !reporteForm.nombre_reporte?.trim()" class="text-xs text-rose-500 mt-1.5 font-medium">
              Campo obligatorio
            </p>
          </div>

          <!-- Fecha Inicio* -->
          <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-800 mb-2">
              Fecha Inicio<span class="text-rose-500">*</span>
            </label>
            <div class="relative flex items-center">
              <input 
                v-model="reporteForm.fecha_inicio"
                type="date" 
                class="w-full pl-10 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-[#00589B] font-semibold focus:ring-2 focus:ring-[#000B58] focus:border-transparent outline-none shadow-xs cursor-pointer"
              />
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="20" viewBox="0 0 21 24" fill="none" class="absolute left-3 pointer-events-none">
                <g clip-path="url(#clip0_7350_1304_inicio)">
                  <path d="M7.06183 1.11503C7.06183 0.497116 6.56472 0 5.94681 0C5.3289 0 4.83178 0.497116 4.83178 1.11503V2.9734H2.9734C1.33339 2.9734 0 4.30679 0 5.94681V20.8138C0 22.4538 1.33339 23.7872 2.9734 23.7872H17.8404C19.4804 23.7872 20.8138 22.4538 20.8138 20.8138V5.94681C20.8138 4.30679 19.4804 2.9734 17.8404 2.9734H15.982V1.11503C15.982 0.497116 15.4849 0 14.867 0C14.2491 0 13.752 0.497116 13.752 1.11503V2.9734H7.06183V1.11503ZM2.23005 8.92021H5.94681V11.5219H2.23005V8.92021ZM2.23005 13.752H5.94681V16.7254H2.23005V13.752ZM8.17686 13.752H12.637V16.7254H8.17686V13.752ZM14.867 13.752H18.5838V16.7254H14.867V13.752ZM18.5838 11.5219H14.867V8.92021H18.5838V11.5219ZM18.5838 18.9555V20.8138C18.5838 21.2227 18.2493 21.5572 17.8404 21.5572H14.867V18.9555H18.5838ZM12.637 18.9555V21.5572H8.17686V18.9555H12.637ZM5.94681 18.9555V21.5572H2.9734C2.56456 21.5572 2.23005 21.2227 2.23005 20.8138V18.9555H5.94681ZM12.637 11.5219H8.17686V8.92021H12.637V11.5219Z" fill="black"/>
                </g>
                <rect x="0.5" y="0.5" width="19.8138" height="22.7872" stroke="black" stroke-opacity="0.17"/>
                <defs>
                  <clipPath id="clip0_7350_1304_inicio">
                    <rect width="20.8138" height="23.7872" fill="white"/>
                  </clipPath>
                </defs>
              </svg>
            </div>
          </div>

          <!-- Fecha Finalización* -->
          <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-800 mb-2">
              Fecha Finalización<span class="text-rose-500">*</span>
            </label>
            <div class="relative flex items-center">
              <input 
                v-model="reporteForm.fecha_fin"
                type="date" 
                class="w-full pl-10 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm text-[#00589B] font-semibold focus:ring-2 focus:ring-[#000B58] focus:border-transparent outline-none shadow-xs cursor-pointer"
              />
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="20" viewBox="0 0 21 24" fill="none" class="absolute left-3 pointer-events-none">
                <g clip-path="url(#clip0_7350_1304_fin)">
                  <path d="M7.06183 1.11503C7.06183 0.497116 6.56472 0 5.94681 0C5.3289 0 4.83178 0.497116 4.83178 1.11503V2.9734H2.9734C1.33339 2.9734 0 4.30679 0 5.94681V20.8138C0 22.4538 1.33339 23.7872 2.9734 23.7872H17.8404C19.4804 23.7872 20.8138 22.4538 20.8138 20.8138V5.94681C20.8138 4.30679 19.4804 2.9734 17.8404 2.9734H15.982V1.11503C15.982 0.497116 15.4849 0 14.867 0C14.2491 0 13.752 0.497116 13.752 1.11503V2.9734H7.06183V1.11503ZM2.23005 8.92021H5.94681V11.5219H2.23005V8.92021ZM2.23005 13.752H5.94681V16.7254H2.23005V13.752ZM8.17686 13.752H12.637V16.7254H8.17686V13.752ZM14.867 13.752H18.5838V16.7254H14.867V13.752ZM18.5838 11.5219H14.867V8.92021H18.5838V11.5219ZM18.5838 18.9555V20.8138C18.5838 21.2227 18.2493 21.5572 17.8404 21.5572H14.867V18.9555H18.5838ZM12.637 18.9555V21.5572H8.17686V18.9555H12.637ZM5.94681 18.9555V21.5572H2.9734C2.56456 21.5572 2.23005 21.2227 2.23005 20.8138V18.9555H5.94681ZM12.637 11.5219H8.17686V8.92021H12.637V11.5219Z" fill="black"/>
                </g>
                <rect x="0.5" y="0.5" width="19.8138" height="22.7872" stroke="black" stroke-opacity="0.17"/>
                <defs>
                  <clipPath id="clip0_7350_1304_fin">
                    <rect width="20.8138" height="23.7872" fill="white"/>
                  </clipPath>
                </defs>
              </svg>
            </div>
          </div>

        </div>

        <!-- SEGUNDA FILA: CANTIDAD DE HORAS + BOTÓN AGREGAR ACTIVIDAD -->
        <div>
          <label class="block text-sm font-medium text-gray-800 mb-2">
            Cantidad de horas<span class="text-rose-500">*</span>
          </label>
          <div class="flex items-center gap-6">
            <input 
              v-model.number="reporteForm.horas_registradas"
              @input="onCambioHoras"
              type="number" 
              min="0"
              placeholder="0"
              class="w-32 px-4 py-2.5 bg-white border rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-[#000B58] outline-none"
              :class="errorHorasMensaje ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            />
            
            <button 
              @click="abrirAgregarActividad"
              type="button"
              class="flex items-center gap-2.5 px-5 py-2 bg-[#000B58] text-white rounded-full text-xs font-semibold hover:bg-[#000840] shadow-sm transition-all cursor-pointer"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none" class="w-[22px] h-[22px] shrink-0">
                <path d="M25 12C25 8.8174 23.683 5.76515 21.3388 3.51472C18.9946 1.26428 15.8152 0 12.5 0C9.18479 0 6.00537 1.26428 3.66116 3.51472C1.31696 5.76515 0 8.8174 0 12C0 15.1826 1.31696 18.2348 3.66116 20.4853C6.00537 22.7357 9.18479 24 12.5 24C15.8152 24 18.9946 22.7357 21.3388 20.4853C23.683 18.2348 25 15.1826 25 12Z" fill="#000B58"/>
                <path d="M11.1995 6.7513C11.1995 6.39768 11.3458 6.05854 11.6063 5.80849C11.8667 5.55844 12.22 5.41797 12.5884 5.41797C12.9567 5.41797 13.31 5.55844 13.5704 5.80849C13.8309 6.05854 13.9772 6.39768 13.9772 6.7513V17.418C13.9772 17.7716 13.8309 18.1107 13.5704 18.3608C13.31 18.6108 12.9567 18.7513 12.5884 18.7513C12.22 18.7513 11.8667 18.6108 11.6063 18.3608C11.3458 18.1107 11.1995 17.7716 11.1995 17.418V6.7513Z" fill="white"/>
                <path d="M18.1355 10.7219C18.5038 10.7198 18.858 10.8583 19.12 11.1068C19.3821 11.3554 19.5305 11.6937 19.5327 12.0473C19.5349 12.4009 19.3907 12.7409 19.1318 12.9924C18.8729 13.244 18.5205 13.3865 18.1522 13.3886L7.04105 13.4473C6.67269 13.4494 6.31854 13.311 6.05651 13.0624C5.79448 12.8139 5.64603 12.4756 5.64382 12.1219C5.64161 11.7683 5.78582 11.4283 6.04473 11.1768C6.30363 10.9252 6.65602 10.7827 7.02438 10.7806L18.1355 10.7219Z" fill="white"/>
              </svg>
              <span>Agregar actividad</span>
            </button>
          </div>

          <!-- MENSAJE DE VALIDACIÓN DE HORAS DYNAMIC Y REACTIVO -->
          <p v-if="errorHorasMensaje" class="text-xs text-rose-500 font-medium mt-2 flex items-center gap-1">
            <span>🛈</span>
            <span>{{ errorHorasMensaje }}</span>
          </p>
        </div>

        <!-- SECCIÓN: ACTIVIDADES REGISTRADAS -->
        <div class="pt-4">
          <h3 class="text-sm font-bold text-gray-800 mb-4">Actividades registradas</h3>

          <div v-if="reporteForm.actividades.length === 0" class="py-6 text-center border border-dashed border-gray-300 rounded-xl">
            <p class="text-xs text-gray-500">No has agregado ninguna actividad aún. Presiona "+ Agregar actividad".</p>
          </div>

          <!-- LISTA DE CARDS DE ACTIVIDADES -->
          <div v-else class="space-y-3">
            <div 
              v-for="(act, index) in reporteForm.actividades" 
              :key="index"
              class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl shadow-2xs hover:border-gray-300 transition-all"
            >
              <div class="pr-4">
                <h4 class="text-sm font-bold text-gray-900 leading-snug">
                  {{ act.objetivo }}
                </h4>
                <p class="text-xs text-gray-500 mt-1 line-clamp-1">
                  {{ act.actividad_realizada }}
                </p>
              </div>

              <!-- ACCIONES DE ACTIVIDAD: EDITAR / ELIMINAR -->
              <div class="flex items-center gap-3 shrink-0">
                <button 
                  @click="editarActividad(index)"
                  type="button" 
                  class="text-gray-700 hover:text-[#000B58] cursor-pointer p-1"
                  title="Editar actividad"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button 
                  @click="eliminarActividad(index)"
                  type="button" 
                  class="hover:opacity-80 cursor-pointer p-1"
                  title="Eliminar actividad"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="20" viewBox="0 0 20 22" fill="none">
                    <g clip-path="url(#clip0_7296_1129)">
                      <path d="M7.27273 17.875V7.79167C7.27273 7.65799 7.23011 7.54818 7.14489 7.46224C7.05966 7.3763 6.95076 7.33333 6.81818 7.33333H5.90909C5.77652 7.33333 5.66761 7.3763 5.58239 7.46224C5.49716 7.54818 5.45455 7.65799 5.45455 7.79167V17.875C5.45455 18.0087 5.49716 18.1185 5.58239 18.2044C5.66761 18.2904 5.77652 18.3333 5.90909 18.3333H6.81818C6.95076 18.3333 7.05966 18.2904 7.14489 18.2044C7.23011 18.1185 7.27273 18.0087 7.27273 17.875ZM10.9091 17.875V7.79167C10.9091 7.65799 10.8665 7.54818 10.7812 7.46224C10.696 7.3763 10.5871 7.33333 10.4545 7.33333H9.54545C9.41288 7.33333 9.30398 7.3763 9.21875 7.46224C9.13352 7.54818 9.09091 7.65799 9.09091 7.79167V17.875C9.09091 18.0087 9.13352 18.1185 9.21875 18.2044C9.30398 18.2904 9.41288 18.3333 9.54545 18.3333H10.4545C10.5871 18.3333 10.696 18.2904 10.7812 18.2044C10.8665 18.1185 10.9091 18.0087 10.9091 17.875ZM14.5455 17.875V7.79167C14.5455 7.65799 14.5028 7.54818 14.4176 7.46224C14.3324 7.3763 14.2235 7.33333 14.0909 7.33333H13.1818C13.0492 7.33333 12.9403 7.3763 12.8551 7.46224C12.7699 7.54818 12.7273 7.65799 12.7273 7.79167V17.875C12.7273 18.0087 12.7699 18.1185 12.8551 18.2044C12.9403 18.2904 13.0492 18.3333 13.1818 18.3333H14.0909C14.2235 18.3333 14.3324 18.2904 14.4176 18.2044C14.5028 18.1185 14.5455 18.0087 14.5455 17.875ZM6.81818 3.66667H13.1818L12.5 1.99089C12.4337 1.90495 12.3532 1.85243 12.2585 1.83333H7.75568C7.66098 1.85243 7.58049 1.90495 7.5142 1.99089L6.81818 3.66667ZM20 4.125V5.04167C20 5.17535 19.9574 5.28516 19.8722 5.37109C19.7869 5.45703 19.678 5.5 19.5455 5.5H18.1818V19.0781C18.1818 19.8707 17.9593 20.5558 17.5142 21.1335C17.0691 21.7112 16.5341 22 15.9091 22H4.09091C3.46591 22 2.93087 21.7207 2.4858 21.1621C2.04072 20.6035 1.81818 19.928 1.81818 19.1354V5.5H0.454545C0.32197 5.5 0.213068 5.45703 0.127841 5.37109C0.0426136 5.28516 0 5.17535 0 5.04167V4.125C0 3.99132 0.0426136 3.88151 0.127841 3.79557C0.213068 3.70964 0.32197 3.66667 0.454545 3.66667H4.84375L5.83807 1.27474C5.98011 0.921441 6.2358 0.62066 6.60511 0.372396C6.97443 0.124132 7.34848 0 7.72727 0H12.2727C12.6515 0 13.0256 0.124132 13.3949 0.372396C13.7642 0.62066 14.0199 0.921441 14.1619 1.27474L15.1562 3.66667H19.5455C19.678 3.66667 19.7869 3.70964 19.8722 3.79557C19.9574 3.88151 20 3.99132 20 4.125Z" fill="#DF0000" fill-opacity="0.88"/>
                    </g>
                    <defs>
                      <clipPath id="clip0_7296_1129">
                        <rect width="20" height="22" fill="white"/>
                      </clipPath>
                    </defs>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN: ADJUNTAR EVIDENCIAS / IMÁGENES -->
        <div class="pt-4">
          <div class="flex items-center gap-4 flex-wrap">
            
            <!-- CAJA DE CARGA CON ÍCONO DE IMAGEN -->
            <label 
              v-if="reporteForm.evidencias.length < 4"
              class="w-24 h-24 border-2 border-dashed border-cyan-700/60 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:bg-cyan-50/50 transition-colors shrink-0 bg-white"
            >
              <input type="file" accept="image/*" multiple @change="handleFileUpload" class="hidden" />
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 68 68" fill="none" class="w-12 h-12">
                <path d="M33.9138 18.37C33.9138 16.3287 33.5118 14.3075 32.7306 12.4216C31.9495 10.5358 30.8045 8.82223 29.3611 7.37885C27.9178 5.93547 26.2042 4.79053 24.3184 4.00937C22.4325 3.22822 20.4112 2.82617 18.37 2.82617C16.3287 2.82617 14.3075 3.22822 12.4216 4.00937C10.5358 4.79053 8.82223 5.93547 7.37885 7.37885C5.93547 8.82223 4.79052 10.5358 4.00937 12.4216C3.22822 14.3075 2.82617 16.3287 2.82617 18.37C2.82617 22.4925 4.46382 26.4461 7.37885 29.3611C10.2939 32.2762 14.2475 33.9138 18.37 33.9138C22.4925 33.9138 26.4461 32.2762 29.3611 29.3611C32.2762 26.4461 33.9138 22.4925 33.9138 18.37ZM19.7831 19.7831L19.7859 26.8597C19.7859 27.2345 19.637 27.5939 19.372 27.8589C19.107 28.1239 18.7476 28.2728 18.3728 28.2728C17.998 28.2728 17.6386 28.1239 17.3736 27.8589C17.1086 27.5939 16.9597 27.2345 16.9597 26.8597V19.7831H9.88024C9.50547 19.7831 9.14605 19.6342 8.88105 19.3692C8.61604 19.1042 8.46717 18.7448 8.46717 18.37C8.46717 17.9952 8.61604 17.6358 8.88105 17.3708C9.14605 17.1058 9.50547 16.9569 9.88024 16.9569H16.9569V9.89154C16.9569 9.51677 17.1058 9.15735 17.3708 8.89235C17.6358 8.62735 17.9952 8.47847 18.37 8.47847C18.7448 8.47847 19.1042 8.62735 19.3692 8.89235C19.6342 9.15735 19.7831 9.51677 19.7831 9.89154V16.9569H26.84C27.2147 16.9569 27.5742 17.1058 27.8392 17.3708C28.1042 17.6358 28.253 17.9952 28.253 18.37C28.253 18.7448 28.1042 19.1042 27.8392 19.3692C27.5742 19.6342 27.2147 19.7831 26.84 19.7831H19.7831ZM50.1642 12.7177H35.8554C35.3713 11.2248 34.6979 9.80015 33.8516 8.47847H50.1642C52.6002 8.47847 54.9364 9.44617 56.6589 11.1687C58.3815 12.8912 59.3492 15.2274 59.3492 17.6635V50.1642C59.3492 52.6002 58.3815 54.9364 56.6589 56.6589C54.9364 58.3815 52.6002 59.3492 50.1642 59.3492H17.6635C15.2274 59.3492 12.8912 58.3815 11.1687 56.6589C9.44617 54.9364 8.47847 52.6002 8.47847 50.1642V33.8516C9.78698 34.691 11.2114 35.3665 12.7177 35.8554V50.1642C12.7196 50.7539 12.8166 51.3135 13.0088 51.8429L29.4655 35.731C30.5936 34.6266 32.0922 33.9814 33.6697 33.9208C35.2472 33.8602 36.7909 34.3886 38.0004 35.4032L38.3622 35.731L54.816 51.8457C55.0082 51.3182 55.1062 50.7577 55.1099 50.1642V17.6635C55.1099 16.3518 54.5889 15.0938 53.6614 14.1663C52.7338 13.2388 51.4759 12.7177 50.1642 12.7177ZM51.8033 54.8301L35.3975 38.7607C35.0396 38.4099 34.569 38.1973 34.0692 38.1605C33.5695 38.1238 33.0728 38.2653 32.6675 38.56L32.4301 38.7578L16.0186 54.8301C16.5349 55.0129 17.0832 55.1062 17.6635 55.1099H50.1642C50.7379 55.1099 51.2918 55.011 51.8033 54.8301ZM43.1073 18.37C44.7952 18.37 46.4141 19.0405 47.6076 20.2341C48.8012 21.4277 49.4718 23.0465 49.4718 24.7345C49.4718 26.4224 48.8012 28.0413 47.6076 29.2349C46.4141 30.4284 44.7952 31.099 43.1073 31.099C41.4193 31.099 39.8005 30.4284 38.6069 29.2349C37.4133 28.0413 36.7428 26.4224 36.7428 24.7345C36.7428 23.0465 37.4133 21.4277 38.6069 20.2341C39.8005 19.0405 41.4193 18.37 43.1073 18.37ZM43.1073 22.6092C42.5436 22.6092 42.0031 22.8331 41.6045 23.2317C41.2059 23.6303 40.982 24.1708 40.982 24.7345C40.982 25.2981 41.2059 25.8387 41.6045 26.2373C42.0031 26.6358 42.5436 26.8597 43.1073 26.8597C43.6709 26.8597 44.2115 26.6358 44.6101 26.2373C45.0086 25.8387 45.2325 25.2981 45.2325 24.7345C45.2325 24.1708 45.0086 23.6303 44.6101 23.2317C44.2115 22.8331 43.6709 22.6092 43.1073 22.6092Z" fill="black" fill-opacity="0.44"/>
              </svg>
            </label>

            <!-- PREVISUALIZACIÓN DE IMÁGENES -->
            <div 
              v-for="(img, idx) in reporteForm.evidencias" 
              :key="idx" 
              class="relative w-24 h-24 rounded-xl overflow-hidden border border-gray-200 shrink-0 shadow-xs"
            >
              <img :src="img" alt="Evidencia" class="w-full h-full object-cover" />
              <button 
                @click="eliminarEvidencia(idx)"
                type="button"
                class="absolute top-1.5 right-1.5 bg-gray-600/85 hover:bg-gray-800 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold cursor-pointer transition-colors shadow-xs"
                title="Eliminar evidencia"
              >
                ✕
              </button>
            </div>

          </div>
        </div>

        <!-- BOTONES DE ACCIÓN INFERIORES: CANCELAR / GUARDAR -->
        <div class="flex items-center justify-end gap-4 pt-6">
          <button 
            @click="vistaActual = 'tabla'"
            type="button" 
            class="px-7 py-2 bg-white border border-gray-800 text-gray-900 rounded-full text-sm font-medium hover:bg-gray-50 transition-all cursor-pointer"
          >
            Cancelar
          </button>
          
          <button 
            @click="guardarReporteAPI"
            :disabled="cargando"
            type="button" 
            class="px-8 py-2 bg-[#000B58] text-white rounded-full text-sm font-medium hover:bg-[#000840] shadow-md transition-all cursor-pointer disabled:opacity-50"
          >
            Guardar
          </button>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 3: SUBFORMULARIO "AGREGAR ACTIVIDAD" (Captura 4) -->
    <!-- ========================================================================= -->
    <div v-else-if="vistaActual === 'actividad_form'">
      
      <!-- NAVEGACIÓN BREADCRUMB -->
      <div class="flex items-center gap-2 mb-8">
        <button 
          @click="vistaActual = 'formulario'"
          class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#000B58] cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Reportes/ Nuevo reporte/Actividades</span>
        </button>
      </div>

      <!-- CAMPOS DE LA ACTIVIDAD (Idéntico a Captura 4) -->
      <div class="space-y-6 bg-white p-2 rounded-2xl">
        
        <!-- Fecha de la actividad* -->
        <div>
          <label class="block text-sm font-medium text-gray-800 mb-2">
            Fecha de la actividad<span class="text-rose-500">*</span>
          </label>
          <div class="relative w-48 flex items-center">
            <input 
              v-model="actividadForm.fecha_actividad"
              type="date" 
              class="w-full pl-10 pr-3 py-2.5 bg-white border rounded-lg text-sm text-[#00589B] font-medium focus:ring-2 focus:ring-[#000B58] outline-none cursor-pointer"
              :class="errorFechaActividad ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            />
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="20" viewBox="0 0 21 24" fill="none" class="absolute left-3 pointer-events-none">
              <g clip-path="url(#clip0_7350_1304_actividad)">
                <path d="M7.06183 1.11503C7.06183 0.497116 6.56472 0 5.94681 0C5.3289 0 4.83178 0.497116 4.83178 1.11503V2.9734H2.9734C1.33339 2.9734 0 4.30679 0 5.94681V20.8138C0 22.4538 1.33339 23.7872 2.9734 23.7872H17.8404C19.4804 23.7872 20.8138 22.4538 20.8138 20.8138V5.94681C20.8138 4.30679 19.4804 2.9734 17.8404 2.9734H15.982V1.11503C15.982 0.497116 15.4849 0 14.867 0C14.2491 0 13.752 0.497116 13.752 1.11503V2.9734H7.06183V1.11503ZM2.23005 8.92021H5.94681V11.5219H2.23005V8.92021ZM2.23005 13.752H5.94681V16.7254H2.23005V13.752ZM8.17686 13.752H12.637V16.7254H8.17686V13.752ZM14.867 13.752H18.5838V16.7254H14.867V13.752ZM18.5838 11.5219H14.867V8.92021H18.5838V11.5219ZM18.5838 18.9555V20.8138C18.5838 21.2227 18.2493 21.5572 17.8404 21.5572H14.867V18.9555H18.5838ZM12.637 18.9555V21.5572H8.17686V18.9555H12.637ZM5.94681 18.9555V21.5572H2.9734C2.56456 21.5572 2.23005 21.2227 2.23005 20.8138V18.9555H5.94681ZM12.637 11.5219H8.17686V8.92021H12.637V11.5219Z" fill="black"/>
              </g>
              <rect x="0.5" y="0.5" width="19.8138" height="22.7872" stroke="black" stroke-opacity="0.17"/>
              <defs>
                <clipPath id="clip0_7350_1304_actividad">
                  <rect width="20.8138" height="23.7872" fill="white"/>
                </clipPath>
              </defs>
            </svg>
          </div>
          <p v-if="errorFechaActividad" class="text-xs text-rose-500 mt-1.5 font-medium">
            Campo obligatorio
          </p>
        </div>

        <!-- Objetivo de la actividad* -->
        <div>
          <label class="block text-sm font-medium text-gray-800 mb-2">
            Objetivo de la actividad<span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <textarea 
              v-model="actividadForm.objetivo"
              rows="2"
              placeholder="Desarrollar el diseño de de los criterios de aceptacion con el equipo de desarrollo"
              class="w-full pr-10 p-3 bg-white border rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-[#000B58] outline-none resize-none"
              :class="errorObjetivo ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            ></textarea>
            <svg class="w-4 h-4 text-gray-700 absolute right-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
          <p v-if="errorObjetivo" class="text-xs text-rose-500 mt-1.5 font-medium">
            Campo obligatorio
          </p>
        </div>

        <!-- Actividad realizada* -->
        <div>
          <label class="block text-sm font-medium text-gray-800 mb-2">
            Actividad realizada<span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <textarea 
              v-model="actividadForm.actividad_realizada"
              rows="2"
              placeholder="Reunión de 8 horas con la célula águil"
              class="w-full pr-10 p-3 bg-white border rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-[#000B58] outline-none resize-none"
              :class="errorActividadRealizada ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            ></textarea>
            <svg class="w-4 h-4 text-gray-700 absolute right-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
          <p v-if="errorActividadRealizada" class="text-xs text-rose-500 mt-1.5 font-medium">
            Campo obligatorio
          </p>
        </div>

        <!-- Logros obtenidos* -->
        <div>
          <label class="block text-sm font-medium text-gray-800 mb-2">
            Logros obtenidos<span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <textarea 
              v-model="actividadForm.logros_obtenidos"
              rows="2"
              placeholder="Metas claras, objetivos definidos, diseño del word base"
              class="w-full pr-10 p-3 bg-white border rounded-lg text-sm text-gray-800 focus:ring-2 focus:ring-[#000B58] outline-none resize-none"
              :class="errorLogros ? 'border-rose-500 ring-1 ring-rose-500' : 'border-gray-300'"
            ></textarea>
            <svg class="w-4 h-4 text-gray-700 absolute right-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
          <p v-if="errorLogros" class="text-xs text-rose-500 mt-1.5 font-medium">
            Campo obligatorio
          </p>
        </div>

        <!-- BOTONES INFERIORES: CANCELAR / GUARDAR -->
        <div class="flex items-center justify-end gap-4 pt-6">
          <button 
            @click="cancelarActividad"
            type="button" 
            class="px-7 py-2 bg-white border border-gray-800 text-gray-900 rounded-full text-sm font-medium hover:bg-gray-50 transition-all cursor-pointer"
          >
            Cancelar
          </button>

          <button 
            @click="guardarActividad"
            type="button" 
            class="px-8 py-2 bg-[#000B58] text-white rounded-full text-sm font-medium hover:bg-[#000840] shadow-md transition-all cursor-pointer"
          >
            Guardar
          </button>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 4: DETALLE DEL REPORTE / "VER INFORME" (Captura 5) -->
    <!-- ========================================================================= -->
    <div v-else-if="vistaActual === 'detalle' && reporteDetalle">
      
      <!-- HEADER CON BREADCRUMB Y BADGE DE ESTADO DE LA CAPTURA 5 -->
      <div class="flex items-center justify-between mb-8">
        <button 
          @click="vistaActual = 'tabla'"
          class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#000B58] cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Reportes/ {{ reporteDetalle.nombre_reporte || 'ACTIVIDADES DE AGOSTO' }}</span>
        </button>

        <!-- BADGE DE ESTADO (Coincide idéntico con Captura 5) -->
        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-medium bg-[#FDE68A] text-[#92400E] shadow-xs">
          <svg class="w-3.5 h-3.5 text-[#B45309]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ reporteDetalle.estado || 'En revisión' }}
        </span>
      </div>

      <!-- INFORMACIÓN DEL REPORTE -->
      <div class="space-y-6 bg-white p-2 rounded-2xl">
        
        <!-- PRIMERA FILA: NOMBRE / PERIODO / HORAS -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
          
          <div class="md:col-span-5">
            <label class="block text-sm font-medium text-gray-800 mb-2">Nombre del reporte</label>
            <input 
              type="text" 
              :value="reporteDetalle.nombre_reporte" 
              readonly
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 font-medium uppercase"
            />
          </div>

          <div class="md:col-span-4">
            <label class="block text-sm font-medium text-gray-800 mb-2">Periodo</label>
            <input 
              type="text" 
              :value="reporteDetalle.periodo || formatearPeriodo(reporteDetalle.fecha_inicio, reporteDetalle.fecha_fin)" 
              readonly
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 font-medium"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-800 mb-2">Cantidad de horas</label>
            <input 
              type="text" 
              :value="reporteDetalle.horas_registradas || reporteDetalle.horas || 50" 
              readonly
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 font-medium"
            />
          </div>

        </div>

        <!-- ACTIVIDADES REGISTRADAS DETALLE -->
        <div class="pt-4">
          <h3 class="text-sm font-bold text-gray-800 mb-4">Acividades registradas</h3>

          <div v-if="!reporteDetalle.actividades || reporteDetalle.actividades.length === 0" class="space-y-3">
            <div class="p-4 bg-white border border-gray-200 rounded-xl">
              <h4 class="text-sm font-bold text-gray-900">Elaboración de prototipos de interfaz de usuario</h4>
              <p class="text-xs text-gray-500 mt-1">Creación de bocetos y prototipos navegables en herramientas de diseño para definir la estructura visual y flujo de pantallas según los requerimientos aprobados.</p>
            </div>
            <div class="p-4 bg-white border border-gray-200 rounded-xl">
              <h4 class="text-sm font-bold text-gray-900">Pruebas funcionales de componentes web</h4>
              <p class="text-xs text-gray-500 mt-1">Ejecución de pruebas básicas en los módulos desarrollados para verificar el correcto comportamiento de los formularios, validación de datos y navegación del sistema.</p>
            </div>
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="(act, idx) in reporteDetalle.actividades" 
              :key="idx"
              class="p-4 bg-white border border-gray-200 rounded-xl shadow-2xs"
            >
              <h4 class="text-sm font-bold text-gray-900 leading-snug">{{ act.objetivo }}</h4>
              <p class="text-xs text-gray-500 mt-1">{{ act.actividad_realizada }}</p>
              <p v-if="act.logros_obtenidos" class="text-xs text-gray-600 mt-1.5 font-medium">
                <span class="text-gray-400">Logros:</span> {{ act.logros_obtenidos }}
              </p>
            </div>
          </div>
        </div>

        <!-- EVIDENCIAS FOTOGRÁFICAS EN DETALLE -->
        <div class="pt-4">
          <div class="flex items-center gap-4 flex-wrap">
            <template v-if="reporteDetalle.evidencias && reporteDetalle.evidencias.length > 0">
              <div 
                v-for="(imgUrl, idx) in reporteDetalle.evidencias" 
                :key="idx"
                class="w-32 h-32 rounded-2xl overflow-hidden border border-gray-200 shadow-xs shrink-0"
              >
                <img :src="imgUrl" alt="Evidencia" class="w-full h-full object-cover" />
              </div>
            </template>
            <template v-else>
              <!-- IMÁGENES DEMO DE LA CAPTURA 5 -->
              <div class="w-32 h-32 rounded-2xl overflow-hidden border border-gray-200 shadow-xs shrink-0 bg-gray-100 flex items-center justify-center">
                <img src="/images/logo_ugb.png" alt="Evidencia Demo 1" class="w-full h-full object-cover opacity-80" />
              </div>
            </template>
          </div>
        </div>

        <!-- BOTÓN ATRÁS -->
        <div class="flex items-center justify-end pt-8">
          <button 
            @click="vistaActual = 'tabla'"
            type="button" 
            class="px-8 py-2 bg-white border border-gray-400 text-gray-900 rounded-full text-sm font-medium hover:bg-gray-50 transition-all cursor-pointer shadow-2xs"
          >
            Atrás
          </button>
        </div>

      </div>

    </div>

  </div>
</template>

<style scoped>
/* Ocultar el indicador nativo de calendario del navegador para que no se muestre duplicado,
   cubriendo todo el input para que al hacer clic se abra directamente el selector nativo */
input[type="date"]::-webkit-calendar-picker-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
  opacity: 0;
  cursor: pointer;
}
</style>
