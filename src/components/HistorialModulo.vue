<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'navegar'])

// Datos del usuario autenticado
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Estados de control: POR DEFECTO AMBOS CERRADOS
const dropdownFiltroAbierto = ref(false)
const indicadoresAbierto = ref(false)

// Criterio de ordenamiento: 'recientes' | 'antiguos'
const ordenSeleccionado = ref('recientes')

// Filtro de categoría seleccionado ('todos' o la clave)
const filtroCategoria = ref('todos')

// Catálogo de indicadores con sus colores y estilos idénticos a la maqueta
const catalogoIndicadores = [
  {
    clave: 'cv',
    titulo: 'Actividad de creación / actualización de CV',
    borderClass: 'border-[#9E2A2B]',
    textClass: 'text-[#9E2A2B]',
    activeBg: 'bg-[#9E2A2B]/10',
    cardBorder: 'border-[#9E2A2B]'
  },
  {
    clave: 'reportes',
    titulo: 'Actividad de creación/envío y observaciones de reportes',
    borderClass: 'border-[#1E3A8A]',
    textClass: 'text-[#1E3A8A]',
    activeBg: 'bg-[#1E3A8A]/10',
    cardBorder: 'border-[#1E3A8A]'
  },
  {
    clave: 'anexos',
    titulo: 'Actividad de anexos a reportes',
    borderClass: 'border-[#C2410C]',
    textClass: 'text-[#C2410C]',
    activeBg: 'bg-[#C2410C]/10',
    cardBorder: 'border-[#C2410C]'
  },
  {
    clave: 'reportes_rechazados',
    titulo: 'Actividad de reportes rechazados',
    borderClass: 'border-[#DC2626]',
    textClass: 'text-[#DC2626]',
    activeBg: 'bg-[#DC2626]/10',
    cardBorder: 'border-[#DC2626]'
  },
  {
    clave: 'reportes_aprobados',
    titulo: 'Actividad de reportes aprobados',
    borderClass: 'border-[#16A34A]',
    textClass: 'text-[#16A34A]',
    activeBg: 'bg-[#16A34A]/10',
    cardBorder: 'border-[#16A34A]'
  },
  {
    clave: 'cambio_estado',
    titulo: 'Actividad de cambio de estado',
    borderClass: 'border-[#0284C7]',
    textClass: 'text-[#0284C7]',
    activeBg: 'bg-[#0284C7]/10',
    cardBorder: 'border-[#0284C7]'
  },
  {
    clave: 'perfil',
    titulo: 'Actividad de actualización de perfil',
    borderClass: 'border-[#9CA3AF]',
    textClass: 'text-[#6B7280]',
    activeBg: 'bg-gray-100',
    cardBorder: 'border-[#9CA3AF]'
  }
]

const itemsHistorial = ref([])
const cargando = ref(false)

// Cargar actividades reales desde la base de datos a través de la API
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
    if (res.data && Array.isArray(res.data.items)) {
      itemsHistorial.value = res.data.items.map(item => ({
        id: item.id,
        fecha_agrupada: item.fecha_agrupada || item.fecha,
        fecha_exacta_raw: item.fecha_exacta || item.created_at,
        titulo: item.titulo,
        descripcion: item.descripcion,
        fecha_exacta_formato: item.fecha_hora_formateada || item.fecha_exacta,
        accion_texto: item.accion_texto || 'Ver detalle',
        accion_destino: item.accion_url?.includes('perfil') ? 'perfil' : (item.accion_url?.includes('cv') ? 'cv' : 'reportes'),
        progreso: Number(item.progreso) || 0,
        categoria: item.categoria || 'reportes'
      }))
    } else {
      itemsHistorial.value = []
    }
  } catch (e) {
    console.error('Error al cargar historial de progreso:', e?.message || '')
  } finally {
    cargando.value = false
  }
}

// Cerrar desplegables al hacer clic afuera
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

// Aplicar filtros por categoría y ordenamiento (recientes/antiguos)
const itemsProcesados = computed(() => {
  let list = [...itemsHistorial.value]

  // 1. Filtro por indicador/categoría
  if (filtroCategoria.value && filtroCategoria.value !== 'todos') {
    list = list.filter(item => item.categoria === filtroCategoria.value)
  }

  // 2. Ordenamiento: 'recientes' (descendente) o 'antiguos' (ascendente)
  list.sort((a, b) => {
    const timeA = new Date(a.fecha_exacta_raw || 0).getTime()
    const timeB = new Date(b.fecha_exacta_raw || 0).getTime()
    return ordenSeleccionado.value === 'antiguos' ? (timeA - timeB) : (timeB - timeA)
  })

  return list
})

// Agrupamiento dinámico por fecha (Ej: "28 de octubre de 2026")
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

