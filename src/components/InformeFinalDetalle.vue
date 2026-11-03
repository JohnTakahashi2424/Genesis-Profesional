<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  informe: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['volver', 'ver-reporte'])

// Textos por defecto según la maqueta oficial si no vienen en la base de datos
const defaultPresentacion = 'Por medio de la presente, pongo a su disposición el Reporte Final de Prácticas Profesionales, el cual documenta la labor desarrollada durante mi periodo como pasante de desarrollo de software. Durante este proceso me integré en el ciclo de vida del proyecto de la empresa, participando activamente en la definición de requerimientos, la arquitectura de base de datos, el diseño de interfaz de usuario (UI/UX) y la implementación de módulos funcionales mediante el uso de herramientas de desarrollo web y herramientas de control de versiones.'

const defaultGeneralidades = 'El presente reporte detalla las actividades desarrolladas durante el periodo de prácticas profesionales en el área de desarrollo de software. El objetivo principal consistió en aplicar conocimientos teóricos y técnicos en un entorno real de trabajo, participando en el análisis de requerimientos, diseño de bases de datos, maquetación de interfaces de usuario y programación de módulos web, garantizando la calidad y funcionalidad de las soluciones implementadas.'

const defaultProcedimiento = 'El proceso de pasantía se estructuró en cuatro fases consecutivas: en primer lugar, la inducción al entorno tecnológico y asignación de requerimientos; en segundo lugar, el análisis y diseño mediante la creación de diagramas de base de datos y prototipos UI/UX; en tercer lugar, la fase de codificación utilizando controladores, vistas y APIs para la lógica de negocio; y finalmente, las pruebas de integración, optimización de componentes y documentación técnica del código desarrollado.'

const defaultDescripcionEmpresa = 'CASS es una organización enfocada en ofrecer soluciones tecnológicas e innovadoras para la gestión operativa y administrativa. La entidad promueve un entorno colaborativo basado en la mejora continua, apoyándose en herramientas digitales modernas para optimizar sus procesos internos y brindar servicios eficientes y de alta calidad a sus usuarios.'

const defaultDescripcionPasantia = 'La pasantía consistió en el desempeño como desarrolladora de software, colaborando activamente en el ciclo completo de creación de aplicaciones web. Las responsabilidades abarcaron el análisis de requerimientos, el diseño de la base de datos, el desarrollo de la interfaz de usuario (UI/UX) y la programación de módulos funcionales, asegurando un código mantenible y adaptado a las necesidades institucionales.'

// Columnas del presupuesto
const presupuestoColumnas = computed(() => {
  if (props.informe?.presupuesto_columnas && Array.isArray(props.informe.presupuesto_columnas) && props.informe.presupuesto_columnas.length > 0) {
    return props.informe.presupuesto_columnas
  }
  return ['Ene.', 'Feb.', 'Mar.', 'Abr.', 'May.', 'Junio', 'Jul.']
})

// Items del presupuesto
const presupuestoItems = computed(() => {
  if (props.informe?.presupuesto_items && Array.isArray(props.informe.presupuesto_items) && props.informe.presupuesto_items.length > 0) {
    return props.informe.presupuesto_items
  }
  return [
    {
      gasto: 'Comida',
      montos: { 'Ene.': 25, 'Feb.': 30, 'Mar.': 30, 'Abr.': 20, 'May.': 25, 'Junio': 21, 'Jul.': 18 },
      total: 172
    },
    {
      gasto: 'Pasaje',
      montos: { 'Ene.': 15, 'Feb.': 15, 'Mar.': 10, 'Abr.': 12, 'May.': 11, 'Junio': 12, 'Jul.': 9 },
      total: 84
    }
  ]
})

// Total consolidado del presupuesto
const granTotalPresupuesto = computed(() => {
  if (props.informe?.presupuesto_total) {
    return props.informe.presupuesto_total
  }
  let sum = 0
  presupuestoItems.value.forEach(item => {
    sum += Number(item.total) || 0
  })
  return sum || 256
})

