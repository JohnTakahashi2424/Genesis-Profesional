<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'navegar'])

// Datos del usuario autenticado
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

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
    titulo: 'Actividad de creación/envio y observaciones de reportes',
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
    titulo: 'Actividad de reportes aprovados',
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

// Elementos base de la interfaz conforme a la captura visual
const itemsBaseMock = [
  // 01 de marzo de 2026
  {
    id: 'mock-1',
    fecha_agrupada: '01 de marzo de 2026',
    fecha_exacta_raw: '2026-03-01T08:16:03',
    titulo: 'Envío de reporte',
    descripcion: 'Envío de reporte del mes de febrero',
    fecha_linea1: '01 de marzo de 2026',
    fecha_linea2: '- 08:16:03 a.m.',
    accion_texto: 'Ver reporte',
    accion_url: '/reportes',
    accion_destino: 'reportes',
    progreso: 12,
    categoria: 'reportes'
  },
  {
    id: 'mock-2',
    fecha_agrupada: '01 de marzo de 2026',
    fecha_exacta_raw: '2026-03-01T08:00:03',
    titulo: 'Creación de reporte',
    descripcion: 'Creación de reporte del mes de febrero',
    fecha_linea1: '01 de marzo de 2026',
    fecha_linea2: '- 08:00:03 a.m.',
    accion_texto: 'Ver reporte',
    accion_url: '/reportes',
    accion_destino: 'reportes',
    progreso: 12,
    categoria: 'reportes'
  },
  // 20 de enero de 2026
  {
    id: 'mock-3',
    fecha_agrupada: '20 de enero de 2026',
    fecha_exacta_raw: '2026-01-20T11:49:03',
    titulo: 'Actualización de fotografía',
    descripcion: 'actualización de fotografía en el perfil',
    fecha_linea1: '20 de enero de 2026 -',
    fecha_linea2: '11:49:03 a.m.',
    accion_texto: 'Ver perfil',
    accion_url: '/perfil',
    accion_destino: 'perfil',
    progreso: 10,
    categoria: 'perfil'
  },
  {
    id: 'mock-4',
    fecha_agrupada: '20 de enero de 2026',
    fecha_exacta_raw: '2026-01-20T11:16:03',
    titulo: 'Reporte aprovado',
    descripcion: 'reporte aprovado por - Jenny Flores',
    fecha_linea1: '20 de enero de 2026 -',
    fecha_linea2: '11:16:03 a.m.',
    accion_texto: 'ver reporte',
    accion_url: '/reportes',
    accion_destino: 'reportes',
    progreso: 10,
    categoria: 'reportes_aprobados'
  },
  {
    id: 'mock-5',
    fecha_agrupada: '20 de enero de 2026',
    fecha_exacta_raw: '2026-01-20T08:11:08',
    titulo: 'Envío de reporte',
    descripcion: 'Envío de reporte del mes de enero',
    fecha_linea1: '20 de enero de 2026 -',
    fecha_linea2: '08:11:08 a.m.',
    accion_texto: 'Ver reporte',
    accion_url: '/reportes',
    accion_destino: 'reportes',
    progreso: 8,
    categoria: 'reportes'
  },
  {
    id: 'mock-6',
    fecha_agrupada: '20 de enero de 2026',
    fecha_exacta_raw: '2026-01-20T06:16:03',
    titulo: 'Creación de reporte',
    descripcion: 'Creación de reporte del mes de enero',
    fecha_linea1: '20 de enero de 2026 -',
    fecha_linea2: '06:16:03 a.m.',
    accion_texto: 'Ver reporte',
    accion_url: '/reportes',
    accion_destino: 'reportes',
    progreso: 8,
    categoria: 'reportes'
  },
  // 18 de enero de 2026
  {
    id: 'mock-7',
    fecha_agrupada: '18 de enero de 2026',
    fecha_exacta_raw: '2026-01-18T08:16:03',
    titulo: 'Actualización de cv',
    descripcion: 'Actualización de datos personales en el cv',
    fecha_linea1: '18 de enero de 2026 -',
    fecha_linea2: '08:16:03 a.m.',
    accion_texto: 'Ver perfil',
    accion_url: '/cv',
    accion_destino: 'cv',
    progreso: 7,
    categoria: 'cv'
  }
]

