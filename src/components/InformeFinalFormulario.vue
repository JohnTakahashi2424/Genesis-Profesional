<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver', 'guardado'])

// Usuario autenticado en sesión
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Estado de carga y envío
const cargando = ref(true)
const enviando = ref(false)
const mensajeError = ref('')
const toastExitoVisible = ref(false)

// Nombre del estudiante autenticado desde sesión
const nombreInicial = usuario.value?.nombres
  ? `${usuario.value.nombres} ${usuario.value.apellidos || ''}`.trim()
  : (usuario.value?.nombre || 'Dallana Lucia Campos Garcia')

// Campos del formulario principal
const form = ref({
  nombre_pasante: nombreInicial || 'Dallana Lucia Campos Garcia',
  institucion: '',
  presentacion: '',
  generalidades: '',
  procedimiento_pasantia: '',
  descripcion_empresa: '',
  descripcion_pasantia: '',
})

// Control de validación visual al enviar
const intentoEnvio = ref(false)

// ----------------------------------------------------
// SECCIÓN 1: Reportes Mensuales para Adjuntar (Ordenados de mes mayor a menor)
// ----------------------------------------------------
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

const reportesDisponibles = ref([
  {
    id: 1,
    mes: 'Enero',
    periodo: '01 Ene – 27 Ene 2026',
    actividades_desarrolladas: 12,
    estado: 'Aprobado',
    seleccionado: false
  },
  {
    id: 2,
    mes: 'Febrero',
    periodo: '01 Febr – 27 Febr 2026',
    actividades_desarrolladas: 12,
    estado: 'Aprobado',
    seleccionado: false
  }
])

// Lista computada garantizada de mayor a menor
const reportesDisponiblesOrdenados = computed(() => {
  return ordenarPorMesMayorAMenor(reportesDisponibles.value)
})

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

const toggleReporte = (rep) => {
  rep.seleccionado = !rep.seleccionado
}

const totalReportesSeleccionados = computed(() => {
  return reportesDisponibles.value.filter(r => r.seleccionado).length
})

// ----------------------------------------------------
// SECCIÓN 2: Anexos (Archivos Adjuntos)
// ----------------------------------------------------
const anexos = ref([])
const fileInputRef = ref(null)
const isDragging = ref(false)

const triggerFileInput = () => {
  if (fileInputRef.value) fileInputRef.value.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files || [])
  procesarArchivos(files)
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files || [])
  procesarArchivos(files)
}

const procesarArchivos = (files) => {
  files.forEach(file => {
    anexos.value.push({
      file,
      nombre: file.name,
      tamano: (file.size / 1024 / 1024).toFixed(2) + ' MB',
      tipo: file.type
    })
  })
}

const eliminarAnexo = (index) => {
  anexos.value.splice(index, 1)
}

// ----------------------------------------------------
// SECCIÓN 3: Presupuesto Dinámico (Filas y Columnas)
// ----------------------------------------------------
// Mapeo cronológico de abreviaturas y nombres de meses
const mapaMesesAbrev = {
  1: 'Ene.', 2: 'Feb.', 3: 'Mar.', 4: 'Abr.', 5: 'May.', 6: 'Junio',
  7: 'Jul.', 8: 'Agos.', 9: 'Sept.', 10: 'Oct.', 11: 'Nov.', 12: 'Dic.'
}

const mapaMesesNombres = {
  'ene.': 'enero', 'feb.': 'febrero', 'mar.': 'marzo', 'abr.': 'abril',
  'may.': 'mayo', 'junio': 'junio', 'jul.': 'julio', 'agos.': 'agosto',
  'sept.': 'septiembre', 'oct.': 'octubre', 'nov.': 'noviembre', 'dic.': 'diciembre'
}

// Columnas de meses dinámicas (por defecto según los reportes mensuales creados)
const columnasMeses = ref(['Ene.', 'Feb.'])

// Filas de gastos dinámicas
const filasPresupuesto = ref([
  {
    gasto: 'Comida',
    montos: { 'Ene.': '', 'Feb.': '' }
  },
  {
    gasto: 'Pasaje',
    montos: { 'Ene.': '', 'Feb.': '' }
  }
])