// Subtítulo del presupuesto
const subtituloPresupuesto = computed(() => {
  if (presupuestoColumnas.value.length === 0) return 'Presupuesto'
  const primerMes = presupuestoColumnas.value[0]
  const ultimoMes = presupuestoColumnas.value[presupuestoColumnas.value.length - 1]
  return `Presupuesto de ${primerMes.toLowerCase()} a ${ultimoMes.toLowerCase()}`
})

// Mapa y función de ordenamiento de mes mayor a menor
const mapaMeses = {
  enero: 1, ene: 1, ener: 1,
  febrero: 2, feb: 2, febr: 2,
  marzo: 3, mar: 3,
  abril: 4, abr: 4,
  mayo: 5, may: 5,
  junio: 6, jun: 6,
  julio: 7, jul: 7,
  agosto: 8, ago: 8, agost: 8,
  septiembre: 9, sept: 9, sep: 9, setiembre: 9,
  octubre: 10, oct: 10,
  noviembre: 11, nov: 11,
  diciembre: 12, dic: 12
}

const obtenerScoreMes = (item) => {
  if (!item) return 0
  let anio = 2026
  let mesNum = 0

  if (item.fecha_inicio) {
    const limpio = String(item.fecha_inicio).split('T')[0]
    const partes = limpio.split('-')
    if (partes.length >= 2) {
      anio = parseInt(partes[0], 10) || 2026
      mesNum = parseInt(partes[1], 10) || 0
    }
  }

  if (!mesNum && item.mes) {
    const mesLimpio = String(item.mes).toLowerCase().trim()
    for (const [clave, val] of Object.entries(mapaMeses)) {
      if (mesLimpio.startsWith(clave)) {
        mesNum = val
        break
      }
    }
  }

  if (!mesNum && item.periodo) {
    const perLimpio = String(item.periodo).toLowerCase()
    for (const [clave, val] of Object.entries(mapaMeses)) {
      if (perLimpio.includes(clave)) {
        mesNum = val
        break
      }
    }
  }

  if (item.periodo) {
    const matchAnio = String(item.periodo).match(/\b(20\d{2})\b/)
    if (matchAnio) {
      anio = parseInt(matchAnio[1], 10)
    }
  }

  return (anio * 100) + mesNum
}

const ordenarPorMesMayorAMenor = (lista) => {
  if (!Array.isArray(lista)) return []
  return [...lista].sort((a, b) => {
    const scoreA = obtenerScoreMes(a)
    const scoreB = obtenerScoreMes(b)
    if (scoreB !== scoreA) {
      return scoreB - scoreA
    }
    return (Number(b.id) || 0) - (Number(a.id) || 0)
  })
}

// Reportes adjuntos desde la base de datos (ordenados de mes mayor a menor)
const reportesList = computed(() => {
  let lista = []
  if (props.informe?.reportes && Array.isArray(props.informe.reportes) && props.informe.reportes.length > 0) {
    lista = props.informe.reportes
  }
  return ordenarPorMesMayorAMenor(lista)
})

// Anexos desde la base de datos
const anexosList = computed(() => {
  if (props.informe?.anexos && Array.isArray(props.informe.anexos) && props.informe.anexos.length > 0) {
    return props.informe.anexos
  }
  return [
    { nombre: 'Archivo 1', tipo: 'archivo' },
    { nombre: 'Archivo 2', tipo: 'archivo' }
  ]
})

// Determina si un anexo es imagen para desplegarla directamente
const esImagen = (anx) => {
  if (!anx) return false
  if (anx.es_imagen === true || anx.es_imagen === 'true') return true
  const nombre = (anx.nombre || '').toLowerCase()
  const url = (anx.url || anx.preview || '').toLowerCase()
  const tipo = (anx.tipo || '').toLowerCase()

  if (url.startsWith('data:image/')) return true
  const extensionesImg = ['.png', '.jpg', '.jpeg', '.webp', '.gif', '.svg', '.bmp', '.avif']
  if (extensionesImg.some(ext => nombre.endsWith(ext) || url.includes(ext))) return true
  if (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'image'].includes(tipo) || tipo.startsWith('image/')) return true
  return false
}