// Obtener el color del borde de una tarjeta según su categoría
const obtenerBordeTarjeta = (categoria) => {
  const ind = catalogoIndicadores.find(i => i.clave === categoria)
  return ind ? ind.cardBorder : 'border-gray-300'
}

// Acción de clic en "Ver reporte", "Ver perfil", etc.
const ejecutarAccion = (item) => {
  if (item.accion_destino) {
    emit('navegar', item.accion_destino)
  }
}

// Alternar filtro de indicador desde el panel lateral
const toggleFiltroIndicador = (clave) => {
  if (filtroCategoria.value === clave) {
    filtroCategoria.value = 'todos'
  } else {
    filtroCategoria.value = clave
  }
}

// Seleccionar ordenamiento desde el menú "Filtrar"
const seleccionarOrden = (orden) => {
  ordenSeleccionado.value = orden
  dropdownFiltroAbierto.value = false
}
</script>

<template>
  <div class="space-y-6">
    
    <!-- ENCABEZADO DE COLUMNAS SUPERIOR (CALCADO A LA CAPTURA) -->
    <div class="flex items-center justify-between px-6 pt-2">
      <!-- Títulos de Columnas alineadas con la lista -->
      <div class="grid grid-cols-12 gap-4 flex-1 max-w-4xl text-sm font-semibold text-gray-800">
        <div class="col-span-5 md:col-span-4 pl-2">Fecha</div>
        <div class="col-span-4 md:col-span-4 text-center">Día y hora exacto</div>
        <div class="col-span-2 md:col-span-2 text-center">Acción</div>
        <div class="col-span-1 md:col-span-2 text-center pr-4">Progreso</div>
      </div>

      <!-- DESPLEGABLE FILTRAR SUPERIOR DERECHO (POR DEFECTO CERRADO) -->
      <div id="contenedor-filtro-dropdown" class="relative shrink-0 ml-4">
        <button
          @click.stop="dropdownFiltroAbierto = !dropdownFiltroAbierto"
          class="flex items-center gap-3 px-5 py-2 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-800 shadow-xs hover:bg-gray-50 transition cursor-pointer"
        >
          <span>Filtrar</span>
          <!-- Flecha hacia arriba cuando está abierto, hacia abajo cuando está cerrado -->
          <svg 
            class="w-4 h-4 text-gray-600 transition-transform duration-200" 
            :class="{ 'rotate-180': dropdownFiltroAbierto }" 
            viewBox="0 0 20 20" 
            fill="currentColor"
          >
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Menú de opciones de filtrado (Antiguos primero / Recientes primero) -->
        <div
          v-if="dropdownFiltroAbierto"
          class="absolute right-0 mt-1.5 w-48 bg-white border border-gray-200 rounded-xl shadow-lg z-30 py-1.5 overflow-hidden"
        >
          <button
            @click="seleccionarOrden('antiguos')"
            class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
            :class="ordenSeleccionado === 'antiguos' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
          >
            <span>Antiguos primero</span>
            <span v-if="ordenSeleccionado === 'antiguos'" class="text-[#000B58]">✓</span>
          </button>
          
          <button
            @click="seleccionarOrden('recientes')"
            class="w-full text-left px-4 py-2.5 text-xs font-medium hover:bg-gray-100 flex items-center justify-between transition cursor-pointer"
            :class="ordenSeleccionado === 'recientes' ? 'text-[#000B58] font-bold bg-blue-50' : 'text-gray-700'"
          >
            <span>Recientes primero</span>
            <span v-if="ordenSeleccionado === 'recientes'" class="text-[#000B58]">✓</span>
          </button>
        </div>
      </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL: TIMELINE (IZQUIERDA) + PANEL INDICADORES (DERECHA) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- ÁREA DE HISTORIAL / CRONOGRAMA DE ACTIVIDADES -->
      <div class="lg:col-span-9 bg-white border border-gray-200 rounded-2xl p-6 md:p-8 shadow-xs min-h-[580px]">
        
        <!-- Estado de carga -->
        <div v-if="cargando" class="flex flex-col items-center justify-center py-20 text-gray-500">
          <svg class="animate-spin h-8 w-8 text-[#000B58] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm">Cargando historial de actividades...</span>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="Object.keys(gruposPorFecha).length === 0" class="text-center py-20 text-gray-500">
          <p class="text-base font-medium">No se encontraron actividades registradas con el filtro seleccionado.</p>
          <button 
            v-if="filtroCategoria !== 'todos'"
            @click="filtroCategoria = 'todos'"
            class="mt-3 px-4 py-1.5 text-xs text-[#000B58] font-semibold underline cursor-pointer"
          >
            Mostrar todas las actividades
          </button>
        </div>

        <!-- Lista de actividades agrupada por fecha -->
        <div v-else class="space-y-8">
          <div 
            v-for="(items, fecha) in gruposPorFecha" 
            :key="fecha" 
            class="relative pl-6 before:content-[''] before:absolute before:left-0 before:top-2 before:bottom-0 before:w-[3px] before:bg-[#000B58]/30"
          >
            <!-- Título de la Fecha (Ej: 28 de octubre de 2026) -->
            <div class="flex items-center gap-3 mb-4 -ml-6">
              <div class="w-3 h-3 rounded-full bg-[#000B58] ring-4 ring-white shrink-0"></div>
              <h2 class="text-base md:text-lg font-bold text-[#000B58]">
                {{ fecha }}
              </h2>
            </div>

            <!-- Tarjetas de Actividades bajo esta fecha -->
            <div class="space-y-3.5">
              <div
                v-for="item in items"
                :key="item.id"
                class="bg-white border rounded-xl px-5 py-3.5 shadow-2xs transition-all duration-150 hover:shadow-xs hover:-translate-y-0.5"
                :class="obtenerBordeTarjeta(item.categoria)"
              >
                <div class="grid grid-cols-12 gap-3 items-center">
                  
                  <!-- 1. Título y descripción -->
                  <div class="col-span-12 md:col-span-5">
                    <h3 class="text-sm font-semibold text-gray-900 leading-snug">
                      {{ item.titulo }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5 leading-tight">
                      {{ item.descripcion }}
                    </p>
                  </div>

                  <!-- 2. Día y hora exacto -->
                  <div class="col-span-6 md:col-span-3 text-left md:text-center text-xs text-gray-600">
                    <span class="inline-block">{{ item.fecha_exacta_formato }}</span>
                  </div>

                  <!-- 3. Acción -->
                  <div class="col-span-3 md:col-span-2 text-center">
                    <button
                      @click="ejecutarAccion(item)"
                      class="text-xs font-normal text-gray-700 hover:text-[#000B58] underline underline-offset-2 transition cursor-pointer"
                    >
                      {{ item.accion_texto }}
                    </button>
                  </div>

                  <!-- 4. Barra de Progreso Cápsula -->
                  <div class="col-span-3 md:col-span-2 flex justify-end md:justify-center">
                    <div class="w-24 md:w-28 h-3.5 bg-gray-100 rounded-full border border-gray-300 p-[2px] flex items-center overflow-hidden">
                      <div
                        class="h-full bg-[#000B58] rounded-full transition-all duration-500"
                        :style="{ width: `${item.progreso}%` }"
                      ></div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- PANEL LATERAL DE INDICADORES (POR DEFECTO CERRADO) -->
      <aside class="lg:col-span-3 bg-white border border-gray-200 rounded-2xl p-4 shadow-xs transition-all duration-200">
        
        <!-- Cabecera del Panel Indicadores (Clic para abrir/cerrar) -->
        <button 
          @click="indicadoresAbierto = !indicadoresAbierto"
          class="w-full flex items-center justify-between text-sm font-semibold text-gray-900 cursor-pointer"
          :class="{ 'pb-3 border-b border-gray-200': indicadoresAbierto }"
        >
          <div class="flex items-center gap-2">
            <span>Indicadores</span>
            <!-- Indicador sutil de filtro activo si está colapsado -->
            <span 
              v-if="!indicadoresAbierto && filtroCategoria !== 'todos'"
              class="w-2 h-2 rounded-full bg-[#000B58]"
              title="Filtro de indicador activo"
            ></span>
          </div>

          <!-- Flecha hacia arriba cuando está abierto, hacia abajo cuando está cerrado -->
          <svg 
            class="w-4 h-4 text-gray-500 transition-transform duration-200" 
            :class="{ 'rotate-180': !indicadoresAbierto }" 
            viewBox="0 0 20 20" 
            fill="currentColor"
          >
            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Lista de Indicadores con sus bordes correspondientes (Desplegable) -->
        <div v-show="indicadoresAbierto" class="mt-4 space-y-2.5">
          <button
            v-for="ind in catalogoIndicadores"
            :key="ind.clave"
            @click="toggleFiltroIndicador(ind.clave)"
            class="w-full text-center p-2 rounded-xl border text-[11px] leading-tight font-medium transition cursor-pointer"
            :class="[
              ind.borderClass,
              filtroCategoria === ind.clave ? ind.activeBg + ' ring-2 ring-offset-1 ' + ind.borderClass : 'bg-white hover:bg-gray-50',
              ind.textClass
            ]"
          >
            {{ ind.titulo }}
          </button>

          <!-- Botón restablecer filtro si hay uno activo -->
          <div v-if="filtroCategoria !== 'todos'" class="pt-2 text-center">
            <button
              @click="filtroCategoria = 'todos'"
              class="text-xs text-gray-500 hover:text-[#000B58] underline cursor-pointer"
            >
              Limpiar filtro activo
            </button>
          </div>
        </div>

      </aside>

    </div>

  </div>
</template>