// Actualiza las columnas dinámicamente según la cantidad y orden de reportes mensuales
const actualizarColumnasPresupuesto = () => {
  if (!reportesDisponibles.value || reportesDisponibles.value.length === 0) {
    columnasMeses.value = ['Ene.', 'Feb.']
    return
  }

  // Ordenar cronológicamente ascendente (desde el primer reporte hasta el último)
  const reportesAsc = [...reportesDisponibles.value].sort((a, b) => {
    return obtenerScoreMes(a) - obtenerScoreMes(b)
  })

  const cols = []
  reportesAsc.forEach(rep => {
    const score = obtenerScoreMes(rep)
    const mesNum = score % 100
    const abrev = mapaMesesAbrev[mesNum] || rep.mes
    if (abrev && !cols.includes(abrev)) {
      cols.push(abrev)
    }
  })

  columnasMeses.value = cols.length > 0 ? cols : ['Ene.', 'Feb.']

  // Garantizar que cada fila mantenga o inicialice sus columnas
  filasPresupuesto.value.forEach(fila => {
    if (!fila.montos) fila.montos = {}
    columnasMeses.value.forEach(col => {
      if (fila.montos[col] === undefined) {
        fila.montos[col] = ''
      }
    })
  })
}

// Verifica si el usuario ha ingresado un valor numérico real
const tieneMontoIngresado = (val) => {
  if (val === null || val === undefined || val === '') return false
  const str = String(val).trim()
  return str !== '' && str !== '0'
}

// Agregar nueva fila de gasto con el botón (+) circular
const agregarFilaGasto = () => {
  const nuevosMontos = {}
  columnasMeses.value.forEach(col => {
    nuevosMontos[col] = ''
  })
  filasPresupuesto.value.push({
    gasto: '',
    montos: nuevosMontos
  })
}

const eliminarFilaGasto = (index) => {
  if (filasPresupuesto.value.length > 1) {
    filasPresupuesto.value.splice(index, 1)
  }
}

// Calcular total por fila con soporte completo de centavos (ej: 25.15)
const calcularTotalFila = (fila) => {
  let suma = 0
  let hayValores = false
  columnasMeses.value.forEach(col => {
    const raw = String(fila.montos?.[col] ?? '').trim().replace(',', '.')
    const val = parseFloat(raw)
    if (!isNaN(val) && val > 0) {
      suma += val
      hayValores = true
    }
  })
  if (!hayValores || suma === 0) return '000'
  return Number.isInteger(suma) ? String(suma) : suma.toFixed(2)
}

// Calcular gran total consolidado con soporte completo de centavos
const granTotalPresupuesto = computed(() => {
  let total = 0
  let hayValores = false
  filasPresupuesto.value.forEach(fila => {
    columnasMeses.value.forEach(col => {
      const raw = String(fila.montos?.[col] ?? '').trim().replace(',', '.')
      const val = parseFloat(raw)
      if (!isNaN(val) && val > 0) {
        total += val
        hayValores = true
      }
    })
  })
  if (!hayValores || total === 0) return '000'
  return Number.isInteger(total) ? String(total) : total.toFixed(2)
})

// Subtítulo dinámico para el presupuesto: 'Presupuesto de [primer mes] a [último mes]'
const subtituloPresupuesto = computed(() => {
  if (columnasMeses.value.length === 0) return 'Presupuesto'
  const primerCol = columnasMeses.value[0].toLowerCase()
  const ultimoCol = columnasMeses.value[columnasMeses.value.length - 1].toLowerCase()
  const primerMes = mapaMesesNombres[primerCol] || primerCol
  const ultimoMes = mapaMesesNombres[ultimoCol] || ultimoCol

  if (primerMes === ultimoMes) {
    return `Presupuesto de ${primerMes}`
  }
  return `Presupuesto de ${primerMes} a ${ultimoMes}`
})

