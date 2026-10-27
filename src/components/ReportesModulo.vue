<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver'])

// Estado del usuario activo
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Vistas del módulo: 'tabla', 'formulario', 'detalle'
const vistaActual = ref('tabla')

// Lista de reportes por defecto calcada exactamente a la Imagen 4
const reportesPorDefecto = [
  {
    id: 1,
    mes: 'Febrero',
    periodo: '01 Febr – 27 Febr 2026',
    actividades_desarrolladas: 12,
    estado: 'En revisión',
    area_pasantia: 'Frontend',
    nombre_pasante: 'Dallana Lucía Campos García',
    fecha_inicio: '01/02/2026',
    fecha_fin: '27/02/2026',
    objetivo: 'Desarrollar componentes visuales y módulos interactivos para la plataforma Genesis.',
    actividades: [
      { numero: 1, actividad: 'Diseño de interfaz para módulo de reportes', fecha: '05/02/2026', resultado_aprendizaje: 'Aplicación de patrones UX/UI con Tailwind CSS' },
      { numero: 2, actividad: 'Integración de servicios API con Axios', fecha: '18/02/2026', resultado_aprendizaje: 'Consumo y manejo de estados asíncronos en Vue 3' }
    ],
    evidencias: ['/images/evidencia_wireframes.png', '/images/evidencia_prototipo.png']
  },
  {
    id: 2,
    mes: 'Enero',
    periodo: '01 Ener – 27 Ener 2026',
    actividades_desarrolladas: 20,
    estado: 'En revisión',
    area_pasantia: 'UX/UI',
    nombre_pasante: 'Dallana Lucía Campos García',
    fecha_inicio: '08/01/2026',
    fecha_fin: '08/27/2026',
    objetivo: 'documentar las actividades desarrolladas en la empresa',
    actividades: [
      { numero: 1, actividad: 'Elaboración de wireframes', fecha: '12/01/2026', resultado_aprendizaje: 'traducir requerimientos en estructuras de pantalla y a priorizar la jerarquia de la información.' },
      { numero: 2, actividad: 'Diseño del prototipo', fecha: '15/01/2026', resultado_aprendizaje: 'Refuerso del uso de estilos, componentes reutilizables' }
    ],
    evidencias: ['/images/evidencia_wireframes.png', '/images/evidencia_prototipo.png']
  }
]

// Lista de reportes y estados de carga
const reportes = ref([...reportesPorDefecto])
const cargando = ref(false)
const enviando = ref(false)

// Notificación de éxito estilo Toast (como en Captura 4)
const toastExitoVisible = ref(false)
const toastMensaje = ref('El reporte ha sido enviado de manera exitosa.')

// Notificación de feedback general (errores / avisos)
const feedbackMensaje = ref('')
const feedbackTipo = ref('error') // 'error' | 'info'

// Filtros de la tabla principal
const busqueda = ref('')
const estadoFiltro = ref('Todos')
const dropdownEstadoAbierto = ref(false)

// Catálogos para el formulario
const areasPasantia = [
  'Frontend',
  'Backend',
  'Full Stack',
  'UX/UI',
  'QA',
  'Infraestructura'
]

const mesesDelAnio = [
  'Enero',
  'Febrero',
  'Marzo',
  'Abril',
  'Mayo',
  'Junio',
  'Julio',
  'Agosto',
  'Septiembre',
  'Octubre',
  'Noviembre',
  'Diciembre'
]

// Control de apertura de dropdowns en el formulario
const dropdownAreaAbierto = ref(false)
const dropdownMesAbierto = ref(false)

// Estado del formulario (Crear / Editar)
const esEdicion = ref(false)
const reporteIdEditando = ref(null)
const intentoGuardar = ref(false)

const reporteForm = ref({
  area_pasantia: '',
  nombre_pasante: '',
  mes: '',
  fecha_inicio: '',
  fecha_fin: '',
  objetivo: '',
  actividades: [],
  evidencias: []
})

// Estado de la Vista de Detalle y Lightbox
const reporteDetalle = ref(null)
const imagenLightbox = ref(null)

// ----------------------------------------------------
// Helpers de Fechas
// ----------------------------------------------------
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

const getMesActualNombre = () => {
  const now = new Date()
  return mesesDelAnio[now.getMonth()] || 'Enero'
}

// Formatear fechas en formato DD/MM/AAAA (ej: 12/01/2026)
const formatearFecha = (fechaStr) => {
  if (!fechaStr) return '-'
  const limpio = String(fechaStr).split('T')[0].split(' ')[0].trim()

  if (limpio.includes('-')) {
    const partes = limpio.split('-')
    if (partes.length === 3) {
      const [anio, mes, dia] = partes
      return `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${anio}`
    }
  }

  if (limpio.includes('/')) {
    return limpio
  }

  return limpio
}

// ----------------------------------------------------
// Carga de Datos desde la API
// ----------------------------------------------------
const cargarReportes = async () => {
  cargando.value = true
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const token = localStorage.getItem('genesis_token')
    const headers = {}
    if (token) headers['Authorization'] = `Bearer ${token}`
    if (userId) headers['X-User-Id'] = userId

    const res = await axios.get('/api/reportes', {
      headers,
      params: {
        user_id: userId,
        search: busqueda.value,
        estado: estadoFiltro.value !== 'Todos' ? estadoFiltro.value : undefined
      }
    })

    if (res.data && res.data.data && res.data.data.length > 0) {
      reportes.value = res.data.data
    } else {
      reportes.value = [...reportesPorDefecto]
    }
  } catch (err) {
    reportes.value = [...reportesPorDefecto]
  } finally {
    cargando.value = false
  }
}

// Inicializar datos sugeridos del pasante
const inicializarDatosFormulario = async () => {
  const nombreGuardado = usuario.value?.nombres
    ? `${usuario.value.nombres} ${usuario.value.apellidos || ''}`.trim()
    : 'Dallana Lucía Campos García'

  reporteForm.value = {
    area_pasantia: '',
    nombre_pasante: nombreGuardado,
    mes: getMesActualNombre(),
    fecha_inicio: getFechaInicioMesActualString(),
    fecha_fin: getFechaActualString(),
    objetivo: '',
    actividades: [
      { numero: 1, actividad: '', fecha: '', resultado_aprendizaje: '' },
      { numero: 2, actividad: '', fecha: '', resultado_aprendizaje: '' }
    ],
    evidencias: []
  }

  // Intentar cargar sugerencias de la API si existen
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const token = localStorage.getItem('genesis_token')
    const headers = {}
    if (token) headers['Authorization'] = `Bearer ${token}`
    if (userId) headers['X-User-Id'] = userId

    const res = await axios.get('/api/reportes/formulario-inicial', { headers })
    if (res.data && res.data.data) {
      const data = res.data.data
      if (data.nombre_pasante) reporteForm.value.nombre_pasante = data.nombre_pasante
      if (data.area_pasantia) {
        // Si el área de la API coincide con alguna de las opciones, asignarla
        const coincide = areasPasantia.find(a => a.toLowerCase() === data.area_pasantia.toLowerCase())
        if (coincide) reporteForm.value.area_pasantia = coincide
      }
      if (data.mes_sugerido) reporteForm.value.mes = data.mes_sugerido
    }
  } catch (e) {
    // Si falla, se mantienen los valores por defecto
  }
}

// ----------------------------------------------------
// Acciones de Navegación y Apertura
// ----------------------------------------------------
const abrirNuevoReporte = async () => {
  esEdicion.value = false
  reporteIdEditando.value = null
  intentoGuardar.value = false
  dropdownAreaAbierto.value = false
  dropdownMesAbierto.value = false
  await inicializarDatosFormulario()
  vistaActual.value = 'formulario'
}