// Lightbox para ampliar imágenes de los anexos
const imagenLightbox = ref(null)
const abrirLightbox = (src) => {
  if (src) imagenLightbox.value = src
}
const cerrarLightbox = () => {
  imagenLightbox.value = null
}

// Helpers para separar rango y año de fechas
const formatearRangoPeriodo = (periodo) => {
  if (!periodo) return '01 Ene – 27 Ene'
  const match = periodo.match(/^(.*?)(\s+\d{4})$/)
  return match ? match[1].trim() : periodo
}

const formatearAnioPeriodo = (periodo) => {
  if (!periodo) return '2026'
  const match = periodo.match(/\b(20\d{2})\b/)
  return match ? match[1] : '2026'
}

const verReporteMensual = (rep) => {
  emit('ver-reporte', rep)
}
</script>

<template>
  <div class="space-y-6 pb-16">
    
    <!-- ENLACE SUPERIOR: ← Reportes/ Informe final -->
    <div class="flex items-center justify-between max-w-5xl mx-auto">
      <button 
        @click="emit('volver')"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#010C67] transition cursor-pointer"
      >
        <span class="text-base font-bold">←</span>
        <span>Reportes/ Informe final</span>
      </button>
    </div>

    <!-- CONTENEDOR PRINCIPAL BLANCO CON BORDE AZUL CLARO Y LÍNEA DE SOMBRA DEFINIDA -->
    <div class="bg-white border border-[#93c5fd] rounded-2xl p-6 sm:p-10 shadow-[4px_4px_0px_0px_rgba(0,11,88,0.16)] max-w-5xl mx-auto overflow-hidden">
      
      <!-- ENCABEZADO INSTITUCIONAL UGB (Tamaño prominente y uniforme) -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-8 mb-8 pb-6 border-b border-slate-100">
        <!-- Logo UGB -->
        <div class="shrink-0 flex items-center justify-center">
          <img 
            src="/images/Logo_para_reportes_mensuales.png" 
            alt="Universidad Gerardo Barrios" 
            style="width: 240px; height: 192px; max-width: 100%; flex-shrink: 0; object-fit: contain;"
            @error="(e) => e.target.style.display = 'none'"
          />
        </div>
        <div class="text-center">
          <h2 style="font-family:'Lora',serif; font-size: 28px; font-weight: 500; color: #010C67; text-align: center; line-height: normal;">
            FACULTAD DE CIENCIA Y TECNOLOGÍA
          </h2>
          <p class="text-base font-semibold text-[#010C67] text-center mt-1">
            Trabajo de Graduación Modalidad Pasantía
          </p>
          <p class="text-sm font-medium text-[#1E3A8A] text-center mt-0.5">
            Ingeniería en Sistemas y Redes Informáticas
          </p>
        </div>
      </div>

      <!-- TÍTULO: Informe final -->
      <div class="mb-6">
        <h1 class="text-2xl font-serif font-bold text-gray-900 tracking-tight">
          Informe final
        </h1>
      </div>

      <!-- CAMPOS DE INFORMACIÓN PRINCIPALES (MODO LECTURA EDITORIAL SEGÚN MAQUETA) -->
      <div class="space-y-6">
        
        <!-- FILA 1: Nombre del pasante e Institución (2 Columnas) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 min-w-0">
          
          <!-- Nombre del pasante -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 mb-1">
              Nombre del pasante
            </h3>
            <p class="text-sm text-gray-700 font-normal">
              {{ informe.nombre_pasante || 'Dallana Lucia Campos Garcia' }}
            </p>
          </div>

          <!-- Institución dónde se realizó la pasantía -->
          <div>
            <h3 class="text-sm font-bold text-gray-900 mb-1">
              Institución dónde se realizó la pasantía
            </h3>
            <p class="text-sm text-gray-700 font-normal break-words break-all [overflow-wrap:anywhere]">
              {{ informe.institucion || 'Empresas CASS' }}
            </p>
          </div>

        </div>

        <!-- Presentación -->
        <div>
          <h3 class="text-sm font-bold text-gray-900 mb-1">
            Presentación
          </h3>
          <p class="text-sm text-gray-700 font-normal leading-relaxed text-justify break-words break-all [overflow-wrap:anywhere]">
            {{ informe.presentacion || defaultPresentacion }}
          </p>
        </div>

        <!-- Generalidades -->
        <div>
          <h3 class="text-sm font-bold text-gray-900 mb-1">
            Generalidades
          </h3>
          <p class="text-sm text-gray-700 font-normal leading-relaxed text-justify break-words break-all [overflow-wrap:anywhere]">
            {{ informe.generalidades || defaultGeneralidades }}
          </p>
        </div>

        <!-- Procedimiento para el proceso de la pasantía* -->
        <div>
          <h3 class="text-sm font-bold text-gray-900 mb-1">
            Procedimiento para el proceso de la pasantía<span class="text-red-500">*</span>
          </h3>
          <p class="text-sm text-gray-700 font-normal leading-relaxed text-justify break-words break-all [overflow-wrap:anywhere]">
            {{ informe.procedimiento_pasantia || defaultProcedimiento }}
          </p>
        </div>

        <!-- Descripción de la empresa -->
        <div>
          <h3 class="text-sm font-bold text-gray-900 mb-1">
            Descripción de la empresa
          </h3>
          <p class="text-sm text-gray-700 font-normal leading-relaxed text-justify break-words break-all [overflow-wrap:anywhere]">
            {{ informe.descripcion_empresa || defaultDescripcionEmpresa }}
          </p>
        </div>

        <!-- Descripción de la pasantía -->
        <div>
          <h3 class="text-sm font-bold text-gray-900 mb-1">
            Descripción de la pasantía
          </h3>
          <p class="text-sm text-gray-700 font-normal leading-relaxed text-justify break-words break-all [overflow-wrap:anywhere]">
            {{ informe.descripcion_pasantia || defaultDescripcionPasantia }}
          </p>
        </div>

        <!-- ===================================================================== -->
        <!-- SECCIÓN: REPORTES ADJUNTOS                                            -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <h3 class="text-sm font-bold text-gray-900 mb-3">
            Reportes adjuntos
          </h3>

          <!-- Encabezado de la tabla -->
          <div class="border border-blue-200 rounded-xl py-3 px-6 mb-3 text-xs sm:text-sm font-medium text-gray-500 grid grid-cols-12 gap-2 text-center items-center bg-white">
            <div class="col-span-3 text-left pl-2">Mes del reporte</div>
            <div class="col-span-3 text-center">Periodo</div>
            <div class="col-span-2 text-center">Actividades desarrolladas</div>
            <div class="col-span-2 text-center">Estado</div>
            <div class="col-span-2 text-center">Acciones</div>
          </div>

          <!-- Filas de reportes adjuntos -->
          <div class="space-y-3">
            <div 
              v-for="rep in reportesList" 
              :key="rep.id"
              class="border border-[#5ba4e5] rounded-xl px-6 py-3.5 grid grid-cols-12 gap-2 items-center bg-white shadow-[2px_2px_0px_0px_rgba(0,11,88,0.08)]"
            >
              <!-- Icono de documento + Mes -->
              <div class="col-span-3 flex items-center gap-3 pl-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-800 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                  <circle cx="12" cy="8" r="2.5"></circle>
                  <path d="M8 14a4 4 0 0 1 8 0"></path>
                  <line x1="8" y1="17" x2="16" y2="17"></line>
                  <line x1="8" y1="20" x2="16" y2="20"></line>
                </svg>
                <span class="font-medium text-sm text-gray-900">{{ rep.mes }}</span>
              </div>

              <!-- Periodo (Rango y Año) -->
              <div class="col-span-3 text-center text-xs sm:text-sm font-medium text-gray-900 leading-snug">
                <div>{{ formatearRangoPeriodo(rep.periodo) }}</div>
                <div>{{ formatearAnioPeriodo(rep.periodo) }}</div>
              </div>

              <!-- Actividades desarrolladas -->
              <div class="col-span-2 text-center text-sm font-medium text-gray-900">
                {{ rep.actividades_desarrolladas ?? (rep.actividades ? rep.actividades.length : 12) }}
              </div>

              <!-- Estado: Badge Verde -->
              <div class="col-span-2 flex justify-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#00C853] text-white shadow-2xs">
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span>{{ rep.estado || 'Aprobado' }}</span>
                </span>
              </div>

              <!-- Acciones: Ver reporte -->
              <div class="col-span-2 flex justify-center">
                <button 
                  @click="verReporteMensual(rep)"
                  type="button"
                  class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 hover:text-[#010C67] transition cursor-pointer"
                  title="Ver detalle del reporte mensual"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <span>Ver reporte</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SECCIÓN: ANEXOS (IMÁGENES REALES O ARCHIVOS DE ASPECTO GRIS)          -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <h3 class="text-base font-serif font-bold text-gray-900 mb-4">
            Anexos
          </h3>

          <!-- Grid de tarjetas de imagen / archivos -->
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6 max-w-2xl">
            <div 
              v-for="(anx, idx) in anexosList" 
              :key="idx"
              class="flex flex-col items-center justify-center p-2 rounded-2xl transition hover:opacity-95"
            >
              <!-- CASO 1: SI ES IMAGEN -> MOSTRAR LA IMAGEN COMO TAL -->
              <div 
                v-if="esImagen(anx)" 
                @click="abrirLightbox(anx.url || anx.preview)"
                class="w-32 h-40 sm:w-40 sm:h-48 rounded-xl overflow-hidden border border-gray-300 shadow-sm bg-gray-50 flex items-center justify-center cursor-pointer hover:shadow-md transition"
                title="Clic para ampliar imagen"
              >
                <img 
                  :src="anx.url || anx.preview" 
                  :alt="anx.nombre || 'Anexo'" 
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- CASO 2: SI ES ARCHIVO (NO IMAGEN) -> MOSTRAR LA IMAGEN CON ASPECTO GRIS (HOJA VECTORIAL) -->
              <div 
                v-else 
                class="flex flex-col items-center justify-center"
              >
                <svg class="w-32 h-40 sm:w-40 sm:h-48 text-[#8E939B] drop-shadow-xs" viewBox="0 0 160 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <!-- Hoja con esquina doblada tipo dog-ear -->
                  <path d="M12 0H112L160 48V188C160 194.627 154.627 200 148 200H12C5.37258 200 0 194.627 0 188V12C0 5.37258 5.37258 0 12 0Z" fill="currentColor"/>
                  <!-- Sol / Círculo blanco superior izquierdo -->
                  <circle cx="56" cy="112" r="14" fill="white"/>
                  <!-- Montaña / Pico izquierdo blanco -->
                  <path d="M28 166L66 116L104 166H28Z" fill="white"/>
                  <!-- Montaña / Pico derecho blanco -->
                  <path d="M86 166L116 128L146 166H86Z" fill="white"/>
                </svg>
              </div>

              <!-- Nombre del archivo o enlace de descarga -->
              <a 
                v-if="anx.url && !anx.url.startsWith('data:')" 
                :href="anx.url" 
                target="_blank" 
                download
                class="mt-2 text-xs font-semibold text-blue-700 hover:underline truncate max-w-[140px] text-center block"
                :title="anx.nombre"
              >
                {{ anx.nombre }}
              </a>
              <span 
                v-else
                class="mt-2 text-xs font-semibold text-gray-800 truncate max-w-[140px] text-center block"
                :title="anx.nombre"
              >
                {{ anx.nombre }}
              </span>
            </div>
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- ===================================================================== -->
        <!-- SECCIÓN: PRESUPUESTO                                                  -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <div class="mb-1">
            <h3 class="text-base font-serif font-bold text-gray-900">
              Presupuesto
            </h3>
            <p class="text-xs sm:text-sm text-gray-700 mt-0.5 mb-3">
              {{ subtituloPresupuesto }}
            </p>
          </div>

          <!-- TABLA DEL PRESUPUESTO -->
          <div class="border border-gray-300 rounded-xl overflow-hidden shadow-xs bg-white">
            <div class="overflow-x-auto">
              <table class="w-full text-center border-collapse">
                <!-- Cabecera Azul Oscuro (#010C67) -->
                <thead>
                  <tr class="bg-[#010C67] text-white text-xs font-semibold">
                    <th class="py-3 px-4 text-left font-semibold min-w-[120px]">Gasto</th>
                    <th 
                      v-for="col in presupuestoColumnas" 
                      :key="col"
                      class="py-3 px-2 font-medium min-w-[55px]"
                    >
                      {{ col }}
                    </th>
                    <th class="py-3 px-4 font-semibold min-w-[85px]">Total USD</th>
                  </tr>
                </thead>

                <!-- Filas de Gastos -->
                <tbody class="divide-y divide-gray-200 text-sm">
                  <tr 
                    v-for="(item, idx) in presupuestoItems" 
                    :key="idx"
                    class="hover:bg-gray-50/50 transition"
                  >
                    <!-- Nombre del Gasto -->
                    <td class="py-3.5 px-4 text-left font-serif font-bold text-gray-900 text-sm sm:text-base">
                      {{ item.gasto }}
                    </td>

                    <!-- Montos por Columna/Mes en cuadritos redondeados -->
                    <td 
                      v-for="col in presupuestoColumnas" 
                      :key="col"
                      class="py-3.5 px-1.5 text-center"
                    >
                      <div class="w-11 h-8 sm:w-12 sm:h-9 mx-auto border border-gray-300 rounded-lg flex items-center justify-center text-xs sm:text-sm font-medium text-gray-800 bg-white shadow-2xs">
                        {{ item.montos?.[col] ?? 0 }}
                      </div>
                    </td>

                    <!-- Total de la Fila -->
                    <td class="py-3.5 px-3 text-center">
                      <div class="inline-flex items-center justify-center min-w-[70px] h-8 sm:h-9 px-2 text-xs sm:text-sm font-semibold text-gray-800 border border-gray-300 rounded-lg bg-gray-50/50 shadow-2xs">
                        {{ item.total ?? 0 }}
                      </div>
                    </td>
                  </tr>

                  <!-- Fila de Total General -->
                  <tr class="border-t border-gray-300 bg-white">
                    <td class="py-4 px-4 text-left font-serif font-bold text-gray-900 text-base sm:text-lg">
                      Total
                    </td>
                    <td :colspan="presupuestoColumnas.length"></td>
                    <td class="py-4 px-3 text-center">
                      <div class="inline-flex items-center justify-center min-w-[85px] h-10 px-3 text-sm font-bold text-gray-900 border border-gray-300 rounded-lg bg-white shadow-2xs">
                        {{ granTotalPresupuesto }}
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- BOTÓN INFERIOR DERECHO: CERRAR (EXACTO A LA CAPTURA) -->
    <div class="flex items-center justify-end max-w-5xl mx-auto pt-4">
      <button 
        @click="emit('volver')"
        type="button"
        class="px-9 py-2 rounded-lg bg-[#010C67] hover:bg-[#000840] text-white font-medium text-sm transition cursor-pointer shadow-sm"
      >
        Cerrar
      </button>
    </div>

  </div>
</template>