// ----------------------------------------------------
// Carga inicial desde la API
// ----------------------------------------------------
const cargarDatosIniciales = async () => {
  cargando.value = true
  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const token = localStorage.getItem('genesis_token')
    const config = {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      params: userId ? { user_id: userId } : {}
    }

    const res = await axios.get('/api/informes-finales/formulario-inicial', config)
    if (res.data && res.data.data) {
      const data = res.data.data
      if (data.nombre_pasante) form.value.nombre_pasante = data.nombre_pasante
      if (data.institucion) form.value.institucion = data.institucion

      // Si la API devuelve reportes reales, usarlos ordenados de mayor a menor
      if (Array.isArray(data.reportes_disponibles) && data.reportes_disponibles.length > 0) {
        const mapeados = data.reportes_disponibles.map(r => ({
          ...r,
          seleccionado: false
        }))
        reportesDisponibles.value = ordenarPorMesMayorAMenor(mapeados)
      }
      actualizarColumnasPresupuesto()
    }
  } catch (e) {
    console.info('Usando configuración estándar de maqueta para informe final.')
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  cargarDatosIniciales()
  actualizarColumnasPresupuesto()
})

// ----------------------------------------------------
// Envío del Formulario (Guardar / Enviar Informe)
// ----------------------------------------------------
const enviarInforme = async () => {
  intentoEnvio.value = true
  mensajeError.value = ''

  // Validaciones requeridas
  if (!form.value.nombre_pasante?.trim() ||
      !form.value.institucion?.trim() ||
      !form.value.presentacion?.trim() ||
      !form.value.generalidades?.trim() ||
      !form.value.procedimiento_pasantia?.trim() ||
      !form.value.descripcion_empresa?.trim() ||
      !form.value.descripcion_pasantia?.trim() ||
      totalReportesSeleccionados.value === 0) {
    setTimeout(() => {
      const primerInvalido = document.querySelector('.border-red-500')
      if (primerInvalido) {
        primerInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' })
        primerInvalido.focus()
      }
    }, 50)
    return
  }

  const reportesSeleccionadosIds = reportesDisponibles.value
    .filter(r => r.seleccionado)
    .map(r => r.id)

  enviando.value = true

  try {
    const userId = usuario.value?.id || usuario.value?.user_id
    const token = localStorage.getItem('genesis_token')
    
    // Preparar FormData para soportar subida de archivos anexos
    const formData = new FormData()
    formData.append('user_id', userId || '')
    formData.append('nombre_pasante', form.value.nombre_pasante)
    formData.append('institucion', form.value.institucion)
    formData.append('presentacion', form.value.presentacion)
    formData.append('generalidades', form.value.generalidades)
    formData.append('procedimiento_pasantia', form.value.procedimiento_pasantia)
    formData.append('descripcion_empresa', form.value.descripcion_empresa)
    formData.append('descripcion_pasantia', form.value.descripcion_pasantia)
    formData.append('estado', 'Enviado')

    // IDs de reportes seleccionados
    formData.append('reportes_ids', JSON.stringify(reportesSeleccionadosIds))

    // Columnas del presupuesto
    formData.append('presupuesto_columnas', JSON.stringify(columnasMeses.value))

    // Items del presupuesto
    const itemsFormateados = filasPresupuesto.value.map((fila, idx) => ({
      gasto: fila.gasto.trim() || `Gasto ${idx + 1}`,
      orden: idx + 1,
      montos: fila.montos,
      total: calcularTotalFila(fila)
    }))
    formData.append('presupuesto_items', JSON.stringify(itemsFormateados))

    // Adjuntar archivos anexos
    anexos.value.forEach((anx, i) => {
      if (anx.file) {
        formData.append(`archivos[${i}]`, anx.file)
      }
    })

    const config = {
      headers: {
        'Content-Type': 'multipart/form-data',
        ...(token ? { Authorization: `Bearer ${token}` } : {})
      }
    }

    const res = await axios.post('/api/informes-finales', formData, config)

    if (res.data && res.data.exito) {
      // Notificar en tiempo real a otras pestañas y ventanas abiertas (Historial de progreso)
      try {
        if (typeof BroadcastChannel !== 'undefined') {
          const canalSync = new BroadcastChannel('genesis_historial_sync')
          canalSync.postMessage({
            tipo: 'informe_final_creado',
            timestamp: Date.now()
          })
          canalSync.close()
        }
      } catch (e) {
        console.warn('BroadcastChannel no disponible:', e)
      }

      try {
        localStorage.setItem('genesis_historial_evento', JSON.stringify({
          tipo: 'informe_final_creado',
          timestamp: Date.now()
        }))
        localStorage.setItem('genesis_historial_updated', Date.now().toString())
      } catch (e) {}

      toastExitoVisible.value = true
    } else {
      mensajeError.value = res.data?.mensaje || 'Ocurrió un error al registrar el informe final.'
    }
  } catch (err) {
    console.error('Error al enviar informe final:', err)
    mensajeError.value = err.response?.data?.mensaje || 'Error de conexión con el servidor al guardar el informe final.'
  } finally {
    enviando.value = false
  }
}