const verDetalle = async (item) => {
  cargando.value = true
  try {
    const token = localStorage.getItem('genesis_token')
    const headers = token ? { Authorization: `Bearer ${token}` } : {}
    const res = await axios.get(`/api/reportes/${item.id}`, { headers })
    if (res.data && res.data.data) {
      reporteDetalle.value = res.data.data
    } else {
      reporteDetalle.value = item
    }
  } catch (e) {
    reporteDetalle.value = item
  } finally {
    cargando.value = false
    vistaActual.value = 'detalle'
  }
}

const volverATabla = () => {
  dropdownAreaAbierto.value = false
  dropdownMesAbierto.value = false
  dropdownEstadoAbierto.value = false
  vistaActual.value = 'tabla'
}

const editarReporteDesdeDetalle = () => {
  if (!reporteDetalle.value) return
  esEdicion.value = true
  reporteIdEditando.value = reporteDetalle.value.id
  reporteForm.value = {
    area_pasantia: reporteDetalle.value.area_pasantia || '',
    nombre_pasante: reporteDetalle.value.nombre_pasante || '',
    mes: reporteDetalle.value.mes || '',
    fecha_inicio: reporteDetalle.value.fecha_inicio ? String(reporteDetalle.value.fecha_inicio).split('T')[0] : '',
    fecha_fin: reporteDetalle.value.fecha_fin ? String(reporteDetalle.value.fecha_fin).split('T')[0] : '',
    objetivo: reporteDetalle.value.objetivo || '',
    actividades: reporteDetalle.value.actividades && reporteDetalle.value.actividades.length > 0
      ? JSON.parse(JSON.stringify(reporteDetalle.value.actividades))
      : [
          { numero: 1, actividad: '', fecha: '', resultado_aprendizaje: '' },
          { numero: 2, actividad: '', fecha: '', resultado_aprendizaje: '' }
        ],
    evidencias: reporteDetalle.value.evidencias ? [...reporteDetalle.value.evidencias] : []
  }
  vistaActual.value = 'formulario'
}

// ----------------------------------------------------
// Gestión de Filas de Actividades (En línea)
// ----------------------------------------------------
const agregarFilaActividad = () => {
  const nuevoNumero = reporteForm.value.actividades.length + 1
  reporteForm.value.actividades.push({
    numero: nuevoNumero,
    actividad: '',
    fecha: '',
    resultado_aprendizaje: ''
  })
}

const eliminarFilaActividad = (index) => {
  if (reporteForm.value.actividades.length <= 1) {
    // Si es la única fila, solo la limpiamos para mantener al menos una
    reporteForm.value.actividades[0] = {
      numero: 1,
      actividad: '',
      fecha: '',
      resultado_aprendizaje: ''
    }
    return
  }

  reporteForm.value.actividades.splice(index, 1)
  // Re-enumerar correlativo
  reporteForm.value.actividades.forEach((act, i) => {
    act.numero = i + 1
  })
}

// ----------------------------------------------------
// Gestión de Imágenes y Evidencias
// ----------------------------------------------------
const triggerInputFoto = () => {
  const input = document.getElementById('input-evidencias-upload')
  if (input) input.click()
}

