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
const exitoModal = ref(false)

// Nombre del estudiante autenticado desde sesión
const nombreInicial = usuario.value?.nombres
  ? `${usuario.value.nombres} ${usuario.value.apellidos || ''}`.trim()
  : (usuario.value?.nombre || '')

// Campos del formulario principal
const form = ref({
  nombre_pasante: nombreInicial,
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
// SECCIÓN 1: Reportes Mensuales para Adjuntar (Cargados de la base de datos)
// ----------------------------------------------------
const reportesDisponibles = ref([])

const toggleReporte = (rep) => {
  rep.seleccionado = !rep.seleccionado
}

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
// Columnas de meses dinámicas (por defecto de enero a julio según la captura)
const columnasMeses = ref(['Ene.', 'Feb.', 'Mar.', 'Abr.', 'May.', 'Junio', 'Jul.'])

// Filas de gastos dinámicas
const filasPresupuesto = ref([
  {
    gasto: 'Comida',
    montos: { 'Ene.': 0, 'Feb.': 0, 'Mar.': 0, 'Abr.': 0, 'May.': 0, 'Junio': 0, 'Jul.': 0 }
  },
  {
    gasto: 'Pasaje',
    montos: { 'Ene.': 0, 'Feb.': 0, 'Mar.': 0, 'Abr.': 0, 'May.': 0, 'Junio': 0, 'Jul.': 0 }
  }
])

// Modal para agregar una nueva columna
const modalColumnaAbierto = ref(false)
const nuevaColumnaNombre = ref('')

const abrirModalNuevaColumna = () => {
  nuevaColumnaNombre.value = ''
  modalColumnaAbierto.value = true
}

const agregarColumna = () => {
  const nombre = nuevaColumnaNombre.value.trim()
  if (!nombre) return
  if (!columnasMeses.value.includes(nombre)) {
    columnasMeses.value.push(nombre)
    // Inicializar el valor en 0 para cada fila existente
    filasPresupuesto.value.forEach(fila => {
      if (fila.montos[nombre] === undefined) {
        fila.montos[nombre] = 0
      }
    })
  }
  modalColumnaAbierto.value = false
  nuevaColumnaNombre.value = ''
}

const eliminarColumna = (colName) => {
  if (columnasMeses.value.length <= 1) return
  columnasMeses.value = columnasMeses.value.filter(c => c !== colName)
  filasPresupuesto.value.forEach(fila => {
    delete fila.montos[colName]
  })
}

// Agregar nueva fila de gasto con el botón (+) circular
const agregarFilaGasto = () => {
  const nuevosMontos = {}
  columnasMeses.value.forEach(col => {
    nuevosMontos[col] = 0
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

// Calcular total por fila
const calcularTotalFila = (fila) => {
  let suma = 0
  columnasMeses.value.forEach(col => {
    const val = parseFloat(fila.montos[col]) || 0
    suma += val
  })
  return Math.round(suma)
}

// Calcular gran total consolidado
const granTotalPresupuesto = computed(() => {
  let total = 0
  filasPresupuesto.value.forEach(fila => {
    total += calcularTotalFila(fila)
  })
  return Math.round(total)
})

// Subtítulo dinámico para el presupuesto
const subtituloPresupuesto = computed(() => {
  if (columnasMeses.value.length === 0) return 'Presupuesto'
  const primerMes = columnasMeses.value[0]
  const ultimoMes = columnasMeses.value[columnasMeses.value.length - 1]
  return `Presupuesto de ${primerMes.toLowerCase()} a ${ultimoMes.toLowerCase()}`
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

      // Si la API devuelve reportes reales, usarlos
      if (Array.isArray(data.reportes_disponibles) && data.reportes_disponibles.length > 0) {
        reportesDisponibles.value = data.reportes_disponibles.map(r => ({
          ...r,
          seleccionado: true
        }))
      }
    }
  } catch (e) {
    console.info('Usando configuración estándar de maqueta para informe final.')
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  cargarDatosIniciales()
})

// ----------------------------------------------------
// Envío del Formulario (Guardar / Enviar Informe)
// ----------------------------------------------------
const enviarInforme = async () => {
  intentoEnvio.value = true
  mensajeError.value = ''

  // Validaciones requeridas
  if (!form.value.institucion.trim() ||
      !form.value.presentacion.trim() ||
      !form.value.generalidades.trim() ||
      !form.value.procedimiento_pasantia.trim() ||
      !form.value.descripcion_empresa.trim() ||
      !form.value.descripcion_pasantia.trim()) {
    mensajeError.value = 'Por favor completa todos los campos obligatorios marcados con (*).'
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  const reportesSeleccionadosIds = reportesDisponibles.value
    .filter(r => r.seleccionado)
    .map(r => r.id)

  if (reportesSeleccionadosIds.length === 0) {
    mensajeError.value = 'Debes seleccionar al menos un reporte mensual para adjuntar al informe final.'
    return
  }

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
      exitoModal.value = true
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
  exitoModal.value = false
  emit('guardado')
  emit('volver')
}
</script>

<template>
  <div class="space-y-6 pb-16">
    
    <!-- ENLACE SUPERIOR: ← Reportes/ Nuevo reporte (Exacto a la captura) -->
    <div class="flex items-center justify-between">
      <button 
        @click="emit('volver')"
        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#000B58] transition cursor-pointer"
      >
        <span class="text-base font-bold">←</span>
        <span>Reportes/ Nuevo reporte</span>
      </button>
    </div>

    <!-- MENSAJE DE ERROR SI FALTA ALGO -->
    <div v-if="mensajeError" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-xs">
      <div class="flex items-center">
        <svg class="h-5 w-5 text-red-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm font-medium text-red-800">{{ mensajeError }}</span>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CONTENEDOR PRINCIPAL BLANCO CON BORDE AZUL CLARO (CALCADO A LA CAPTURA)   -->
    <!-- ========================================================================= -->
    <div class="bg-white border border-[#3b82f6] rounded-2xl p-6 sm:p-10 shadow-xs max-w-5xl mx-auto">
      
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
              class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs"
              placeholder="Nombre del pasante"
            />
          </div>

          <!-- Institución dónde se realizó la pasantía* -->
          <div>
            <label class="block text-sm font-medium text-gray-900 mb-1.5">
              Institución dónde se realizó la pasantía<span class="text-red-500">*</span>
            </label>
            <input 
              v-model="form.institucion"
              type="text" 
              class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs"
              :class="{ 'border-red-400': intentoEnvio && !form.institucion.trim() }"
              placeholder="Institución donde se realizó la pasantía"
            />
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
            class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-y placeholder:text-gray-400"
            :class="{ 'border-red-400': intentoEnvio && !form.presentacion.trim() }"
            placeholder="Escribe una presentación..."
          ></textarea>
        </div>

        <!-- Generalidades* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Generalidades<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.generalidades"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-y placeholder:text-gray-400"
            :class="{ 'border-red-400': intentoEnvio && !form.generalidades.trim() }"
            placeholder="Escribe las generalidades..."
          ></textarea>
        </div>

        <!-- Procedimiento para el proceso de la pasantía* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Procedimiento para el proceso de la pasantía<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.procedimiento_pasantia"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-y placeholder:text-gray-400"
            :class="{ 'border-red-400': intentoEnvio && !form.procedimiento_pasantia.trim() }"
            placeholder="Escribe el procedimiento para el proceso de pasantías..."
          ></textarea>
        </div>

        <!-- Descripción de la empresa CASS* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Descripción de la empresa {{ form.institucion ? form.institucion : 'CASS' }}<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.descripcion_empresa"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-y placeholder:text-gray-400"
            :class="{ 'border-red-400': intentoEnvio && !form.descripcion_empresa.trim() }"
            placeholder="Escribe el procedimiento para el proceso de pasantías..."
          ></textarea>
        </div>

        <!-- Descripción de la pasantía* -->
        <div>
          <label class="block text-sm font-medium text-gray-900 mb-1.5">
            Descripción de la pasantía<span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.descripcion_pasantia"
            rows="3"
            class="w-full px-3.5 py-2.5 rounded-lg border border-[#3b82f6] bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-y placeholder:text-gray-400"
            :class="{ 'border-red-400': intentoEnvio && !form.descripcion_pasantia.trim() }"
            placeholder="Escribe la descripción de la pasantía realizada..."
          ></textarea>
        </div>

        <!-- ========================================================================= -->
        <!-- SECCIÓN: SELECCIONA LOS REPORTES MENSUALES PARA ADJUNTAR* (CALCADO A LA 3) -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <label class="block text-sm font-medium text-gray-900 mb-3">
            Selecciona los reportes mensuales para adjuntar<span class="text-red-500">*</span>
          </label>

          <!-- Encabezado de la tabla de reportes adjuntos -->
          <div class="border border-blue-200 rounded-lg py-2 px-6 mb-3 text-xs font-medium text-gray-600 grid grid-cols-12 gap-2 text-center items-center bg-gray-50/50">
            <div class="col-span-4 text-center">Mes del reporte</div>
            <div class="col-span-4 text-center">Periodo</div>
            <div class="col-span-2 text-center">Actividades desarrolladas</div>
            <div class="col-span-2 text-center">Estado</div>
          </div>

          <!-- Filas de los reportes mensuales -->
          <div class="space-y-2.5">
            <div 
              v-for="rep in reportesDisponibles" 
              :key="rep.id"
              @click="toggleReporte(rep)"
              class="border border-[#3b82f6] rounded-xl px-5 py-3 grid grid-cols-12 gap-2 items-center bg-white hover:bg-blue-50/40 transition cursor-pointer"
            >
              <!-- Checkbox redondeado idéntico a la captura -->
              <div class="col-span-1 flex items-center">
                <div 
                  class="w-5 h-5 rounded-md border-2 transition-all flex items-center justify-center cursor-pointer"
                  :class="rep.seleccionado ? 'border-blue-600 bg-blue-600' : 'border-gray-400 bg-white'"
                >
                  <svg v-if="rep.seleccionado" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>

              <!-- Icono de documento y Mes del reporte -->
              <div class="col-span-3 flex items-center gap-2.5 font-medium text-sm text-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>{{ rep.mes }}</span>
              </div>

              <!-- Periodo -->
              <div class="col-span-4 text-center text-xs sm:text-sm text-gray-700">
                {{ rep.periodo }}
              </div>

              <!-- Actividades desarrolladas -->
              <div class="col-span-2 text-center text-sm font-semibold text-gray-800">
                {{ rep.actividades_desarrolladas }}
              </div>

              <!-- Estado: Badge Verde "✓ Aprobado" idéntico a la captura -->
              <div class="col-span-2 text-center flex justify-center">
                <span class="inline-flex items-center gap-1 px-4 py-1 rounded-full text-xs font-semibold bg-[#00c853] text-white shadow-2xs">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  <span>{{ rep.estado || 'Aprobado' }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECCIÓN: ANEXOS (SUBIR ARCHIVOS CON ZONA DROPZONE CALCADA A LA CAPTURA)  -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <h3 class="text-xl font-serif font-bold text-gray-900 mb-3">
            Anexos
          </h3>

          <!-- Zona de Subida (Dropzone) punteada con icono de nube negra -->
          <div 
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            class="border-2 border-dashed border-gray-400 rounded-xl bg-gray-50/70 p-10 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100/70 transition-all text-center"
            :class="{ 'border-blue-500 bg-blue-50/50': isDragging }"
          >
            <input 
              ref="fileInputRef" 
              type="file" 
              multiple 
              class="hidden" 
              @change="handleFileSelect"
            />

            <!-- Icono de Nube con Flecha Hacia Arriba (Idéntico a la captura) -->
            <div class="w-14 h-14 rounded-full bg-black flex items-center justify-center text-white mb-3 shadow-xs">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                <polyline points="12 11 12 16"/>
                <polyline points="9 13 12 10 15 13"/>
              </svg>
            </div>

            <span class="text-sm font-semibold text-gray-800">
              Subir archivos
            </span>
            <span class="text-xs text-gray-500 mt-1">
              Arrastra y suelta tus anexos o haz clic para seleccionarlos (PDF, DOCX, PNG, JPG)
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
        <!-- SECCIÓN: PRESUPUESTO DINÁMICO (CALCADO A LAS CAPTURAS 4 Y 5)              -->
        <!-- ========================================================================= -->
        <div class="pt-4">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="text-xl font-serif font-bold text-gray-900">
              Presupuesto
            </h3>
            
            <!-- Botón opcional para agregar columnas dinámicamente -->
            <button 
              @click="abrirModalNuevaColumna"
              type="button"
              class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition cursor-pointer border border-blue-200"
              title="Agregar mes o columna adicional al presupuesto"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
              </svg>
              <span>+ Agregar columna</span>
            </button>
          </div>

          <p class="text-sm text-gray-700 mb-4">
            {{ subtituloPresupuesto }}
          </p>

          <!-- TABLA PRINCIPAL DEL PRESUPUESTO -->
          <div class="border border-gray-300 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
              <table class="w-full border-collapse">
                <!-- ENCABEZADO AZUL MARINO OSCURO (#000B58) -->
                <thead>
                  <tr class="bg-[#000B58] text-white text-xs font-semibold">
                    <th class="py-3 px-4 text-left min-w-[120px]">Gasto</th>
                    <th 
                      v-for="col in columnasMeses" 
                      :key="col"
                      class="py-3 px-2 text-center min-w-[65px] group relative"
                    >
                      <div class="flex items-center justify-center gap-1">
                        <span>{{ col }}</span>
                        <!-- Botón para eliminar columna si hay más de 1 -->
                        <button 
                          v-if="columnasMeses.length > 2"
                          @click="eliminarColumna(col)"
                          type="button"
                          class="opacity-0 group-hover:opacity-100 text-red-300 hover:text-red-100 text-[10px] transition cursor-pointer ml-0.5"
                          title="Eliminar columna"
                        >
                          ✕
                        </button>
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
                    <td class="py-3 px-4 font-serif text-base font-semibold text-gray-900">
                      <input 
                        v-model="fila.gasto"
                        type="text"
                        placeholder="Nombre de gasto"
                        class="w-full font-serif text-sm sm:text-base font-semibold text-gray-900 bg-transparent focus:outline-none focus:border-b border-blue-500"
                      />
                    </td>

                    <!-- Inputs por cada mes (cuadritos redondeados con 0) -->
                    <td 
                      v-for="col in columnasMeses" 
                      :key="col"
                      class="py-3 px-1.5 text-center"
                    >
                      <input 
                        v-model.number="fila.montos[col]"
                        type="number" 
                        min="0"
                        step="any"
                        class="w-12 h-9 text-center font-medium text-gray-800 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 shadow-2xs"
                      />
                    </td>

                    <!-- Total USD por fila (cuadrito redondeado con 000) -->
                    <td class="py-3 px-3 text-center">
                      <div class="inline-flex items-center justify-center min-w-[75px] h-9 px-2 text-sm font-semibold text-gray-800 border border-gray-300 rounded-lg bg-gray-50/50 shadow-2xs">
                        {{ calcularTotalFila(fila) }}
                      </div>
                    </td>

                    <!-- Botón de eliminar fila (✕ gris idéntico a la captura) -->
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
                        class="w-7 h-7 rounded-full bg-[#000B58] text-white hover:bg-[#000840] flex items-center justify-center transition shadow-xs cursor-pointer"
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
                    <td class="py-4 px-4 font-serif text-lg font-bold text-gray-900">
                      Total
                    </td>
                    <td :colspan="columnasMeses.length"></td>
                    <!-- Box del Total General ($000) -->
                    <td class="py-4 px-3 text-center">
                      <div class="inline-flex items-center justify-center min-w-[85px] h-10 px-3 text-sm font-bold text-gray-900 border border-gray-300 rounded-lg bg-white shadow-2xs">
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
    <!-- BOTONES DE ACCIÓN: CANCELAR Y ENVIAR (ABAJO A LA DERECHA DE LA TARJETA)   -->
    <!-- ========================================================================= -->
    <div class="flex items-center justify-end gap-4 max-w-5xl mx-auto pt-4">
      
      <!-- Botón Cancelar (Blanco con borde azul) -->
      <button 
        @click="emit('volver')"
        type="button"
        class="px-8 py-2.5 rounded-lg border border-[#000B58] text-[#000B58] hover:bg-blue-50 font-serif font-medium text-sm transition cursor-pointer bg-white shadow-2xs"
      >
        Cancelar
      </button>

      <!-- Botón Enviar (Azul marino sólido) -->
      <button 
        @click="enviarInforme"
        type="button"
        :disabled="enviando"
        class="px-9 py-2.5 rounded-lg bg-[#000B58] hover:bg-[#000840] text-white font-serif font-medium text-sm transition cursor-pointer shadow-md flex items-center gap-2 disabled:opacity-60"
      >
        <svg v-if="enviando" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Enviar</span>
      </button>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL PARA AGREGAR COLUMNA AL PRESUPUESTO                                -->
    <!-- ========================================================================= -->
    <div 
      v-if="modalColumnaAbierto" 
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-gray-100">
        <h4 class="text-base font-bold text-gray-900 mb-2">
          Agregar columna de mes
        </h4>
        <p class="text-xs text-gray-500 mb-4">
          Ingresa el nombre o abreviatura de la nueva columna (ej: Agos., Sept., Extra).
        </p>
        <input 
          v-model="nuevaColumnaNombre"
          @keyup.enter="agregarColumna"
          type="text" 
          placeholder="Ej: Agos."
          class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-5"
          autofocus
        />
        <div class="flex justify-end gap-2">
          <button 
            @click="modalColumnaAbierto = false"
            type="button"
            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition cursor-pointer"
          >
            Cancelar
          </button>
          <button 
            @click="agregarColumna"
            type="button"
            class="px-4 py-2 text-xs font-semibold text-white bg-[#000B58] hover:bg-[#000840] rounded-lg transition cursor-pointer shadow-xs"
          >
            Agregar
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DE ÉXITO TRAS REGISTRAR EL INFORME FINAL                           -->
    <!-- ========================================================================= -->
    <div 
      v-if="exitoModal" 
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl p-7 max-w-md w-full shadow-2xl text-center border border-gray-100">
        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">
          ¡Informe final enviado!
        </h3>
        <p class="text-sm text-gray-600 mb-6">
          Tu informe final ha sido registrado de manera exitosa y se encuentra en revisión por el comité evaluador.
        </p>
        <button 
          @click="finalizarEnvio"
          class="w-full py-2.5 bg-[#000B58] hover:bg-[#000840] text-white text-sm font-semibold rounded-xl shadow-md transition cursor-pointer"
        >
          Aceptar
        </button>
      </div>
    </div>

  </div>
</template>