const finalizarEnvio = () => {
  toastExitoVisible.value = false
  emit('guardado')
  emit('volver')
}
</script>

<template>
  <div class="space-y-6 pb-16">
    
    <!-- ENLACE SUPERIOR: ← Reportes/ Nuevo reporte -->
    <div class="flex items-center justify-between">
      <button 
        @click="emit('volver')"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#010C67] transition cursor-pointer"
      >
        <span class="text-base font-bold">←</span>
        <span>Reportes/ Nuevo reporte</span>
      </button>
    </div>

    <!-- MENSAJE DE ERROR DEL SERVIDOR (SI OCURRE AL ENVIAR) -->
    <div v-if="mensajeError" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-xs">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-500 mr-2 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm font-medium text-red-800">{{ mensajeError }}</span>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CONTENEDOR PRINCIPAL BLANCO CON BORDE AZUL CLARO Y LÍNEA DE SOMBRA DEFINIDA -->
    <!-- ========================================================================= -->
    <div class="bg-white border border-[#93c5fd] rounded-2xl p-6 sm:p-10 shadow-[4px_4px_0px_0px_rgba(0,11,88,0.16)] max-w-5xl mx-auto">
      
      <!-- ENCABEZADO INSTITUCIONAL UGB -->
      <div class="flex flex-col items-center justify-center text-center mb-8">
        <div class="flex items-center justify-center gap-6 mb-2">
          <!-- Logo UGB -->
          <img 
            src="/images/Logo_para_reportes_mensuales.png" 
            alt="Universidad Gerardo Barrios" 
            class="h-16 w-auto object-contain"
            @error="(e) => e.target.style.display = 'none'"
          />
          <div class="text-center">
            <h2 class="text-xl sm:text-2xl font-serif font-bold text-[#010C67] tracking-tight">
              FACULTAD DE CIENCIA Y TECNOLOGÍA
            </h2>
            <p class="text-xs sm:text-sm font-semibold text-[#010C67] mt-0.5">
              Trabajo de Graduación Modalidad Pasantía
            </p>
            <p class="text-xs sm:text-sm font-medium text-[#1E3A8A]">
              Ingeniería en Sistemas y Redes Informáticas
            </p>
          </div>
        </div>
      </div>

      <!-- TÍTULO: Informe final -->
      <div class="mb-6">
        <h1 class="text-2xl font-serif font-bold text-gray-900 tracking-tight">
          Informe final
        </h1>
      </div>

      <!-- CAMPOS DEL FORMULARIO -->
      <div class="space-y-5">
        
        <!-- FILA 1: Nombre del pasante y Institución (2 Columnas) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          
          <!-- Nombre del pasante* -->
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-1.5">
              Nombre del pasante<span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.nombre_pasante"
              type="text" 
              class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs transition-colors"
              :class="intentoEnvio && !form.nombre_pasante?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
              placeholder="Nombre del pasante"
            />
            <!-- Mensaje de error campo obligatorio -->
            <p v-if="intentoEnvio && !form.nombre_pasante?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
              <span>Este campo es obligatorio.</span>
            </p>
          </div>

          <!-- Institución dónde se realizó la pasantía* -->
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-1.5">
              Institución dónde se realizó la pasantía<span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.institucion"
              type="text" 
              class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs transition-colors"
              :class="intentoEnvio && !form.institucion?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
              placeholder="Empresas CASS"
            />
            <!-- Mensaje de error campo obligatorio -->
            <p v-if="intentoEnvio && !form.institucion?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
              <span>Este campo es obligatorio.</span>
            </p>
          </div>

        </div>

        <!-- Presentación* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Presentación<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.presentacion"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs resize-y placeholder:text-gray-400 transition-colors"
            :class="intentoEnvio && !form.presentacion?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
            placeholder="Escribe una presentación..."
          ></textarea>
          <!-- Mensaje de error campo obligatorio -->
          <p v-if="intentoEnvio && !form.presentacion?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio.</span>
          </p>
        </div>

        <!-- Generalidades* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Generalidades<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.generalidades"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs resize-y placeholder:text-gray-400 transition-colors"
            :class="intentoEnvio && !form.generalidades?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
            placeholder="Escribe las generalidades..."
          ></textarea>
          <!-- Mensaje de error campo obligatorio -->
          <p v-if="intentoEnvio && !form.generalidades?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio.</span>
          </p>
        </div>

        <!-- Procedimiento para el proceso de la pasantía* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Procedimiento para el proceso de la pasantía<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.procedimiento_pasantia"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs resize-y placeholder:text-gray-400 transition-colors"
            :class="intentoEnvio && !form.procedimiento_pasantia?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
            placeholder="Describe el procedimiento para el proceso de pasantías..."
          ></textarea>
          <!-- Mensaje de error campo obligatorio -->
          <p v-if="intentoEnvio && !form.procedimiento_pasantia?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio.</span>
          </p>
        </div>

        <!-- Descripción de la empresa* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Descripción de la empresa<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.descripcion_empresa"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs resize-y placeholder:text-gray-400 transition-colors"
            :class="intentoEnvio && !form.descripcion_empresa?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
            placeholder="Escribe una descripción sobre la empresa..."
          ></textarea>
          <!-- Mensaje de error campo obligatorio -->
          <p v-if="intentoEnvio && !form.descripcion_empresa?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio.</span>
          </p>
        </div>

        <!-- Descripción de la pasantía* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Descripción de la pasantía<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.descripcion_pasantia"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border bg-white text-gray-900 text-sm focus:outline-none shadow-xs resize-y placeholder:text-gray-400 transition-colors"
            :class="intentoEnvio && !form.descripcion_pasantia?.trim() ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500' : 'border-[#3b82f6] focus:border-blue-600 focus:ring-1 focus:ring-blue-500'"
            placeholder="Describe las tareas y aprendizajes de la pasantía..."
          ></textarea>
          <!-- Mensaje de error campo obligatorio -->
          <p v-if="intentoEnvio && !form.descripcion_pasantia?.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio.</span>
          </p>
        </div>

        <!-- ========================================================================= -->
        <!-- SECCIÓN: SELECCIONA LOS REPORTES MENSUALES PARA ADJUNTAR*                 -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <label class="block text-sm font-semibold text-gray-900 mb-3">
            Selecciona los reportes mensuales para adjuntar<span class="text-red-500">*</span>
          </label>

          <!-- Encabezado de la tabla de reportes adjuntos -->
          <div class="border border-blue-200 rounded-xl py-3 px-6 mb-3 text-sm font-medium text-gray-500 grid grid-cols-12 gap-2 sm:gap-4 items-center bg-white">
            <div class="col-span-4 text-center">Mes del reporte</div>
            <div class="col-span-3 text-center">Periodo</div>
            <div class="col-span-3 text-center">Actividades desarrolladas</div>
            <div class="col-span-2 text-center">Estado</div>
          </div>

          <!-- Filas de los reportes mensuales (ordenados del mes mayor al menor) -->
          <div class="space-y-3">
            <div 
              v-for="rep in reportesDisponiblesOrdenados" 
              :key="rep.id"
              @click="toggleReporte(rep)"
              class="border border-[#5ba4e5] rounded-xl px-6 py-4 grid grid-cols-12 gap-2 sm:gap-4 items-center bg-white hover:bg-blue-50/20 transition cursor-pointer shadow-[2px_2px_0px_0px_rgba(0,11,88,0.08)]"
            >
              <!-- Checkbox + Icono de documento + Mes -->
              <div class="col-span-4 flex items-center gap-3 sm:gap-4">
                <!-- Checkbox redondeado con borde azul marino -->
                <div 
                  class="w-6 h-6 rounded-lg border-2 transition-all flex items-center justify-center shrink-0 cursor-pointer"
                  :class="rep.seleccionado ? 'border-[#000B58] bg-[#000B58]' : 'border-[#000B58] bg-white'"
                >
                  <svg v-if="rep.seleccionado" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                </div>

                <!-- Icono de documento / ficha de reporte -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-800 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                  <circle cx="12" cy="8" r="2.5"></circle>
                  <path d="M8 14a4 4 0 0 1 8 0"></path>
                  <line x1="8" y1="17" x2="16" y2="17"></line>
                  <line x1="8" y1="20" x2="16" y2="20"></line>
                </svg>

                <!-- Nombre del mes -->
                <span class="font-medium text-sm sm:text-base text-gray-900">{{ rep.mes }}</span>
              </div>

              <!-- Periodo (Rango de fechas y año) -->
              <div class="col-span-3 text-center text-sm font-medium text-gray-900 leading-snug">
                <div>{{ formatearRangoPeriodo(rep.periodo) }}</div>
                <div>{{ formatearAnioPeriodo(rep.periodo) }}</div>
              </div>

              <!-- Actividades desarrolladas -->
              <div class="col-span-3 text-center text-sm sm:text-base font-medium text-gray-900">
                {{ rep.actividades_desarrolladas }}
              </div>

              <!-- Estado: Badge Verde "✓ Aprobado" idéntico a la captura -->
              <div class="col-span-2 flex justify-center">
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs sm:text-sm font-medium bg-[#00C853] text-white shadow-2xs">
                  <svg class="w-4 h-4 shrink-0 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <span>{{ rep.estado || 'Aprobado' }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Mensaje de error para reportes mensuales -->
          <p v-if="intentoEnvio && totalReportesSeleccionados === 0" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>Este campo es obligatorio. Debes seleccionar al menos un reporte mensual.</span>
          </p>
        </div>

        <!-- ========================================================================= -->
        <!-- SECCIÓN: ANEXOS (SUBIR ARCHIVOS CON DROPZONE)                             -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <h3 class="text-base font-serif font-bold text-gray-900 mb-3">
            Anexos
          </h3>

          <!-- Zona de Subida (Dropzone) punteada con icono de nube circular negro -->
          <div 
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            class="border-2 border-dashed border-gray-400 rounded-xl bg-gray-50/70 py-10 px-6 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100/70 transition-all text-center"
            :class="{ 'border-blue-500 bg-blue-50/50': isDragging }"
          >
            <input 
              ref="fileInputRef" 
              type="file" 
              multiple 
              class="hidden" 
              @change="handleFileSelect"
            />

            <!-- Icono exacto de Anexos (Figma) -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mb-2 text-black" viewBox="0 0 74 74" fill="none">
              <mask id="mask0_anexos_upload" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="5" y="14" width="64" height="45">
                <path d="M21.3557 57.966H54.9149C61.6572 57.966 67.1182 52.505 67.1182 45.7627C67.1182 39.0204 61.6572 33.5594 54.9149 33.5594H51.864V30.5085C51.864 22.0883 45.0302 15.2544 36.6099 15.2544C29.2269 15.2544 23.0642 20.5018 21.6608 27.4577H21.3557C12.9354 27.4577 6.10156 34.2916 6.10156 42.7119C6.10156 51.1322 12.9354 57.966 21.3557 57.966Z" fill="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="white"/>
                <path d="M30.508 51.8642H42.7113V39.6609H51.8638L36.6096 24.4067L21.3555 39.6609H30.508V51.8642Z" fill="black"/>
              </mask>
              <g mask="url(#mask0_anexos_upload)">
                <path d="M0 0H73.2199V73.2199H0V0Z" fill="black"/>
              </g>
            </svg>

            <span class="text-xs font-semibold text-gray-800">
              Subir archivos
            </span>
          </div>

          <!-- Lista de Archivos Adjuntados -->
          <div v-if="anexos.length > 0" class="mt-4 space-y-2">
            <div 
              v-for="(anx, index) in anexos" 
              :key="index"
              class="flex items-center justify-between p-3 bg-blue-50/50 border border-blue-200 rounded-lg text-sm"
            >
              <div class="flex items-center gap-3 truncate">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <span class="font-medium text-gray-800 truncate">{{ anx.nombre }}</span>
                <span class="text-xs text-gray-500">({{ anx.tamano }})</span>
              </div>
              <button 
                @click.stop="eliminarAnexo(index)"
                class="p-1 text-gray-400 hover:text-red-500 transition cursor-pointer"
                title="Eliminar anexo"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECCIÓN: PRESUPUESTO DINÁMICO                                             -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <div class="mb-1">
            <h3 class="text-base font-serif font-bold text-gray-900">
              Presupuesto
            </h3>
            <p class="text-xs sm:text-sm text-gray-700 mt-0.5 mb-3">
              {{ subtituloPresupuesto }}
            </p>
          </div>

          <!-- TABLA PRINCIPAL DEL PRESUPUESTO -->
          <div class="border border-gray-300 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
              <table class="w-full border-collapse">
                <!-- ENCABEZADO AZUL MARINO (#010C67) -->
                <thead>
                  <tr class="bg-[#010C67] text-white text-xs font-semibold select-none">
                    <th class="py-3 px-4 text-left min-w-[120px]">Gasto</th>
                    <th 
                      v-for="col in columnasMeses" 
                      :key="col"
                      class="py-3 px-2 text-center min-w-[65px]"
                    >
                      <div class="flex items-center justify-center">
                        <span>{{ col }}</span>
                      </div>
                    </th>
                    <th class="py-3 px-4 text-center min-w-[95px]">Total USD</th>
                    <th class="py-3 px-2 text-center w-10"></th>
                  </tr>
                </thead>

                <!-- CUERPO DE LA TABLA (FILAS DE GASTOS) -->
                <tbody class="divide-y divide-gray-200 bg-white text-sm">
                  <tr 
                    v-for="(fila, fIndex) in filasPresupuesto" 
                    :key="fIndex"
                    class="hover:bg-gray-50/50 transition"
                  >
                    <!-- Nombre del gasto (Comida, Pasaje, etc.) -->
                    <td class="py-3 px-4 font-serif text-sm sm:text-base font-semibold text-gray-900">
                      <input 
                        v-model="fila.gasto"
                        type="text"
                        placeholder="Nombre de gasto"
                        class="w-full font-serif text-sm sm:text-base font-semibold text-gray-900 bg-transparent focus:outline-none focus:border-b border-blue-500"
                      />
                    </td>

                    <!-- Inputs por cada mes (cuadritos redondeados con color gris por defecto y negro al ingresar número) -->
                    <td 
                      v-for="col in columnasMeses" 
                      :key="col"
                      class="py-3 px-1.5 text-center"
                    >
                      <input 
                        v-model="fila.montos[col]"
                        type="text" 
                        inputmode="decimal"
                        placeholder="0"
                        class="no-spinner w-12 sm:w-14 h-9 text-center text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-2xs transition-colors"
                        :class="tieneMontoIngresado(fila.montos[col]) ? 'text-gray-900 font-semibold' : 'text-gray-400 font-normal'"
                      />
                    </td>

                    <!-- Total USD por fila (cuadrito redondeado con 000 o monto con centavos ej: 25.15) -->
                    <td class="py-3 px-3 text-center">
                      <div 
                        class="inline-flex items-center justify-center min-w-[75px] h-9 px-2 text-sm border border-gray-300 rounded-lg bg-gray-50/50 shadow-2xs transition-colors"
                        :class="calcularTotalFila(fila) !== '000' ? 'text-gray-900 font-semibold' : 'text-gray-400 font-normal'"
                      >
                        {{ calcularTotalFila(fila) }}
                      </div>
                    </td>

                    <!-- Botón de eliminar fila (✕ gris) -->
                    <td class="py-3 px-2 text-center">
                      <button 
                        @click="eliminarFilaGasto(fIndex)"
                        type="button"
                        class="text-gray-400 hover:text-red-500 font-bold text-lg p-1 transition cursor-pointer"
                        title="Eliminar fila de gasto"
                      >
                        ✕
                      </button>
                    </td>
                  </tr>

                  <!-- FILA DEL BOTÓN (+) CIRCULAR PARA AGREGAR NUEVAS FILAS DE GASTO -->
                  <tr>
                    <td :colspan="columnasMeses.length + 3" class="p-3 pl-4">
                      <button 
                        @click="agregarFilaGasto"
                        type="button"
                        class="w-7 h-7 rounded-full bg-[#010C67] text-white hover:bg-[#000840] flex items-center justify-center transition shadow-xs cursor-pointer"
                        title="Agregar nueva fila de gasto"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                        </svg>
                      </button>
                    </td>
                  </tr>

                  <!-- FILA FINAL DE TOTAL CONSOLIDADO -->
                  <tr class="border-t border-gray-300 bg-white">
                    <td class="py-4 px-4 font-serif text-base sm:text-lg font-bold text-gray-900">
                      Total
                    </td>
                    <td :colspan="columnasMeses.length"></td>
                    <!-- Box del Total General ($000 o monto con centavos ej: $25.15) -->
                    <td class="py-4 px-3 text-center">
                      <div 
                        class="inline-flex items-center justify-center min-w-[85px] h-10 px-3 text-sm border border-gray-300 rounded-lg bg-white shadow-2xs transition-colors"
                        :class="granTotalPresupuesto !== '000' ? 'text-gray-900 font-bold' : 'text-gray-400 font-normal'"
                      >
                        ${{ granTotalPresupuesto }}
                      </div>
                    </td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- BOTONES DE ACCIÓN: CANCELAR Y ENVIAR                                      -->
    <!-- ========================================================================= -->
    <div class="flex items-center justify-end gap-4 max-w-5xl mx-auto pt-4">
      
      <!-- Botón Cancelar -->
      <button 
        @click="emit('volver')"
        type="button"
        class="px-8 py-2.5 rounded-lg border border-[#010C67] text-[#010C67] hover:bg-blue-50 font-serif font-medium text-sm transition cursor-pointer bg-white shadow-2xs"
      >
        Cancelar
      </button>

      <!-- Botón Enviar -->
      <button 
        @click="enviarInforme"
        type="button"
        :disabled="enviando"
        class="px-9 py-2.5 rounded-lg bg-[#010C67] hover:bg-[#000840] text-white font-serif font-medium text-sm transition cursor-pointer shadow-md flex items-center gap-2 disabled:opacity-60"
      >
        <svg v-if="enviando" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Enviar</span>
      </button>

    </div>

    <!-- ========================================================================= -->
    <!-- TOAST DE ÉXITO IDÉNTICO A LA CAPTURA (ESQUINA INFERIOR DERECHA)           -->
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
            @click="finalizarEnvio"
            class="text-white/80 hover:text-white transition-colors cursor-pointer p-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <p class="text-white/95 text-sm mt-2 mb-4 leading-relaxed font-normal">
          Reporte enviado exitosamente
        </p>

        <div class="flex justify-end">
          <button
            @click="finalizarEnvio"
            class="px-5 py-1.5 border border-white rounded-lg text-xs font-semibold text-white hover:bg-white hover:text-[#00C853] transition-all cursor-pointer shadow-xs"
          >
            Aceptar
          </button>
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
.no-spinner::-webkit-inner-spin-button,
.no-spinner::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.no-spinner {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