const handleFileUpload = (event) => {
  const files = event.target.files
  if (!files || files.length === 0) return

  const fotosActuales = reporteForm.value.evidencias.length
  if (fotosActuales >= 6) {
    mostrarFeedback('Se permite un máximo de 6 imágenes de evidencia por reporte.')
    event.target.value = ''
    return
  }

  const espacioDisponible = 6 - fotosActuales
  const cantidadACargar = Math.min(files.length, espacioDisponible)

  for (let i = 0; i < cantidadACargar; i++) {
    const file = files[i]
    if (!file.type.startsWith('image/')) continue

    const reader = new FileReader()
    reader.onload = (e) => {
      if (reporteForm.value.evidencias.length < 6) {
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
// Guardar y Enviar Reporte a la API
// ----------------------------------------------------
const enviarReporte = async () => {
  intentoGuardar.value = true
  feedbackMensaje.value = ''

  // Validaciones obligatorias de Información General
  if (!reporteForm.value.area_pasantia?.trim()) {
    mostrarFeedback('Por favor seleccione el área de pasantía.')
    return
  }
  if (!reporteForm.value.nombre_pasante?.trim()) {
    mostrarFeedback('El nombre del pasante es requerido.')
    return
  }
  if (!reporteForm.value.mes?.trim()) {
    mostrarFeedback('Por favor seleccione el mes del reporte.')
    return
  }
  if (!reporteForm.value.fecha_inicio) {
    mostrarFeedback('La fecha de inicio es requerida.')
    return
  }
  if (!reporteForm.value.fecha_fin) {
    mostrarFeedback('La fecha de finalización es requerida.')
    return
  }
  if (!reporteForm.value.objetivo?.trim()) {
    mostrarFeedback('El objetivo del reporte es requerido.')
    return
  }

  // Filtrar actividades válidas que tengan contenido
  const actividadesValidas = reporteForm.value.actividades.filter(
    a => a.actividad?.trim() || a.fecha || a.resultado_aprendizaje?.trim()
  )

  if (actividadesValidas.length === 0) {
    mostrarFeedback('Debe registrar al menos una actividad realizada con su fecha y resultado.')
    return
  }

  // Validar que cada actividad con contenido esté completa
  for (let i = 0; i < actividadesValidas.length; i++) {
    const act = actividadesValidas[i]
    if (!act.actividad?.trim() || !act.fecha || !act.resultado_aprendizaje?.trim()) {
      mostrarFeedback(`Por favor complete todos los campos de la actividad Nº ${i + 1} (actividad, fecha y resultado).`)
      return
    }
  }

  enviando.value = true
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const token = localStorage.getItem('genesis_token')
    const headers = { 'Content-Type': 'application/json' }
    if (token) headers['Authorization'] = `Bearer ${token}`
    if (userId) headers['X-User-Id'] = userId

    const payload = {
      user_id: userId,
      area_pasantia: reporteForm.value.area_pasantia,
      nombre_pasante: reporteForm.value.nombre_pasante,
      mes: reporteForm.value.mes,
      fecha_inicio: reporteForm.value.fecha_inicio,
      fecha_fin: reporteForm.value.fecha_fin,
      objetivo: reporteForm.value.objetivo,
      estado: 'En revisión',
      actividades: actividadesValidas.map((a, idx) => ({
        numero: idx + 1,
        actividad: a.actividad.trim(),
        fecha: a.fecha,
        resultado_aprendizaje: a.resultado_aprendizaje.trim()
      })),
      evidencias: reporteForm.value.evidencias
    }

    if (esEdicion.value && reporteIdEditando.value) {
      await axios.put(`/api/reportes/${reporteIdEditando.value}`, payload, { headers })
    } else {
      await axios.post('/api/reportes', payload, { headers })
    }

    // Refrescar lista
    await cargarReportes()

    // Mostrar modal / toast de éxito de la Captura 4
    toastMensaje.value = 'El reporte ha sido enviado de manera exitosa.'
    toastExitoVisible.value = true

    // Regresar a la tabla
    vistaActual.value = 'tabla'
  } catch (err) {
    console.error('Error al enviar reporte:', err)
    const msg = err.response?.data?.mensaje || err.response?.data?.message || 'Error al enviar el reporte. Verifique los datos.'
    mostrarFeedback(msg)
  } finally {
    enviando.value = false
  }
}

const mostrarFeedback = (mensaje, tipo = 'error') => {
  feedbackMensaje.value = mensaje
  feedbackTipo.value = tipo
  setTimeout(() => {
    feedbackMensaje.value = ''
  }, 5000)
}

const cerrarToastExito = () => {
  toastExitoVisible.value = false
}

// Filtro computado de reportes en caso de búsqueda local rápida
const reportesMostrados = computed(() => {
  let lista = reportes.value && reportes.value.length > 0 ? reportes.value : reportesPorDefecto

  if (estadoFiltro.value && estadoFiltro.value !== 'Todos') {
    lista = lista.filter(item => item.estado === estadoFiltro.value)
  }

  if (!busqueda.value?.trim()) return lista

  const q = busqueda.value.toLowerCase().trim()
  return lista.filter(item => {
    const mes = (item.mes || '').toLowerCase()
    const periodo = (item.periodo || '').toLowerCase()
    const area = (item.area_pasantia || '').toLowerCase()
    const pasante = (item.nombre_pasante || '').toLowerCase()
    const objetivo = (item.objetivo || '').toLowerCase()
    return mes.includes(q) || periodo.includes(q) || area.includes(q) || pasante.includes(q) || objetivo.includes(q)
  })
})

onMounted(() => {
  cargarReportes()
})
</script>

<template>
  <div class="max-w-6xl mx-auto font-sans antialiased text-slate-800 relative pb-12">

    <!-- ========================================================================= -->
    <!-- TOAST NOTIFICACIÓN: ENVIADO DE MANERA EXITOSA (Captura 4) -->
    <!-- ========================================================================= -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="toastExitoVisible"
        class="fixed bottom-6 right-6 z-50 bg-[#00C853] text-white rounded-2xl shadow-2xl p-5 w-80 sm:w-96 border border-emerald-400/40"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-2.5">
            <!-- Icono check circular -->
            <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h4 class="text-lg font-bold tracking-tight">Enviado</h4>
          </div>
          <!-- Botón cerrar (X) -->
          <button 
            @click="cerrarToastExito"
            class="text-white/80 hover:text-white transition-colors cursor-pointer p-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <p class="text-white/95 text-sm mt-2 mb-4 leading-relaxed font-normal">
          {{ toastMensaje }}
        </p>

        <div class="flex justify-end">
          <button
            @click="cerrarToastExito"
            class="px-5 py-1.5 border border-white rounded-lg text-xs font-semibold text-white hover:bg-white hover:text-[#00C853] transition-all cursor-pointer shadow-xs"
          >
            Aceptar
          </button>
        </div>
      </div>
    </transition>

    <!-- BANNER DE FEEDBACK TEMPORAL (ERRORES O ADVERTENCIAS) -->
    <div 
      v-if="feedbackMensaje"
      class="mb-6 p-4 rounded-xl text-sm font-medium flex items-center justify-between border shadow-xs"
      :class="feedbackTipo === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-blue-50 text-blue-800 border-blue-200'"
    >
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ feedbackMensaje }}</span>
      </div>
      <button @click="feedbackMensaje = ''" class="text-sm font-bold opacity-70 hover:opacity-100 cursor-pointer">✕</button>
    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 1: TABLA PRINCIPAL DE REPORTES (Captura 1) -->
    <!-- ========================================================================= -->
    <div v-if="vistaActual === 'tabla'">
      
      <!-- TOOLBAR SUPERIOR: BUSCAR + ESTADO + BOTÓN NUEVO REPORTE -->
      <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        
        <!-- BARRA DE BÚSQUEDA REDONDEADA AZUL -->
        <div class="relative flex-1 min-w-[260px] max-w-md">
          <input 
            v-model="busqueda"
            @input="cargarReportes"
            type="text" 
            placeholder="Buscar..."
            class="w-full pl-5 pr-12 py-2.5 bg-white border border-[#2563EB]/70 rounded-full text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs placeholder-slate-400 font-normal transition-all"
          />
          <svg class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- FILTRO ESTADO + BOTÓN NUEVO REPORTE -->
        <div class="flex items-center gap-4 flex-wrap">
          
          <!-- DROPDOWN ESTADO: TODOS (Imagen 4) -->
          <div class="relative min-w-[140px]">
            <button 
              @click="dropdownEstadoAbierto = !dropdownEstadoAbierto"
              class="w-full flex items-center justify-between gap-2 px-3.5 py-2 bg-white border border-[#2563EB] text-sm text-slate-800 hover:bg-slate-50 shadow-xs cursor-pointer transition-all"
              :class="dropdownEstadoAbierto ? 'rounded-t-[8px] border-b-0' : 'rounded-[8px]'"
            >
              <span class="text-[13px] font-normal">Estado: {{ estadoFiltro }}</span>
              <span class="text-[10px] text-black">
                {{ dropdownEstadoAbierto ? '▲' : '▼' }}
              </span>
            </button>

            <!-- Menú desplegable de estados exacto a Imagen 4 -->
            <div 
              v-if="dropdownEstadoAbierto" 
              class="absolute left-0 right-0 top-full bg-white border border-[#2563EB] rounded-b-[8px] shadow-lg z-30 overflow-hidden py-1 text-center"
            >
              <button 
                v-for="opc in ['En revisión', 'Aprobado', 'Todos']"
                :key="opc"
                @click="estadoFiltro = opc; dropdownEstadoAbierto = false; cargarReportes()"
                class="w-full py-1.5 text-[13px] text-slate-800 hover:bg-blue-50 cursor-pointer transition-colors text-center block"
                :class="{ 'font-semibold text-[#000B58]': estadoFiltro === opc }"
              >
                {{ opc }}
              </button>
            </div>
          </div>

          <!-- BOTÓN + NUEVO REPORTE -->
          <button 
            @click="abrirNuevoReporte"
            class="flex items-center gap-2 px-6 py-2.5 bg-[#000B58] hover:bg-[#000840] text-white rounded-full text-sm font-medium shadow-md transition-all cursor-pointer font-serif tracking-wide"
          >
            <!-- Icono circular blanco con plus -->
            <div class="w-5 h-5 rounded-full bg-white flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-[#000B58]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
            </div>
            <span>Nuevo reporte</span>
          </button>

        </div>
      </div>

      <!-- ENCABEZADOS DE COLUMNA DE LA TABLA (Idénticos a la Captura 1) -->
      <div class="grid grid-cols-12 gap-4 px-6 py-3 text-xs font-semibold text-slate-400 mb-2">
        <div class="col-span-3 text-left">Mes del reporte</div>
        <div class="col-span-3 text-left">Periodo</div>
        <div class="col-span-2 text-center">Actividades desarrolladas</div>
        <div class="col-span-2 text-center">Estado</div>
        <div class="col-span-2 text-center">Acciones</div>
      </div>

      <!-- INDICADOR DE CARGA -->
      <div v-if="cargando" class="py-16 text-center text-slate-500 text-sm bg-white rounded-2xl border border-slate-100 shadow-xs">
        <div class="inline-block animate-spin w-6 h-6 border-2 border-[#000B58] border-t-transparent rounded-full mb-2"></div>
        <p>Cargando reportes mensuales...</p>
      </div>

      <!-- LISTA VACÍA (Sin datos de prueba) -->
      <div v-else-if="reportesMostrados.length === 0" class="py-16 text-center bg-white rounded-2xl border border-dashed border-slate-300 shadow-xs">
        <div class="w-14 h-14 mx-auto mb-3 text-slate-300">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-700 mb-1">No hay reportes registrados aún</h3>
        <p class="text-slate-500 text-xs mb-4 max-w-sm mx-auto">
          Comienza redactando tu primer reporte mensual de pasantía para dar seguimiento a tus actividades.
        </p>
        <button 
          @click="abrirNuevoReporte"
          class="px-6 py-2.5 bg-[#000B58] hover:bg-[#000840] text-white text-xs font-semibold rounded-full shadow-sm cursor-pointer transition-all"
        >
          + Crear primer reporte
        </button>
      </div>

      <!-- FILAS DE REPORTES (Exactamente como en Captura 1) -->
      <div v-else class="space-y-3">
        <div 
          v-for="item in reportesMostrados" 
          :key="item.id"
          class="grid grid-cols-12 gap-4 items-center bg-white px-6 py-4 rounded-xl border border-slate-100 shadow-xs hover:shadow-md transition-all"
        >
          <!-- COLUMNA 1: MES DEL REPORTE CON ÍCONO (Imagen 4) -->
          <div class="col-span-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-slate-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="16" rx="2" />
              <circle cx="8.5" cy="9.5" r="1.5" />
              <line x1="12.5" y1="8.5" x2="17.5" y2="8.5" />
              <line x1="12.5" y1="11.5" x2="17.5" y2="11.5" />
              <line x1="6.5" y1="15.5" x2="17.5" y2="15.5" />
            </svg>
            <span class="text-sm font-semibold text-slate-800 capitalize">
              {{ item.mes || 'Reporte' }}
            </span>
          </div>

          <!-- COLUMNA 2: PERIODO -->
          <div class="col-span-3 text-left text-xs font-medium text-slate-600">
            {{ item.periodo || 'Periodo no definido' }}
          </div>

          <!-- COLUMNA 3: ACTIVIDADES DESARROLLADAS (Conteo) -->
          <div class="col-span-2 text-center text-sm font-semibold text-slate-700">
            {{ item.actividades_desarrolladas ?? (item.actividades ? item.actividades.length : 0) }}
          </div>

          <!-- COLUMNA 4: ESTADO (Badges calcados a Figma — Imágenes 1, 2 y 3) -->
          <div class="col-span-2 flex justify-center">
            <!-- En revisión -->
            <span 
              v-if="item.estado === 'En revisión' || item.estado === 'en_espera'"
              class="select-none"
              style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: var(--color-acento, #FFC071); align-items: center; justify-content: center; gap: 8px;"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none" class="shrink-0">
                <path d="M14.876 7.5C14.876 11.6421 11.5459 15 7.43802 15C3.33011 15 0 11.6421 0 7.5C0 3.35786 3.33011 0 7.43802 0C11.5459 0 14.876 3.35786 14.876 7.5Z" stroke="rgba(0, 0, 0, 0.66)" stroke-width="1.57895"/>
                <g transform="translate(2.44, 2.5)">
                  <path d="M2.01025 4.82461L4.02053 6.83488L8.04108 2.81433" stroke-width="1.57895" stroke-linecap="round" stroke-linejoin="round" stroke="black" stroke-opacity="0.6"/>
                </g>
              </svg>
              <span class="text-black font-bold text-[13px] leading-none">En revisión</span>
            </span>

            <!-- Aprobado -->
            <span 
              v-else-if="item.estado === 'Aprobado' || item.estado === 'aprobado'"
              class="select-none"
              style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: #00C853; align-items: center; justify-content: center; gap: 8px;"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none" class="shrink-0">
                <path d="M14.876 7.5C14.876 11.6421 11.5459 15 7.43802 15C3.33011 15 0 11.6421 0 7.5C0 3.35786 3.33011 0 7.43802 0C11.5459 0 14.876 3.35786 14.876 7.5Z" stroke="rgba(0, 0, 0, 0.66)" stroke-width="1.57895"/>
                <g transform="translate(2.44, 2.5)">
                  <path d="M2.01025 4.82461L4.02053 6.83488L8.04108 2.81433" stroke-width="1.57895" stroke-linecap="round" stroke-linejoin="round" stroke="black" stroke-opacity="0.6"/>
                </g>
              </svg>
              <span class="text-black font-bold text-[13px] leading-none">Aprobado</span>
            </span>

            <!-- Otros estados -->
            <span 
              v-else
              class="select-none"
              style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: #E2E8F0; align-items: center; justify-content: center; gap: 8px;"
            >
              <span class="text-slate-800 font-bold text-[13px] leading-none">{{ item.estado || 'Enviado' }}</span>
            </span>
          </div>

          <!-- COLUMNA 5: ACCIONES: VER REPORTE -->
          <div class="col-span-2 flex justify-center">
            <button 
              @click="verDetalle(item)"
              class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-[#000B58] transition-colors cursor-pointer group"
            >
              <svg class="w-4 h-4 text-slate-500 group-hover:text-[#000B58] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <span>Ver informe</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 2: FORMULARIO "NUEVO REPORTE" (Capturas 2, 3 y 4) -->
    <!-- ========================================================================= -->
    <div v-else-if="vistaActual === 'formulario'">
      
      <!-- ENLACE VOLVER: ← Reportes/ Nuevo reporte -->
      <div class="mb-5">
        <button 
          @click="volverATabla"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-[#000B58] transition-colors cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Reportes/ Nuevo reporte</span>
        </button>
      </div>

      <!-- CONTENEDOR PRINCIPAL CON BORDE AZUL CLARO (Como en las capturas 2, 3 y 4) -->
      <div class="bg-white rounded-2xl border border-blue-400 p-8 sm:p-10 shadow-sm max-w-4xl mx-auto">
        
        <!-- ENCABEZADO INSTITUCIONAL DE LA FACULTAD -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6 mb-8 pb-6 border-b border-slate-100">
          <!-- Logo UGB -->
          <div class="shrink-0 flex items-center justify-center">
            <img 
              src="/images/Logo_para_reportes_mensuales.png" 
              alt="Universidad Gerardo Barrios" 
              style="width:123.482px; height:100px; flex-shrink:0; aspect-ratio:123.48/100; object-fit:contain;"
              @error="(e) => e.target.style.display = 'none'"
            />
          </div>

          <!-- Títulos Oficiales de la Facultad -->
          <div class="text-center">
            <h2 style="font-family:'Lora',serif; font-size:24px; font-weight:500; color:#010C67; text-align:center; line-height:normal;">
              FACULTAD DE CIENCIA Y TECNOLOGÍA
            </h2>
            <h3 class="text-sm font-semibold text-[#010C67] text-center mt-0.5">
              Trabajo de Graduación Modalidad Pasantía
            </h3>
            <p class="text-sm font-medium text-[#1E3A8A] text-center mt-0.5">
              Reporte mensual
            </p>
          </div>
        </div>

        <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
        <div class="mb-8">
          <h3 class="text-lg font-serif font-bold text-slate-800 mb-5">
            Información General
          </h3>

          <!-- FILA 1: Área de pasantía + Nombre del pasante + Mes -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5 items-start">
            
            <!-- Área de pasantía* (Dropdown) -->
            <div class="md:col-span-4 relative">
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Área de pasantía<span class="text-rose-500">*</span>
              </label>
              
              <div class="relative">
                <button
                  type="button"
                  @click="dropdownAreaAbierto = !dropdownAreaAbierto; dropdownMesAbierto = false"
                  class="w-full px-3.5 py-2.5 bg-white border rounded-lg text-sm text-left flex items-center justify-between cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#000B58] transition-all shadow-xs"
                  :class="intentoGuardar && !reporteForm.area_pasantia ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                >
                  <span :class="reporteForm.area_pasantia ? 'text-slate-800 font-medium' : 'text-slate-400'">
                    {{ reporteForm.area_pasantia || 'Seleccionar' }}
                  </span>
                  <svg class="w-4 h-4 text-slate-600 transition-transform" :class="{ 'rotate-180': dropdownAreaAbierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Menú desplegable con las opciones exactas (Capturas 3 y 4) -->
                <div 
                  v-if="dropdownAreaAbierto"
                  class="absolute left-0 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-xl z-30 py-1 max-h-56 overflow-y-auto"
                >
                  <button
                    v-for="area in areasPasantia"
                    :key="area"
                    type="button"
                    @click="reporteForm.area_pasantia = area; dropdownAreaAbierto = false"
                    class="w-full px-4 py-2 text-sm text-left text-slate-700 hover:bg-slate-100 flex items-center justify-between cursor-pointer transition-colors"
                    :class="{ 'font-semibold text-[#000B58] bg-blue-50/40': reporteForm.area_pasantia === area }"
                  >
                    <span>{{ area }}</span>
                    <span v-if="reporteForm.area_pasantia === area" class="text-xs text-[#000B58]">✓</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Nombre del pasante* -->
            <div class="md:col-span-5">
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Nombre del pasante<span class="text-rose-500">*</span>
              </label>
              <input 
                v-model="reporteForm.nombre_pasante"
                type="text" 
                placeholder="Dallana Lucía Campos García"
                class="w-full px-3.5 py-2.5 bg-white border rounded-lg text-sm text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs transition-all"
                :class="intentoGuardar && !reporteForm.nombre_pasante?.trim() ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
              />
            </div>

            <!-- Mes* (Dropdown con meses del año) -->
            <div class="md:col-span-3 relative">
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Mes<span class="text-rose-500">*</span>
              </label>

              <div class="relative">
                <button
                  type="button"
                  @click="dropdownMesAbierto = !dropdownMesAbierto; dropdownAreaAbierto = false"
                  class="w-full px-3.5 py-2.5 bg-white border rounded-lg text-sm text-left flex items-center justify-between cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#000B58] transition-all shadow-xs"
                  :class="intentoGuardar && !reporteForm.mes ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                >
                  <span :class="reporteForm.mes ? 'text-slate-800 font-medium' : 'text-slate-400'">
                    {{ reporteForm.mes || 'Seleccionar' }}
                  </span>
                  <svg class="w-4 h-4 text-slate-600 transition-transform" :class="{ 'rotate-180': dropdownMesAbierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Menú desplegable con meses -->
                <div 
                  v-if="dropdownMesAbierto"
                  class="absolute right-0 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-xl z-30 py-1 max-h-56 overflow-y-auto"
                >
                  <button
                    v-for="m in mesesDelAnio"
                    :key="m"
                    type="button"
                    @click="reporteForm.mes = m; dropdownMesAbierto = false"
                    class="w-full px-4 py-2 text-sm text-left text-slate-700 hover:bg-slate-100 flex items-center justify-between cursor-pointer transition-colors"
                    :class="{ 'font-semibold text-[#000B58] bg-blue-50/40': reporteForm.mes === m }"
                  >
                    <span>{{ m }}</span>
                    <span v-if="reporteForm.mes === m" class="text-xs text-[#000B58]">✓</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- FILA 2: Fecha Inicio + Fecha Finalización -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
            
            <!-- Fecha Inicio* -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Fecha Inicio<span class="text-rose-500">*</span>
              </label>
              <div class="relative flex items-center">
                <input 
                  v-model="reporteForm.fecha_inicio"
                  type="date" 
                  class="w-full pl-10 pr-3.5 py-2.5 bg-white border rounded-lg text-sm text-[#00589B] font-semibold focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs cursor-pointer"
                  :class="intentoGuardar && !reporteForm.fecha_inicio ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                />
                <!-- Icono de calendario -->
                <div class="absolute left-3 pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 23 26" fill="none">
                    <g clip-path="url(#clip0_fecha_inicio)">
                      <path d="M7.71875 1.21875C7.71875 0.543359 7.17539 0 6.5 0C5.82461 0 5.28125 0.543359 5.28125 1.21875V3.25H3.25C1.45742 3.25 0 4.70742 0 6.5V22.75C0 24.5426 1.45742 26 3.25 26H19.5C21.2926 26 22.75 24.5426 22.75 22.75V6.5C22.75 4.70742 21.2926 3.25 19.5 3.25H17.4688V1.21875C17.4688 0.543359 16.9254 0 16.25 0C15.5746 0 15.0312 0.543359 15.0312 1.21875V3.25H7.71875V1.21875ZM2.4375 9.75H6.5V12.5938H2.4375V9.75ZM2.4375 15.0312H6.5V18.2812H2.4375V15.0312ZM8.9375 15.0312H13.8125V18.2812H8.9375V15.0312ZM16.25 15.0312H20.3125V18.2812H16.25V15.0312ZM20.3125 12.5938H16.25V9.75H20.3125V12.5938ZM20.3125 20.7188V22.75C20.3125 23.1969 19.9469 23.5625 19.5 23.5625H16.25V20.7188H20.3125ZM13.8125 20.7188V23.5625H8.9375V20.7188H13.8125ZM6.5 20.7188V23.5625H3.25C2.80312 23.5625 2.4375 23.1969 2.4375 22.75V20.7188H6.5ZM13.8125 12.5938H8.9375V9.75H13.8125V12.5938Z" fill="#475569"/>
                    </g>
                    <defs>
                      <clipPath id="clip0_fecha_inicio">
                        <rect width="22.75" height="26" fill="white"/>
                      </clipPath>
                    </defs>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Fecha Finalización* -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Fecha Finalización<span class="text-rose-500">*</span>
              </label>
              <div class="relative flex items-center">
                <input 
                  v-model="reporteForm.fecha_fin"
                  type="date" 
                  class="w-full pl-10 pr-3.5 py-2.5 bg-white border rounded-lg text-sm text-[#00589B] font-semibold focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs cursor-pointer"
                  :class="intentoGuardar && !reporteForm.fecha_fin ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
                />
                <!-- Icono de calendario -->
                <div class="absolute left-3 pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 23 26" fill="none">
                    <g clip-path="url(#clip0_fecha_fin)">
                      <path d="M7.71875 1.21875C7.71875 0.543359 7.17539 0 6.5 0C5.82461 0 5.28125 0.543359 5.28125 1.21875V3.25H3.25C1.45742 3.25 0 4.70742 0 6.5V22.75C0 24.5426 1.45742 26 3.25 26H19.5C21.2926 26 22.75 24.5426 22.75 22.75V6.5C22.75 4.70742 21.2926 3.25 19.5 3.25H17.4688V1.21875C17.4688 0.543359 16.9254 0 16.25 0C15.5746 0 15.0312 0.543359 15.0312 1.21875V3.25H7.71875V1.21875ZM2.4375 9.75H6.5V12.5938H2.4375V9.75ZM2.4375 15.0312H6.5V18.2812H2.4375V15.0312ZM8.9375 15.0312H13.8125V18.2812H8.9375V15.0312ZM16.25 15.0312H20.3125V18.2812H16.25V15.0312ZM20.3125 12.5938H16.25V9.75H20.3125V12.5938ZM20.3125 20.7188V22.75C20.3125 23.1969 19.9469 23.5625 19.5 23.5625H16.25V20.7188H20.3125ZM13.8125 20.7188V23.5625H8.9375V20.7188H13.8125ZM6.5 20.7188V23.5625H3.25C2.80312 23.5625 2.4375 23.1969 2.4375 22.75V20.7188H6.5ZM13.8125 12.5938H8.9375V9.75H13.8125V12.5938Z" fill="#475569"/>
                    </g>
                    <defs>
                      <clipPath id="clip0_fecha_fin">
                        <rect width="22.75" height="26" fill="white"/>
                      </clipPath>
                    </defs>
                  </svg>
                </div>
              </div>
            </div>

          </div>

          <!-- FILA 3: Objetivo* -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
              Objetivo<span class="text-rose-500">*</span>
            </label>
            <input 
              v-model="reporteForm.objetivo"
              type="text" 
              placeholder="documentar las actividades desarrolladas en la empresa"
              class="w-full px-4 py-2.5 bg-white border rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#000B58] shadow-xs"
              :class="intentoGuardar && !reporteForm.objetivo?.trim() ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300'"
            />
          </div>

        </div>

        <!-- SECCIÓN 2: ACTIVIDADES REALIZADAS (Tabla dinámica en línea) -->
        <div class="mb-8">
          <h3 class="text-lg font-serif font-bold text-slate-800 mb-4">
            Actividades realizadas
          </h3>

          <!-- ENCABEZADOS DE LA TABLA DE ACTIVIDADES -->
          <div class="grid grid-cols-12 gap-3 px-3 py-2 text-xs font-semibold text-slate-600 mb-1 border-b border-slate-100">
            <div class="col-span-1 text-center">Nº</div>
            <div class="col-span-4">Actividad</div>
            <div class="col-span-3 text-center">Fecha</div>
            <div class="col-span-3">Resultado de aprendizaje</div>
            <div class="col-span-1 text-center"></div>
          </div>

          <!-- FILAS DE ACTIVIDADES EDITABLES EN LÍNEA -->
          <div class="space-y-3 mt-2">
            <div 
              v-for="(act, index) in reporteForm.actividades" 
              :key="index"
              class="grid grid-cols-12 gap-3 items-center group"
            >
              <!-- Nº -->
              <div class="col-span-1 text-center text-sm font-semibold text-slate-700">
                {{ index + 1 }}
              </div>

              <!-- Actividad realizada -->
              <div class="col-span-4">
                <input 
                  v-model="act.actividad"
                  type="text" 
                  placeholder="Actividad realizada"
                  class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-100/80 focus:bg-white border border-transparent focus:border-slate-300 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#000B58] transition-all"
                />
              </div>

              <!-- Fecha -->
              <div class="col-span-3">
                <input 
                  v-model="act.fecha"
                  type="date" 
                  placeholder="dd/mm/aaaa"
                  class="w-full px-2.5 py-2 bg-slate-100 hover:bg-slate-100/80 focus:bg-white border border-transparent focus:border-slate-300 rounded-lg text-sm text-slate-700 text-center focus:outline-none focus:ring-1 focus:ring-[#000B58] transition-all cursor-pointer"
                />
              </div>

              <!-- Resultado de aprendizaje -->
              <div class="col-span-3">
                <input 
                  v-model="act.resultado_aprendizaje"
                  type="text" 
                  placeholder="Aprendizaje obtenido"
                  class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-100/80 focus:bg-white border border-transparent focus:border-slate-300 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#000B58] transition-all"
                />
              </div>

              <!-- Botón Eliminar fila (✕) -->
              <div class="col-span-1 text-center">
                <button 
                  type="button"
                  @click="eliminarFilaActividad(index)"
                  title="Eliminar fila"
                  class="hover:opacity-70 transition-opacity p-1 cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35" fill="none">
                    <path d="M17.5 15.9585L8.46858 6.927L6.92712 8.46846L15.9586 17.4999L6.92712 26.5314L8.46858 28.0728L17.5 19.0414L26.5315 28.0728L28.073 26.5314L19.0415 17.4999L28.073 8.46846L26.5315 6.927L17.5 15.9585Z" fill="#A8A8A8"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- BOTÓN AGREGAR FILA (+) -->
          <div class="mt-4 flex items-center">
            <button 
              type="button"
              @click="agregarFilaActividad"
              class="cursor-pointer transition-transform hover:scale-105 hover:opacity-90"
              title="Agregar otra actividad"
            >
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="38" height="38" viewBox="0 0 38 38" fill="none">
                <use xlink:href="#stroke0_add_act" fill="#010C67"/>
                <mask id="stroke1_add_act" fill="white">
                  <use xlink:href="#stroke0_add_act"/>
                </mask>
                <path d="M38 19.0516C38 14.6561 36.1233 10.4406 32.7828 7.33248C29.4423 4.22438 24.9117 2.47827 20.1875 2.47827C15.4633 2.47827 10.9327 4.22438 7.59216 7.33248C4.25167 10.4406 2.375 14.6561 2.375 19.0516C2.375 23.4471 4.25167 27.6626 7.59216 30.7707C10.9327 33.8788 15.4633 35.6249 20.1875 35.6249C24.9117 35.6249 29.4423 33.8788 32.7828 30.7707C36.1233 27.6626 38 23.4471 38 19.0516Z" stroke-width="2" stroke="white" mask="url(#stroke1_add_act)"/>
                <path d="M18.2202 11.6857C18.2202 11.1973 18.4287 10.7289 18.7999 10.3836C19.1711 10.0382 19.6745 9.84424 20.1994 9.84424C20.7243 9.84424 21.2277 10.0382 21.5989 10.3836C21.97 10.7289 22.1785 11.1973 22.1785 11.6857V26.4175C22.1785 26.9059 21.97 27.3743 21.5989 27.7197C21.2277 28.065 20.7243 28.259 20.1994 28.259C19.6745 28.259 19.1711 28.065 18.7999 27.7197C18.4287 27.3743 18.2202 26.9059 18.2202 26.4175V11.6857Z" fill="#F5F5F5"/>
                <path d="M28.1041 17.1695C28.629 17.1665 29.1337 17.3577 29.5071 17.701C29.8805 18.0443 30.092 18.5115 30.0952 18.9999C30.0983 19.4883 29.8928 19.9578 29.5239 20.3053C29.1549 20.6527 28.6528 20.8495 28.1279 20.8524L12.2945 20.9334C11.7696 20.9364 11.265 20.7452 10.8916 20.4019C10.5182 20.0586 10.3067 19.5914 10.3035 19.103C10.3004 18.6146 10.5059 18.1451 10.8748 17.7977C11.2437 17.4502 11.7459 17.2534 12.2708 17.2505L28.1041 17.1695Z" fill="#F5F5F5"/>
                <defs>
                  <path id="stroke0_add_act" d="M38 19.0516C38 14.6561 36.1233 10.4406 32.7828 7.33248C29.4423 4.22438 24.9117 2.47827 20.1875 2.47827C15.4633 2.47827 10.9327 4.22438 7.59216 7.33248C4.25167 10.4406 2.375 14.6561 2.375 19.0516C2.375 23.4471 4.25167 27.6626 7.59216 30.7707C10.9327 33.8788 15.4633 35.6249 20.1875 35.6249C24.9117 35.6249 29.4423 33.8788 32.7828 30.7707C36.1233 27.6626 38 23.4471 38 19.0516Z"/>
                </defs>
              </svg>
            </button>
          </div>

          <!-- ÁREA DE SUBIDA DE EVIDENCIAS FOTOGRÁFICAS -->
          <div class="mt-6">
            <input 
              id="input-evidencias-upload"
              type="file" 
              multiple 
              accept="image/jpeg,image/png,.jpg,.jpeg,.png"
              class="hidden"
              @change="handleFileUpload"
            />

            <div class="flex items-center gap-4 flex-wrap">
              <!-- TARJETA CON BORDE PUNTEADO PARA AGREGAR IMAGEN (Como en Captura 2) -->
              <div 
                @click="triggerInputFoto"
                class="w-32 h-32 border-2 border-dashed border-blue-400 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:bg-blue-50/40 transition-colors group relative"
                title="Haga clic para adjuntar evidencias fotográficas"
              >
                <!-- Icono de carga de imágenes personalizado -->
                <div class="transition-opacity group-hover:opacity-80">
                  <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 83 83" fill="none">
                    <path d="M41.0247 22.2217C41.0247 19.7525 40.5384 17.3074 39.5934 15.0261C38.6485 12.7448 37.2635 10.672 35.5174 8.92597C33.7714 7.17995 31.6986 5.79493 29.4173 4.85C27.136 3.90506 24.691 3.4187 22.2217 3.4187C19.7525 3.4187 17.3074 3.90506 15.0261 4.85C12.7448 5.79493 10.672 7.17995 8.92597 8.92597C7.17995 10.672 5.79493 12.7448 4.85 15.0261C3.90506 17.3074 3.4187 19.7525 3.4187 22.2217C3.4187 27.2086 5.39973 31.9912 8.92598 35.5174C12.4522 39.0437 17.2348 41.0247 22.2217 41.0247C27.2086 41.0247 31.9912 39.0437 35.5174 35.5174C39.0437 31.9912 41.0247 27.2086 41.0247 22.2217ZM23.9311 23.9311L23.9345 32.4916C23.9345 32.9449 23.7544 33.3797 23.4338 33.7003C23.1133 34.0208 22.6785 34.2009 22.2251 34.2009C21.7718 34.2009 21.337 34.0208 21.0164 33.7003C20.6959 33.3797 20.5158 32.9449 20.5158 32.4916V23.9311H11.9518C11.4985 23.9311 11.0637 23.751 10.7431 23.4304C10.4226 23.1098 10.2425 22.6751 10.2425 22.2217C10.2425 21.7684 10.4226 21.3336 10.7431 21.013C11.0637 20.6924 11.4985 20.5123 11.9518 20.5123H20.5123V11.9655C20.5123 11.5122 20.6924 11.0774 21.013 10.7568C21.3336 10.4363 21.7684 10.2562 22.2217 10.2562C22.6751 10.2562 23.1098 10.4363 23.4304 10.7568C23.751 11.0774 23.9311 11.5122 23.9311 11.9655V20.5123H32.4676C32.921 20.5123 33.3558 20.6924 33.6763 21.013C33.9969 21.3336 34.177 21.7684 34.177 22.2217C34.177 22.6751 33.9969 23.1098 33.6763 23.4304C33.3558 23.751 32.921 23.9311 32.4676 23.9311H23.9311ZM60.6824 15.3843H43.3734C42.7878 13.5784 41.9732 11.855 40.9495 10.2562H60.6824C63.6292 10.2562 66.4553 11.4268 68.539 13.5105C70.6227 15.5941 71.7933 18.4202 71.7933 21.367V60.6824C71.7933 63.6292 70.6227 66.4553 68.539 68.539C66.4553 70.6227 63.6292 71.7933 60.6824 71.7933H21.367C18.4202 71.7933 15.5941 70.6227 13.5105 68.539C11.4268 66.4553 10.2562 63.6292 10.2562 60.6824V40.9495C11.839 41.9649 13.5621 42.7819 15.3843 43.3734V60.6824C15.3865 61.3958 15.5039 62.0727 15.7364 62.7131L35.6436 43.223C37.0083 41.887 38.8211 41.1065 40.7294 41.0332C42.6377 40.9599 44.5051 41.5991 45.9682 42.8264L46.4058 43.223L66.3096 62.7166C66.5421 62.0784 66.6606 61.4003 66.6652 60.6824V21.367C66.6652 19.7803 66.0349 18.2586 64.9129 17.1366C63.7909 16.0146 62.2691 15.3843 60.6824 15.3843ZM62.6653 66.3267L42.8196 46.8878C42.3866 46.4635 41.8173 46.2063 41.2127 46.1619C40.6082 46.1174 40.0074 46.2886 39.5171 46.6451L39.2299 46.8844L19.3773 66.3267C20.0018 66.5478 20.6651 66.6606 21.367 66.6652H60.6824C61.3764 66.6652 62.0465 66.5455 62.6653 66.3267ZM52.1458 22.2217C54.1877 22.2217 56.146 23.0329 57.5898 24.4767C59.0337 25.9205 59.8448 27.8788 59.8448 29.9207C59.8448 31.9626 59.0337 33.9208 57.5898 35.3647C56.146 36.8085 54.1877 37.6197 52.1458 37.6197C50.104 37.6197 48.1457 36.8085 46.7018 35.3647C45.258 33.9208 44.4469 31.9626 44.4469 29.9207C44.4469 27.8788 45.258 25.9205 46.7018 24.4767C48.1457 23.0329 50.104 22.2217 52.1458 22.2217ZM52.1458 27.3498C51.464 27.3498 50.8101 27.6207 50.328 28.1028C49.8458 28.5849 49.575 29.2388 49.575 29.9207C49.575 30.6025 49.8458 31.2564 50.328 31.7386C50.8101 32.2207 51.464 32.4916 52.1458 32.4916C52.8277 32.4916 53.4816 32.2207 53.9637 31.7386C54.4459 31.2564 54.7167 30.6025 54.7167 29.9207C54.7167 29.2388 54.4459 28.5849 53.9637 28.1028C53.4816 27.6207 52.8277 27.3498 52.1458 27.3498Z" fill="black" fill-opacity="0.44"/>
                  </svg>
                </div>
              </div>

              <!-- PREVISUALIZACIÓN DE IMÁGENES CARGADAS -->
              <div 
                v-for="(foto, fIdx) in reporteForm.evidencias" 
                :key="fIdx"
                class="w-32 h-32 rounded-xl overflow-hidden border border-slate-200 relative group shadow-xs bg-slate-50"
              >
                <img :src="foto" alt="Evidencia" class="w-full h-full object-cover" />
                <button 
                  type="button"
                  @click.stop="eliminarEvidencia(fIdx)"
                  class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer shadow-md"
                  title="Eliminar fotografía"
                >
                  ✕
                </button>
              </div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">
              Formatos soportados: JPG y PNG. Máximo 6 evidencias fotográficas.
            </p>
          </div>

        </div>

        <!-- BOTONES DE ACCIÓN INFERIORES: CANCELAR + ENVIAR -->
        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-100">
          <!-- Botón Cancelar -->
          <button 
            type="button"
            @click="volverATabla"
            class="border border-[#000B58] text-[#000B58] hover:bg-slate-50 rounded-full text-sm font-semibold transition-all cursor-pointer shadow-xs flex flex-wrap items-center justify-center gap-[10px] px-[10px]"
            style="width:171px; height:41px;"
          >
            Cancelar
          </button>

          <!-- Botón Enviar -->
          <button 
            type="button"
            :disabled="enviando"
            @click="enviarReporte"
            class="bg-[#000B58] hover:bg-[#000840] text-white rounded-full text-sm font-semibold transition-all cursor-pointer shadow-md flex flex-wrap items-center justify-center gap-[10px] px-[10px]"
            style="width:171px; height:41px;"
          >
            <span v-if="enviando" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ enviando ? 'Enviando...' : 'Enviar' }}</span>
          </button>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VISTA 3: VISTA DE DETALLE / CONSULTA DEL REPORTE (Captura Figma)           -->
    <!-- ========================================================================= -->
    <div v-else-if="vistaActual === 'detalle' && reporteDetalle" class="pb-10">
      
      <!-- BARRA SUPERIOR: ← Reportes/Mes + BOTÓN EDITAR -->
      <div class="mb-4 flex items-center justify-between max-w-5xl mx-auto">
        <button 
          @click="volverATabla"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-800 hover:text-[#000B58] transition-colors cursor-pointer"
        >
          <svg class="w-4 h-4 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Reportes/{{ reporteDetalle.mes || 'Detalle' }}</span>
        </button>

        <!-- Botón Editar ✎ con bordes redondeados y borde fino -->
        <button
          type="button"
          @click="editarReporteDesdeDetalle"
          class="px-5 py-1.5 border border-slate-700 bg-white hover:bg-slate-50 text-slate-900 rounded-[10px] text-sm font-medium flex items-center gap-2 cursor-pointer shadow-xs transition-all"
        >
          <span>Editar</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
          </svg>
        </button>
      </div>

      <!-- TARJETA PRINCIPAL CON BORDE AZUL Y ESQUINAS REDONDEADAS -->
      <div class="relative bg-white rounded-[20px] border border-[#2563EB]/40 p-8 sm:p-12 shadow-sm max-w-5xl mx-auto">
        
        <!-- BADGE DE ESTADO EN LA ESQUINA SUPERIOR DERECHA DE LA TARJETA -->
        <div class="absolute top-8 right-8">
          <span 
            v-if="reporteDetalle.estado === 'En revisión' || reporteDetalle.estado === 'en_espera'"
            class="select-none"
            style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: var(--color-acento, #FFC071); align-items: center; justify-content: center; gap: 8px;"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none" class="shrink-0">
              <path d="M14.876 7.5C14.876 11.6421 11.5459 15 7.43802 15C3.33011 15 0 11.6421 0 7.5C0 3.35786 3.33011 0 7.43802 0C11.5459 0 14.876 3.35786 14.876 7.5Z" stroke="rgba(0, 0, 0, 0.66)" stroke-width="1.57895"/>
              <g transform="translate(2.44, 2.5)">
                <path d="M2.01025 4.82461L4.02053 6.83488L8.04108 2.81433" stroke-width="1.57895" stroke-linecap="round" stroke-linejoin="round" stroke="black" stroke-opacity="0.6"/>
              </g>
            </svg>
            <span class="text-black font-bold text-[13px] leading-none">En revisión</span>
          </span>

          <span 
            v-else-if="reporteDetalle.estado === 'Aprobado' || reporteDetalle.estado === 'aprobado'"
            class="select-none"
            style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: #00C853; align-items: center; justify-content: center; gap: 8px;"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none" class="shrink-0">
              <path d="M14.876 7.5C14.876 11.6421 11.5459 15 7.43802 15C3.33011 15 0 11.6421 0 7.5C0 3.35786 3.33011 0 7.43802 0C11.5459 0 14.876 3.35786 14.876 7.5Z" stroke="rgba(0, 0, 0, 0.66)" stroke-width="1.57895"/>
              <g transform="translate(2.44, 2.5)">
                <path d="M2.01025 4.82461L4.02053 6.83488L8.04108 2.81433" stroke-width="1.57895" stroke-linecap="round" stroke-linejoin="round" stroke="black" stroke-opacity="0.6"/>
              </g>
            </svg>
            <span class="text-black font-bold text-[13px] leading-none">Aprobado</span>
          </span>

          <span 
            v-else
            class="select-none"
            style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: #E2E8F0; align-items: center; justify-content: center; gap: 8px;"
          >
            <span class="text-slate-800 font-bold text-[13px] leading-none">{{ reporteDetalle.estado || 'Enviado' }}</span>
          </span>
        </div>

        <!-- ENCABEZADO: LOGO + TÍTULOS OFICIALES -->
        <div class="flex items-start gap-8 mb-8 pb-4">
          <!-- Logo UGB -->
          <div class="shrink-0">
            <img 
              src="/images/Logo_para_reportes_mensuales.png" 
              alt="Universidad Gerardo Barrios" 
              style="width: 123.482px; height: 100px; flex-shrink: 0; aspect-ratio: 123.48/100; object-fit: contain;"
              @error="(e) => e.target.style.display = 'none'"
            />
          </div>

          <!-- Títulos Oficiales de la Facultad -->
          <div class="flex-1 text-center pr-28">
            <h2 style="font-family:'Lora',serif; font-size:24px; font-weight:500; color:#010C67; text-align:center; line-height:normal;">
              FACULTAD DE CIENCIA Y TECNOLOGÍA
            </h2>
            <h3 class="text-sm font-semibold text-[#010C67] text-center mt-1">
              Trabajo de Graduación Modalidad Pasantía
            </h3>
            <p class="text-sm font-medium text-[#010C67] text-center mt-1">
              Reporte mensual
            </p>
          </div>
        </div>

        <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
        <div class="mb-6">
          <!-- FILA 1: Área de pasantía + Nombre del pasante + Mes -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-5">
            <div class="md:col-span-4">
              <span class="block text-sm font-bold text-slate-900 mb-1">Área de pasantía</span>
              <span class="text-sm text-slate-700">{{ reporteDetalle.area_pasantia || 'UX/UI' }}</span>
            </div>

            <div class="md:col-span-5">
              <span class="block text-sm font-bold text-slate-900 mb-1">Nombre del pasante</span>
              <span class="text-sm text-slate-700">{{ reporteDetalle.nombre_pasante || 'Dallana Lucía Campos García' }}</span>
            </div>

            <div class="md:col-span-3">
              <span class="block text-sm font-bold text-slate-900 mb-1">Mes</span>
              <span class="text-sm text-slate-700">{{ reporteDetalle.mes || 'Enero' }}</span>
            </div>
          </div>

          <!-- FILA 2: Fecha Inicio + Fecha Finalización con píldoras de calendario -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-5">
            <div class="md:col-span-4">
              <span class="block text-sm font-bold text-slate-900 mb-1.5">Fecha Inicio</span>
              <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 border border-[#3b82f6] rounded-[8px] bg-white text-[#2563eb] text-sm font-normal">
                <!-- Icono de calendario con trazo negro como en la captura -->
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black shrink-0">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>{{ formatearFecha(reporteDetalle.fecha_inicio) || '08/01/2026' }}</span>
              </div>
            </div>

            <div class="md:col-span-5">
              <span class="block text-sm font-bold text-slate-900 mb-1.5">Fecha Finalización</span>
              <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 border border-[#3b82f6] rounded-[8px] bg-white text-[#2563eb] text-sm font-normal">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black shrink-0">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>{{ formatearFecha(reporteDetalle.fecha_fin) || '08/27/2026' }}</span>
              </div>
            </div>
          </div>

          <!-- FILA 3: Objetivo -->
          <div class="mb-6">
            <span class="block text-sm font-bold text-slate-900 mb-1">Objetivo</span>
            <p class="text-sm text-slate-700 leading-relaxed">
              {{ reporteDetalle.objetivo || 'documentar las actividades desarrolladas en la empresa' }}
            </p>
          </div>
        </div>

        <!-- SECCIÓN 2: ACTIVIDADES REALIZADAS -->
        <div class="mb-6">
          <h4 class="text-sm font-bold text-slate-900 mb-3">Actividades realizadas</h4>
          <div class="w-full overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="border-b border-slate-300 text-slate-900 font-bold">
                <tr>
                  <th class="py-2.5 pr-4 w-12 text-left font-bold">Nº</th>
                  <th class="py-2.5 px-4 w-1/4 text-left font-bold">Actividad</th>
                  <th class="py-2.5 px-4 w-32 text-left font-bold whitespace-nowrap">Fecha</th>
                  <th class="py-2.5 pl-4 text-left font-bold">Resultado de aprendizaje</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="(act, idx) in (reporteDetalle.actividades || [])" 
                  :key="idx"
                  class="align-top"
                >
                  <td class="py-4 pr-4 font-normal text-slate-600">{{ act.numero ?? (idx + 1) }}</td>
                  <td class="py-4 px-4 font-normal text-slate-600 leading-relaxed">{{ act.actividad || act.actividad_realizada }}</td>
                  <td class="py-4 px-4 font-normal text-slate-600 whitespace-nowrap">{{ formatearFecha(act.fecha || act.fecha_actividad) }}</td>
                  <td class="py-4 pl-4 font-normal text-slate-600 leading-relaxed">{{ act.resultado_aprendizaje || act.logros_obtenidos }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- GALERÍA DE EVIDENCIAS FOTOGRÁFICAS DEBAJO DE LA TABLA -->
        <div v-if="reporteDetalle.evidencias && reporteDetalle.evidencias.length > 0" class="flex flex-wrap gap-4 mt-2">
          <div 
            v-for="(foto, pIdx) in reporteDetalle.evidencias" 
            :key="pIdx"
            @click="imagenLightbox = foto"
            class="w-36 h-36 rounded-[8px] overflow-hidden border border-slate-200 cursor-pointer hover:shadow-md transition-all shrink-0"
          >
            <img :src="foto" alt="Evidencia" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
          </div>
        </div>

      </div>

      <!-- BOTÓN INFERIOR: ACEPTAR (Abajo a la derecha) -->
      <div class="max-w-5xl mx-auto flex justify-end mt-6">
        <button 
          type="button"
          @click="volverATabla"
          class="w-[140px] py-2.5 rounded-[12px] bg-[#000854] hover:bg-[#00063d] text-white text-sm font-medium transition-all shadow-md cursor-pointer text-center"
        >
          Aceptar
        </button>
      </div>

    </div>

    <!-- LIGHTBOX PARA VER IMAGEN DE EVIDENCIA EN TAMAÑO COMPLETO -->
    <div 
      v-if="imagenLightbox"
      @click="imagenLightbox = null"
      class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-xs cursor-pointer"
    >
      <div class="relative max-w-3xl max-h-[90vh]">
        <img :src="imagenLightbox" alt="Evidencia Ampliada" class="max-w-full max-h-[85vh] rounded-xl shadow-2xl object-contain" />
        <button 
          @click="imagenLightbox = null"
          class="absolute -top-10 right-0 text-white font-bold text-lg bg-black/40 rounded-full w-8 h-8 flex items-center justify-center hover:bg-black/80 transition-colors"
        >
          ✕
        </button>
      </div>
    </div>

  </div>
</template>
<style>
@import url('https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&display=swap');
</style>