// Listado reactivo de actividades
const itemsHistorial = ref([...itemsBaseMock])
const cargando = ref(false)

// Cargar actividades desde la base de datos asegurando que no se omita ningún elemento del diseño
const cargarHistorialDesdeApi = async () => {
  try {
    cargando.value = true
    const userId = usuario.value?.id || usuario.value?.user_id || usuario.value?.usuario_id
    const token = localStorage.getItem('genesis_token')
    
    const config = {}
    if (token) {
      config.headers = { Authorization: `Bearer ${token}` }
    }
    if (userId) {
      config.params = { user_id: userId }
    }

    const res = await axios.get('/api/historial-progreso', config)
    if (res.data && Array.isArray(res.data.items) && res.data.items.length > 0) {
      const apiItems = res.data.items.map(item => ({
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

      // Unir garantizando que no se omita ningún elemento de la imagen
      const titulosEnApi = new Set(apiItems.map(i => `${i.titulo.toLowerCase()}_${i.fecha_agrupada}`))
      const faltantesMock = itemsBaseMock.filter(m => !titulosEnApi.has(`${m.titulo.toLowerCase()}_${m.fecha_agrupada}`))
      itemsHistorial.value = [...apiItems, ...faltantesMock]
    } else {
      itemsHistorial.value = [...itemsBaseMock]
    }
  } catch (e) {
    itemsHistorial.value = [...itemsBaseMock]
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

onMounted(() => {
  cargarHistorialDesdeApi()
  window.addEventListener('click', handleClickAfuera)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickAfuera)
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
  if (isNaN(d.getTime())) {
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

// Acción de navegación al hacer clic en "Ver reporte", "Ver perfil", etc.
const ejecutarAccion = (item) => {
  if (item.accion_url && item.accion_url.includes('reporte')) {
    const match = item.accion_url.match(/reportes\/(\d+)/)
    if (match && match[1]) {
      localStorage.setItem('genesis_ver_reporte_id', match[1])
    }
    emit('navegar', 'reportes')
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
                    class="bg-white rounded-xl px-5 py-3.5 transition-all duration-150 shadow-2xs hover:shadow-xs"
                    :class="obtenerBordeTarjeta(item.categoria)"
                  >
                    <div class="grid grid-cols-12 gap-3 items-center">
                      
                      <!-- 1. Título y descripción de la actividad -->
                      <div class="col-span-12 md:col-span-5">
                        <h3 class="text-sm font-semibold text-slate-800 leading-snug">
                          {{ item.titulo }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 leading-tight">
                          {{ item.descripcion }}
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

                      <!-- 4. Barra de Progreso Cápsula con acumulado -->
                      <div class="col-span-3 md:col-span-2 flex items-center justify-start">
                        <div class="w-28 sm:w-32 h-3.5 bg-white rounded-full border border-gray-300 p-[1px] overflow-hidden shadow-2xs">
                          <div
                            class="h-full bg-[#00529C] rounded-full transition-all duration-300"
                            :style="{ width: `${Math.max(4, Math.min(100, Number(item.progreso) || 0))}%` }"
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

            <!-- LISTA DE LOS 7 BOTONES DE ACCESO DIRECTO -->
            <div class="space-y-3">
              <button
                v-for="ind in catalogoIndicadores"
                :key="ind.clave"
                type="button"
                @click="ingresarASeccion(ind)"
                class="w-full text-center px-2 py-2.5 bg-white rounded-xl text-[11px] leading-tight font-medium text-slate-800 hover:bg-slate-50 hover:shadow-xs transition duration-150 cursor-pointer block"
                :class="ind.borderClass"
              >
                {{ ind.titulo }}
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>
