<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import jsPDF from 'jspdf'

const props = defineProps({
  cvInicial: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['volver'])

// Obtener datos del usuario en sesión
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Estado del módulo
const cvs = ref([])
const cargando = ref(false)
const mostrandoWizard = ref(false)
const pasoActual = ref(0) // 0: Nombre, 1: Diseño, 2: Perfil, 3: Objetivos, 4: Logros, 5: Modal Exito Green, 6: Vista previa
const mostrandoModalExito = ref(false)
const cargandoGuardar = ref(false)
const mensajeExito = ref('')
const mensajeError = ref('')
const imgErrorWizard = ref(false)

const formatTexto = (val) => {
  if (!val) return ''
  if (typeof val === 'string') {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) return parsed.join('\n')
      if (typeof parsed === 'string') return parsed
    } catch (e) {
      return val
    }
    return val
  }
  if (Array.isArray(val)) return val.join('\n')
  return String(val)
}

const normalizarUrlFoto = (url) => {
  if (!url) return ''
  if (url.startsWith('data:') || url.startsWith('http://') || url.startsWith('https://')) return url
  if (url.startsWith('/api/storage/')) return url
  if (url.startsWith('api/storage/')) return '/' + url
  if (url.startsWith('/storage/')) return '/api' + url
  if (url.startsWith('storage/')) return '/api/' + url
  if (url.startsWith('/')) return url
  return '/api/storage/' + url
}

// Formulario del CV (inicia limpio para usar placeholders)
const formCv = ref({
  id: null,
  tituloCv: '',
  diseno: {
    color: '#010C67',
    fuente: 'Montserrat'
  },
  perfil: {
    fotoUrl: normalizarUrlFoto(usuario.value?.foto || usuario.value?.foto_url || usuario.value?.avatar || ''),
    nombre: usuario.value?.nombre_completo || ((usuario.value?.nombres || '') + ' ' + (usuario.value?.apellidos || '')).trim() || '',
    profesion: '',
    direccion: 'Usulután',
    email: usuario.value?.correo || usuario.value?.email || '',
    telefono: '',
    sobreMi: '',
    educacion: 'Universidad Gerardo Barrios (Egresado)'
  },
  objetivos: {
    objetivo: '',
    valores: '',
    conocimientos: '',
    idiomas: ''
  },
  logros: {
    certificados: '',
    habilidades: '',
    logros: '',
    proyectos: ''
  }
})

// Cargar CVs del usuario desde la API
const cargarCvs = async () => {
  cargando.value = true
  try {
    const userId = usuario.value.id || usuario.value.user_id
    if (!userId) return

    const res = await axios.get(`/api/cv/obtener/${userId}`, {
      params: { correo: usuario.value.correo || usuario.value.email }
    })
    if (res.data && res.data.cvs) {
      cvs.value = res.data.cvs
    }
  } catch (err) {
    console.warn('Error al cargar CVs:', err)
  } finally {
    cargando.value = false
  }
}

onMounted(async () => {
  await cargarCvs()
  if (props.cvInicial) {
    const cv = props.cvInicial
    formCv.value = {
      id: cv.id || null,
      tituloCv: cv.titulo_cv || 'Mi CV',
      diseno: {
        color: cv.color_plantilla || '#010C67',
        fuente: cv.fuente || 'Montserrat'
      },
      perfil: {
        fotoUrl: normalizarUrlFoto(cv.foto_url || cv.foto || cv.avatar || usuario.value?.foto || usuario.value?.foto_url || ''),
        nombre: cv.nombre_completo || usuario.value?.nombre_completo || ((usuario.value?.nombres || '') + ' ' + (usuario.value?.apellidos || '')).trim() || '',
        profesion: cv.profesion || '',
        direccion: cv.direccion || 'Usulután',
        email: cv.email || usuario.value?.correo || usuario.value?.email || '',
        telefono: cv.telefono || '',
        sobreMi: formatTexto(cv.sobre_mi),
        educacion: cv.educacion ? formatTexto(cv.educacion) : 'Universidad Gerardo Barrios (Egresado)'
      },
      objetivos: {
        objetivo: formatTexto(cv.objetivo),
        valores: formatTexto(cv.valores),
        conocimientos: formatTexto(cv.conocimientos),
        idiomas: formatTexto(cv.idiomas)
      },
      logros: {
        certificados: formatTexto(cv.certificados),
        habilidades: formatTexto(cv.habilidades),
        logros: formatTexto(cv.logros),
        proyectos: formatTexto(cv.proyectos_sociales)
      }
    }
    mostrandoWizard.value = true
    pasoActual.value = 2 // Directo a Editar Información de Perfil
  } else {
    // Si entra a crear/modificar su CV, abrir automáticamente el paso 0
    abrirCrearCv()
  }
})

// Inicializar la edición/creación del único CV del usuario
const abrirCrearCv = () => {
  const cvExistente = cvs.value && cvs.value.length > 0 ? cvs.value[0] : null
  formCv.value = {
    id: cvExistente ? cvExistente.id : null,
    tituloCv: cvExistente ? (cvExistente.titulo_cv || 'Mi CV') : 'Mi CV',
    diseno: {
      color: cvExistente?.color_plantilla || '#010C67',
      fuente: cvExistente?.fuente || 'Montserrat'
    },
    perfil: {
      fotoUrl: normalizarUrlFoto(cvExistente?.foto_url || cvExistente?.foto || cvExistente?.avatar || usuario.value?.foto || usuario.value?.foto_url || ''),
      nombre: cvExistente?.nombre_completo || usuario.value?.nombre_completo || ((usuario.value?.nombres || '') + ' ' + (usuario.value?.apellidos || '')).trim() || '',
      profesion: cvExistente?.profesion || '',
      direccion: cvExistente?.direccion || 'Usulután',
      email: cvExistente?.email || usuario.value?.correo || usuario.value?.email || '',
      telefono: cvExistente?.telefono || '',
      sobreMi: cvExistente?.sobre_mi ? formatTexto(cvExistente.sobre_mi) : '',
      educacion: cvExistente?.educacion ? formatTexto(cvExistente.educacion) : 'Universidad Gerardo Barrios (Egresado)'
    },
    objetivos: {
      objetivo: cvExistente?.objetivo ? formatTexto(cvExistente.objetivo) : '',
      valores: cvExistente?.valores ? formatTexto(cvExistente.valores) : '',
      conocimientos: cvExistente?.conocimientos ? formatTexto(cvExistente.conocimientos) : '',
      idiomas: cvExistente?.idiomas ? formatTexto(cvExistente.idiomas) : ''
    },
    logros: {
      certificados: cvExistente?.certificados ? formatTexto(cvExistente.certificados) : '',
      habilidades: cvExistente?.habilidades ? formatTexto(cvExistente.habilidades) : '',
      logros: cvExistente?.logros ? formatTexto(cvExistente.logros) : '',
      proyectos: cvExistente?.proyectos_sociales ? formatTexto(cvExistente.proyectos_sociales) : ''
    }
  }
  pasoActual.value = 0
  mostrandoModalExito.value = false
  mostrandoWizard.value = true
  mensajeError.value = ''
  mensajeExito.value = ''
}

// Subir foto de perfil (Base64)
const handleSubirFoto = (evento) => {
  const archivo = evento.target.files[0]
  if (!archivo) return
  if (!archivo.type.startsWith('image/')) {
    alert('Por favor seleccione una imagen válida.')
    return
  }
  const reader = new FileReader()
  reader.onload = (e) => {
    formCv.value.perfil.fotoUrl = e.target.result
  }
  reader.readAsDataURL(archivo)
}

const cancelarWizard = () => {
  mostrandoWizard.value = false
  emit('volver')
}

// Navegación entre pasos
const siguientePaso = () => {
  if (pasoActual.value === 0 && !formCv.value.tituloCv.trim()) {
    mensajeError.value = 'Por favor asigne un nombre al CV'
    return
  }
  mensajeError.value = ''
  if (pasoActual.value < 4) {
    pasoActual.value++
  } else if (pasoActual.value === 4) {
    // Al finalizar Paso 4 -> mostrar Modal Verde "Perfil completado"
    mostrandoModalExito.value = true
  }
}

const anteriorPaso = () => {
  if (pasoActual.value > 0) {
    pasoActual.value--
  } else {
    cancelarWizard()
  }
}

const verCvVistaPrevia = () => {
  mostrandoModalExito.value = false
  pasoActual.value = 6 // Pantalla Vista Previa (Captura 2)
}

// Generar PDF usando jsPDF
const generarPdfDoc = () => {
  const doc = new jsPDF('p', 'mm', 'a4')
  const colorHeader = formCv.value.diseno.color || '#010C67'
  
  doc.setFillColor(colorHeader)
  doc.rect(0, 0, 210, 35, 'F')
  
  doc.setTextColor(255, 255, 255)
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(18)
  doc.text(formCv.value.perfil.nombre || 'Dallana Lucia Campoz Garcia', 15, 18)
  
  doc.setFontSize(10)
  doc.setFont('helvetica', 'normal')
  doc.text(formCv.value.perfil.educacion || '', 15, 26)

  doc.setTextColor(40, 40, 40)
  doc.setFontSize(9)
  let y = 45

  doc.setFont('helvetica', 'bold')
  doc.text('DATOS DE CONTACTO', 15, y)
  y += 5
  doc.setFont('helvetica', 'normal')
  doc.text(`Email: ${formCv.value.perfil.email || ''}`, 15, y)
  y += 5
  doc.text(`Teléfono: ${formCv.value.perfil.telefono || ''}`, 15, y)
  y += 5
  doc.text(`Dirección: ${formCv.value.perfil.direccion || ''}`, 15, y)

  y += 10
  doc.setFont('helvetica', 'bold')
  doc.text('SOBRE MÍ', 15, y)
  y += 5
  doc.setFont('helvetica', 'normal')
  const lineasSobreMi = doc.splitTextToSize(formCv.value.perfil.sobreMi || '', 180)
  doc.text(lineasSobreMi, 15, y)
  y += (lineasSobreMi.length * 5) + 5

  doc.setFont('helvetica', 'bold')
  doc.text('EDUCACIÓN Y OBJETIVOS', 15, y)
  y += 5
  doc.setFont('helvetica', 'normal')
  doc.text(`Educación: ${formCv.value.perfil.educacion || ''}`, 15, y)
  y += 5
  const lineasObj = doc.splitTextToSize(`Objetivo: ${formCv.value.objetivos.objetivo || ''}`, 180)
  doc.text(lineasObj, 15, y)
  y += (lineasObj.length * 5) + 5

  doc.setFont('helvetica', 'bold')
  doc.text('CONOCIMIENTOS Y VALORES', 15, y)
  y += 5
  doc.setFont('helvetica', 'normal')
  const lineasCon = doc.splitTextToSize(`Conocimientos: ${formCv.value.objetivos.conocimientos || ''}`, 180)
  doc.text(lineasCon, 15, y)
  y += (lineasCon.length * 5) + 5
  const lineasVal = doc.splitTextToSize(`Valores: ${formCv.value.objetivos.valores || ''}`, 180)
  doc.text(lineasVal, 15, y)
  y += (lineasVal.length * 5) + 5

  doc.setFont('helvetica', 'bold')
  doc.text('CERTIFICADOS Y LOGROS', 15, y)
  y += 5
  doc.setFont('helvetica', 'normal')
  const lineasCert = doc.splitTextToSize(`Certificados: ${formCv.value.logros.certificados || ''}`, 180)
  doc.text(lineasCert, 15, y)

  return doc
}

// Descargar PDF directamente en el cliente
const descargarPdfDirecto = () => {
  const doc = generarPdfDoc()
  const nombre = (formCv.value.tituloCv || 'Curriculum_Vitae').replace(/\s+/g, '_') + '.pdf'
  doc.save(nombre)
}

// Imprimir CV
const imprimirCvDirecto = () => {
  window.print()
}

// Guardar CV en Backend y regresar a la lista
const guardarCvFinal = async () => {
  cargandoGuardar.value = true
  mensajeError.value = ''

  try {
    const doc = generarPdfDoc()
    const pdfBase64 = doc.output('datauristring')

    const payload = {
      usuario_id: usuario.value.id || 1,
      pdf_base64: pdfBase64,
      titulo_cv: formCv.value.tituloCv,
      cv_id: formCv.value.id,
      perfil: formCv.value.perfil,
      objetivos: formCv.value.objetivos,
      logros: formCv.value.logros,
      diseno: formCv.value.diseno
    }

    const res = await axios.post('/api/cv/guardar', payload)

    if (res.data) {
      mensajeExito.value = '¡Currículum vitae guardado exitosamente!'
      mostrandoWizard.value = false
      await cargarCvs()
      // Redirigir automáticamente al Inicio
      emit('volver')
    }
  } catch (err) {
    mensajeError.value = err.response?.data?.mensaje || 'Error al guardar el CV'
  } finally {
    cargandoGuardar.value = false
  }
}

// Eliminar CV
const eliminarCv = async (cvId) => {
  if (!confirm('¿Está seguro de eliminar este currículum vitae?')) return
  try {
    await axios.delete(`/api/cv/eliminar/${cvId}`)
    await cargarCvs()
  } catch (err) {
    alert('No se pudo eliminar el CV.')
  }
}
</script>

<template>
  <div class="p-2 sm:p-4">
    <!-- Header del Módulo -->
    <div v-if="!mostrandoWizard" class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-950 font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
          Gestión de Currículum (CV)
        </h1>
        <p class="text-sm text-gray-600 font-sans mt-0.5">
          Crea, personaliza y descarga tus currículums profesionales en formato PDF
        </p>
      </div>

      <button
        @click="abrirCrearCv"
        class="bg-[#010C67] hover:bg-[#01094f] text-white px-5 py-2.5 rounded-[18px] text-sm font-medium transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
        style="font-family: 'Lora', Georgia, serif;"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>Crear nuevo CV</span>
      </button>
    </div>

    <!-- Alertas -->
    <div v-if="mensajeExito" class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
      <span>{{ mensajeExito }}</span>
      <button @click="mensajeExito = ''" class="font-bold ml-2 text-emerald-900">&times;</button>
    </div>

    <!-- Listado de CVs Guardados -->
    <div v-if="cargando" class="text-center py-12">
      <div class="w-8 h-8 border-4 border-[#010C67] border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
      <p class="text-sm text-gray-600">Cargando currículums...</p>
    </div>

    <div v-else-if="cvs.length === 0 && !mostrandoWizard" class="bg-white rounded-2xl p-10 text-center border border-gray-200 shadow-sm my-6">
      <div class="w-16 h-16 bg-blue-50 text-[#010C67] rounded-full flex items-center justify-center mx-auto mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-900 font-serif" style="font-family: 'Lora', Georgia, serif;">Aún no tienes currículums creados</h3>
      <p class="text-xs text-gray-600 max-w-md mx-auto mt-1 mb-5">
        Diseña tu perfil profesional paso a paso de forma rápida y genera tu documento listo para enviar a pasantías.
      </p>
      <button
        @click="abrirCrearCv"
        class="bg-[#010C67] hover:bg-[#01094f] text-white px-6 py-2.5 rounded-[18px] text-sm font-medium transition-all shadow-md inline-flex items-center gap-2 cursor-pointer"
        style="font-family: 'Lora', Georgia, serif;"
      >
        <span>Empezar ahora</span>
      </button>
    </div>

    <div v-else-if="!mostrandoWizard" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div 
        v-for="cv in cvs" 
        :key="cv.id"
        class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-2 mb-3">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-[#010C67]">
              {{ cv.estado || 'activo' }}
            </span>
            <span class="text-[11px] text-gray-400">
              {{ new Date(cv.created_at || Date.now()).toLocaleDateString() }}
            </span>
          </div>

          <h4 class="text-base font-bold text-gray-950 font-serif line-clamp-1" style="font-family: 'Lora', Georgia, serif;">
            {{ cv.titulo_cv }}
          </h4>
          <p class="text-xs text-gray-600 mt-1 line-clamp-1">
            {{ cv.nombre_completo || 'Sin nombre asignado' }}
          </p>
        </div>

        <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
          <a
            v-if="cv.url_publica"
            :href="normalizarUrlFoto(cv.url_publica)"
            target="_blank"
            class="text-[#00589B] text-xs font-semibold hover:underline flex items-center gap-1 cursor-pointer"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Ver PDF</span>
          </a>
          <button
            @click="eliminarCv(cv.id)"
            class="text-red-600 hover:text-red-800 text-xs font-medium cursor-pointer"
          >
            Eliminar
          </button>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════ -->
    <!-- WIZARD / MODAL DE CREACIÓN DE CV                             -->
    <!-- ═════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div 
        v-if="mostrandoWizard"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#071329]/50 backdrop-blur-[2px] overflow-y-auto"
      >
      <!-- ========================================================= -->
      <!-- PASO 0: MODAL NOMBRAR CV                                 -->
      <!-- ========================================================= -->
      <div 
        v-if="pasoActual === 0"
        class="relative w-full max-w-[580px] bg-white rounded-[22px] shadow-2xl overflow-hidden my-auto"
      >
        <div class="bg-[#010C67] text-white px-6 py-4 text-center">
          <h3 class="text-base sm:text-lg font-bold font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            Bienvenido a Génesis Profesional - Completa tu perfil profesional
          </h3>
        </div>

        <div class="p-6 sm:p-8 text-center">
          <h2 class="text-xl sm:text-2xl font-bold text-gray-900 font-serif mb-6" style="font-family: 'Lora', Georgia, serif;">
            Inicia la creación de tu perfil profesional
          </h2>

          <div class="bg-[#f0f3ff] rounded-2xl p-6 text-left border border-blue-100 mb-6">
            <label class="block text-sm font-bold text-[#010C67] mb-2 flex items-center gap-2" style="font-family: 'Lora', Georgia, serif;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41 41" fill="none" class="w-5 h-5 text-black shrink-0"><g clip-path="url(#clip0_6153_508_tag)"><path d="M33.4552 18.852C33.9572 19.27 34.238 19.8063 34.2978 20.4609C34.3575 21.1155 34.1784 21.6944 33.7605 22.1975L24.6397 33.1498C24.2217 33.6518 23.6851 33.9324 23.0299 33.9916C22.3747 34.0509 21.7961 33.872 21.2942 33.4551L11.2546 25.0945C10.9656 24.8538 10.7457 24.561 10.5951 24.216C10.4444 23.871 10.3801 23.5151 10.4023 23.1482L10.9462 12.4725L21.3472 10.0054C21.7045 9.91657 22.0665 9.91503 22.433 10.0008C22.7995 10.0865 23.127 10.25 23.4155 10.4913L33.4552 18.852Z" fill="currentColor"/></g><defs><clipPath id="clip0_6153_508_tag"><rect width="28.5055" height="28.5055" fill="white" transform="translate(21.9048 40.1461) rotate(-140.214)"/></clipPath></defs></svg>
              <span>Ponle un nombre a tu cv<span class="text-red-500">*</span></span>
            </label>

            <input 
              v-model="formCv.tituloCv"
              type="text"
              placeholder="Ej. *CV para pasantias en Redes*"
              class="w-full px-4 py-3 rounded-xl bg-white border border-gray-300 text-gray-900 text-sm focus:outline-none focus:border-[#010C67]"
            />

            <p class="text-xs text-[#00589B] mt-2.5 font-sans">
              Este nombre te ayudará a identificarlo entre varios curriculums guardados
            </p>
          </div>

          <p v-if="mensajeError" class="text-red-600 text-xs mb-4 font-medium">{{ mensajeError }}</p>

          <div class="flex items-center justify-between pt-2">
            <button 
              type="button" 
              @click="cancelarWizard"
              class="w-[140px] h-[45px] rounded-[18px] bg-[#67000F] hover:bg-[#52000c] text-white text-sm font-medium transition-all cursor-pointer shadow-md"
              style="font-family: 'Lora', Georgia, serif;"
            >
              Cancelar
            </button>

            <button 
              type="button" 
              @click="siguientePaso"
              class="w-[140px] h-[45px] rounded-[18px] bg-[#010C67] hover:bg-[#01094f] text-white text-sm font-medium transition-all cursor-pointer shadow-md"
              style="font-family: 'Lora', Georgia, serif;"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- PANTALLA: VISTA PREVIA DEL CV ("Tu perfil ya casi está listo.") (Captura 2) -->
      <!-- ========================================================= -->
      <div 
        v-else-if="pasoActual === 6"
        class="relative w-full max-w-[950px] bg-white rounded-[22px] shadow-2xl overflow-hidden my-auto flex flex-col max-h-[95vh]"
      >
        <!-- Header Banner -->
        <div class="bg-[#010C67] text-white px-6 py-4 text-center shrink-0">
          <h3 class="text-lg sm:text-xl font-bold font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            Tu perfil ya casi está listo.
          </h3>
        </div>

        <div class="p-6 sm:p-8 overflow-y-auto flex-1 text-center">
          <h2 class="text-2xl font-bold text-gray-900 font-serif mb-6" style="font-family: 'Lora', Georgia, serif;">
            Vista previa
          </h2>

          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Plantilla CV Renderizada (Lado Izquierdo) -->
            <div class="lg:col-span-8 bg-white border border-gray-300 rounded-xl shadow-lg p-6 text-left space-y-4">
              <!-- Encabezado con color elegido -->
              <div class="p-5 rounded-lg text-white transition-colors flex items-center gap-4" :style="{ backgroundColor: formCv.diseno.color }">
                <div class="w-16 h-16 rounded-full bg-white/20 border border-white flex items-center justify-center overflow-hidden shrink-0">
                  <img v-if="formCv.perfil.fotoUrl" :src="formCv.perfil.fotoUrl" class="w-full h-full object-cover" />
                  <span v-else class="text-2xl text-white">👤</span>
                </div>
                <div>
                  <h3 class="text-xl font-bold font-serif" :style="{ fontFamily: formCv.diseno.fuente }">
                    {{ formCv.perfil.nombre || 'Dallana Lucia Campoz Garcia' }}
                  </h3>
                  <p v-if="formCv.perfil.educacion" class="text-xs opacity-90 font-sans">
                    {{ formCv.perfil.educacion }}
                  </p>
                </div>
              </div>

              <!-- Contacto y Educación -->
              <div class="grid grid-cols-2 gap-4 text-xs">
                <div class="space-y-1.5">
                  <p class="flex items-center gap-1.5 font-bold text-[#010C67]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41 41" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M15.5003 19.6459C14.3676 19.6459 13.2813 19.196 12.4804 18.395C11.6795 17.5941 11.2295 16.5078 11.2295 15.3751C11.2295 14.2424 11.6795 13.1561 12.4804 12.3551C13.2813 11.5542 14.3676 11.1042 15.5003 11.1042C16.633 11.1042 17.7193 11.5542 18.5203 12.3551C19.3212 13.1561 19.7712 14.2424 19.7712 15.3751C19.7712 15.9359 19.6607 16.4913 19.4461 17.0095C19.2314 17.5276 18.9168 17.9984 18.5203 18.395C18.1237 18.7916 17.6529 19.1062 17.1347 19.3208C16.6165 19.5354 16.0612 19.6459 15.5003 19.6459ZM15.5003 3.41675C12.3288 3.41675 9.28713 4.67664 7.04451 6.91926C4.80188 9.16189 3.54199 12.2035 3.54199 15.3751C3.54199 24.3438 15.5003 37.5834 15.5003 37.5834C15.5003 37.5834 27.4587 24.3438 27.4587 15.3751C27.4587 12.2035 26.1988 9.16189 23.9561 6.91926C21.7135 4.67664 18.6719 3.41675 15.5003 3.41675Z" fill="currentColor"/></svg>
                    <span>Dirección:</span>
                    <span class="font-normal text-gray-800 ml-1">{{ formCv.perfil.direccion }}</span>
                  </p>
                  <p class="flex items-center gap-1.5 font-bold text-[#010C67]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 39" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M32.5 29.25H29.25V15.0312L19.5 21.125L9.75 15.0312V29.25H6.5V9.75H8.45L19.5 16.6562L30.55 9.75H32.5M32.5 6.5H6.5C4.69625 6.5 3.25 7.94625 3.25 9.75V29.25C3.25 30.112 3.59241 30.9386 4.2019 31.5481C4.8114 32.1576 5.63805 32.5 6.5 32.5H32.5C33.362 32.5 34.1886 32.1576 34.7981 31.5481C35.4076 30.9386 35.75 30.112 35.75 29.25V9.75C35.75 8.88805 35.4076 8.0614 34.7981 7.4519C34.1886 6.84241 33.362 6.5 32.5 6.5Z" fill="currentColor"/></svg>
                    <span>Email:</span>
                    <span class="font-normal text-gray-800 ml-1">{{ formCv.perfil.email }}</span>
                  </p>
                  <p class="flex items-center gap-1.5 font-bold text-[#010C67]">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 34" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M26.0809 31.1668H26.6051C27.0442 31.1526 27.4551 30.926 27.7101 30.5576L30.9259 25.9251C31.1384 25.6135 31.2234 25.231 31.1526 24.8485C31.1178 24.662 31.046 24.4844 30.9414 24.3263C30.8367 24.1681 30.7014 24.0325 30.5434 23.9276L23.5876 19.2951C23.0067 18.9126 22.2276 18.9976 21.7601 19.5076L19.0967 22.3835C18.0201 21.746 16.2209 20.5985 14.8042 19.1818C13.3876 17.7651 12.2401 15.966 11.6026 14.9035L14.4784 12.2401C14.9884 11.7726 15.0876 10.9935 14.6909 10.4126L10.0584 3.4568C9.8459 3.14513 9.52007 2.91847 9.15173 2.84763C8.76923 2.7768 8.38673 2.84763 8.07507 3.0743L3.44257 6.27597C3.07423 6.53097 2.84757 6.9418 2.8334 7.38097C2.7909 8.3868 2.60673 17.3543 9.61923 24.3526C15.9376 30.671 23.8426 31.1526 26.0809 31.1526V31.1668Z" fill="currentColor"/></svg>
                    <span>Teléfono:</span>
                    <span class="font-normal text-gray-800 ml-1">{{ formCv.perfil.telefono }}</span>
                  </p>
                </div>
                <div class="space-y-2">
                  <div>
                    <p class="flex items-center gap-1.5 font-bold text-[#010C67]">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 34" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><g clip-path="url(#clip0_2016_494_pv)"><path d="M30.5226 0.175559C30.6635 0.269819 30.7791 0.397321 30.859 0.546808C30.939 0.696295 30.9809 0.863171 30.9811 1.0327V16.5232C30.9811 16.7295 30.9193 16.931 30.8037 17.1018C30.6881 17.2726 30.524 17.4049 30.3326 17.4816L30.3264 17.4837L30.314 17.4899L30.2665 17.5084C29.995 17.6165 29.7217 17.7198 29.4465 17.8183C28.9013 18.0145 28.1433 18.2788 27.2841 18.5411C25.5987 19.0616 23.4032 19.6213 21.6868 19.6213C19.9374 19.6213 18.4896 19.043 17.2297 18.537L17.1718 18.5164C15.8624 17.9897 14.747 17.5559 13.4252 17.5559C11.9794 17.5559 10.042 18.031 8.39179 18.5411C7.65303 18.7718 6.9209 19.0232 6.19626 19.295V32.0138C6.19626 32.2877 6.08746 32.5503 5.89379 32.744C5.70012 32.9377 5.43745 33.0465 5.16356 33.0465C4.88967 33.0465 4.627 32.9377 4.43333 32.744C4.23966 32.5503 4.13086 32.2877 4.13086 32.0138V1.0327C4.13086 0.758813 4.23966 0.496141 4.43333 0.302472C4.627 0.108802 4.88967 0 5.16356 0C5.43745 0 5.70012 0.108802 5.89379 0.302472C6.08746 0.496141 6.19626 0.758813 6.19626 1.0327V1.61515C6.66305 1.45198 7.22071 1.26403 7.82793 1.07814C9.51331 0.56179 11.7109 0 13.4252 0C15.1601 0 16.5729 0.572117 17.8059 1.07195L17.8947 1.10912C19.1794 1.62754 20.2989 2.06541 21.6868 2.06541C23.1326 2.06541 25.0699 1.59036 26.7202 1.08021C27.6604 0.785826 28.5897 0.457905 29.5064 0.097074L29.5457 0.0826162L29.5539 0.0784854H29.556" fill="currentColor"/></g><defs><clipPath id="clip0_2016_494_pv"><rect width="33.0465" height="33.0465" fill="white"/></clipPath></defs></svg>
                      <span>Idiomas:</span>
                    </p>
                    <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.objetivos.idiomas }}</p>
                  </div>
                </div>
              </div>

              <!-- Sobre mi -->
              <div class="text-xs pt-2 border-t border-gray-100">
                <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 35 35" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M29.5312 0H3.28125C1.47656 0 0 1.47656 0 3.28125V31.7188C0 33.5234 1.47656 35 3.28125 35H29.5312C31.3359 35 32.8125 33.5234 32.8125 31.7188V3.28125C32.8125 1.47656 31.3359 0 29.5312 0ZM28.4375 30.625H4.375V4.375H28.4375V30.625ZM8.75 19.6875H24.0625V21.875H8.75V19.6875ZM8.75 24.0625H24.0625V26.25H8.75V24.0625ZM10.9375 9.84375C10.9376 9.41271 11.0227 8.98591 11.1878 8.58773C11.3529 8.18956 11.5948 7.8278 11.8996 7.5231C12.2045 7.21841 12.5665 6.97676 12.9648 6.81193C13.363 6.64711 13.7899 6.56236 14.2209 6.5625C14.652 6.56264 15.0788 6.64769 15.477 6.81277C15.8751 6.97786 16.2369 7.21975 16.5416 7.52465C16.8463 7.82955 17.0879 8.19147 17.2528 8.58976C17.4176 8.98804 17.5023 9.41489 17.5022 9.84594C17.5019 10.7165 17.1558 11.5512 16.54 12.1666C15.9243 12.7819 15.0893 13.1275 14.2188 13.1272C13.3482 13.1269 12.5135 12.7808 11.8981 12.165C11.2827 11.5493 10.9372 10.7143 10.9375 9.84375ZM16.4062 13.125H12.0312C10.2266 13.125 8.75 14.1094 8.75 15.3125V17.5H19.6875V15.3125C19.6875 14.1094 18.2109 13.125 16.4062 13.125Z" fill="currentColor"/></svg>
                  <span>Sobre mí:</span>
                </p>
                <p class="text-gray-800 ml-5">{{ formCv.perfil.sobreMi }}</p>
              </div>

              <!-- Conocimientos y Valores -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 39" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M35.6383 21.8333V29.1586C35.6383 30.4474 35.1263 31.6835 34.2149 32.5949C33.3035 33.5063 32.0674 34.0183 30.7785 34.0183H8.09952C6.81063 34.0183 5.57453 33.5063 4.66314 32.5949C3.75176 31.6835 3.23975 30.4474 3.23975 29.1586V21.8333L4.13556 22.282C8.88345 24.6733 14.1257 25.9187 19.4418 25.9181C24.7579 25.9176 29.9999 24.6711 34.7473 22.2787L35.6383 21.8333ZM22.6789 3.23975C23.9677 3.23975 25.2039 3.75176 26.1152 4.66314C27.0266 5.57453 27.5386 6.81063 27.5386 8.09952V9.71945H30.7785C32.0674 9.71945 33.3035 10.2315 34.2149 11.1428C35.1263 12.0542 35.6383 13.2903 35.6383 14.5792V18.2111L33.2942 19.3839C29.1003 21.4981 24.4781 22.6244 19.7817 22.6767C15.0853 22.7289 10.4393 21.7056 6.19935 19.6852L5.15126 19.1669L3.23975 18.2111V14.5792C3.23975 13.2903 3.75176 12.0542 4.66314 11.1428C5.57453 10.2315 6.81063 9.71945 8.09952 9.71945H11.3394V8.09952C11.3394 6.81063 11.8514 5.57453 12.7628 4.66314C13.6742 3.75176 14.9103 3.23975 16.1992 3.23975H22.6789ZM19.439 16.1992C19.0094 16.1992 18.5973 16.3698 18.2935 16.6736C17.9897 16.9774 17.8191 17.3894 17.8191 17.8191C17.818 18.0318 17.8589 18.2427 17.9393 18.4396C18.0197 18.6366 18.1381 18.8157 18.2878 18.9669C18.4375 19.1181 18.6155 19.2383 18.8116 19.3207C19.0077 19.4031 19.2182 19.446 19.4309 19.4471C19.6436 19.4482 19.8545 19.4073 20.0514 19.3269C20.2484 19.2465 20.4276 19.128 20.5787 18.9784C20.7299 18.8287 20.8501 18.6507 20.9325 18.4546C21.0149 18.2584 21.0579 18.048 21.0589 17.8353C21.0589 16.9249 20.3332 16.1992 19.439 16.1992ZM22.6789 6.4796H16.1992C15.7695 6.4796 15.3575 6.65027 15.0537 6.95406C14.7499 7.25786 14.5792 7.66989 14.5792 8.09952V9.71945H24.2988V8.09952C24.2988 7.66989 24.1281 7.25786 23.8243 6.95406C23.5205 6.65027 23.1085 6.4796 22.6789 6.4796Z" fill="currentColor"/></svg>
                    <span>Conocimientos:</span>
                  </p>
                  <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.objetivos.conocimientos }}</p>
                </div>
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 46 46" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M23.9322 25.0333L30.3294 31.5133C31.4032 32.6061 32.0048 34.0769 32.0048 35.6089C32.0048 37.141 31.4032 38.6118 30.3294 39.7046C29.3458 40.7067 28.0281 41.3122 26.627 41.4059C25.226 41.4996 23.8394 41.075 22.7311 40.2129L22.5899 40.0962L22.4525 40.2129C21.5508 40.9145 20.4597 41.3302 19.3198 41.4065L18.9376 41.4197C18.177 41.4199 17.4239 41.2685 16.7225 40.9744C16.021 40.6802 15.3852 40.2492 14.8523 39.7065C13.7773 38.6129 13.1752 37.1407 13.1759 35.6072C13.1766 34.0738 13.7801 32.6021 14.856 31.5095L21.2513 25.0333C21.4265 24.8557 21.6352 24.7147 21.8653 24.6184C22.0954 24.5222 22.3423 24.4726 22.5918 24.4726C22.8412 24.4726 23.0881 24.5222 23.3183 24.6184C23.5484 24.7147 23.7571 24.8557 23.9322 25.0333ZM39.7049 14.8539C40.7075 15.8376 41.3133 17.1557 41.407 18.5571C41.5007 19.9586 41.0758 21.3456 40.2132 22.4541L40.0965 22.5915L40.2132 22.7327C40.9148 23.6344 41.3305 24.7254 41.4068 25.8654L41.4199 26.2476C41.4202 27.0082 41.2688 27.7613 40.9746 28.4627C40.6805 29.1642 40.2495 29.7999 39.7067 30.3329C38.6132 31.4079 37.1409 32.0099 35.6075 32.0092C34.0741 32.0085 32.6023 31.4051 31.5098 30.3291L25.0335 23.9338C24.8559 23.7587 24.7149 23.55 24.6187 23.3199C24.5224 23.0898 24.4729 22.8428 24.4729 22.5934C24.4729 22.344 24.5224 22.097 24.6187 21.8669C24.7149 21.6368 24.8559 21.4281 25.0335 21.253L31.5154 14.8539C32.6082 13.7801 34.079 13.1785 35.6111 13.1785C37.1432 13.1785 38.6121 13.7801 39.7049 14.8539ZM13.6756 14.8577L20.1519 21.253C20.3295 21.4281 20.4705 21.6368 20.5667 21.8669C20.663 22.097 20.7126 22.344 20.7126 22.5934C20.7126 22.8428 20.663 23.0898 20.5667 23.3199C20.4705 23.55 20.3295 23.7587 20.1519 23.9338L13.6681 30.3291C12.5753 31.4029 11.1045 32.0046 9.57245 32.0046C8.04039 32.0046 6.5696 31.4029 5.47679 30.3291C4.47472 29.3455 3.86921 28.0278 3.77548 26.6268C3.68176 25.2258 4.10635 23.8392 4.96848 22.7308L5.08521 22.5896L4.96848 22.4522C4.26685 21.5505 3.85115 20.4595 3.7749 19.3195L3.76172 18.9373C3.76172 17.4011 4.37922 15.9289 5.47491 14.852C6.56846 13.777 8.04073 13.175 9.57416 13.1757C11.1076 13.1764 12.5793 13.7798 13.6719 14.8558M26.2441 3.76522C27.0047 3.76498 27.7578 3.91636 28.4592 4.21052C29.1607 4.50468 29.7964 4.93571 30.3294 5.47842C31.4044 6.57196 32.0064 8.04423 32.0057 9.57767C32.005 11.1111 31.4016 12.5828 30.3256 13.6754L23.9303 20.1516C23.7552 20.3292 23.5465 20.4702 23.3164 20.5665C23.0863 20.6627 22.8393 20.7123 22.5899 20.7123C22.3405 20.7123 22.0935 20.6627 21.8634 20.5665C21.6333 20.4702 21.4246 20.3292 21.2495 20.1516L14.8542 13.6679C13.7804 12.575 13.1787 11.1043 13.1787 9.57219C13.1787 8.04013 13.7804 6.56934 14.8542 5.47653C15.8378 4.47446 17.1555 3.86895 18.5565 3.77522C19.9575 3.6815 21.3441 4.10609 22.4525 4.96822L22.5899 5.08495L22.7311 4.96822C23.6328 4.26659 24.7238 3.85089 25.8638 3.77464L26.2441 3.76522Z" fill="currentColor"/></svg>
                    <span>Valores profesionales:</span>
                  </p>
                  <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.objetivos.valores }}</p>
                </div>
              </div>

              <!-- Objetivo y Habilidades -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 5" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M1 2.30965C1 2.657 1.13798 2.99011 1.38359 3.23572C1.6292 3.48133 1.96231 3.61931 2.30965 3.61931C2.657 3.61931 2.99011 3.48133 3.23572 3.23572C3.48133 2.99011 3.61931 2.657 3.61931 2.30965C3.61931 1.96231 3.48133 1.6292 3.23572 1.38359C2.99011 1.13798 2.657 1 2.30965 1C1.96231 1 1.6292 1.13798 1.38359 1.38359C1.13798 1.6292 1 1.96231 1 2.30965Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Mi objetivo:</span>
                  </p>
                  <p class="text-gray-800 ml-5">{{ formCv.objetivos.objetivo }}</p>
                </div>
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41 41" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M21.359 22.3415L27.0683 28.1248C28.0266 29.1001 28.5636 30.4127 28.5636 31.78C28.5636 33.1473 28.0266 34.46 27.0683 35.4353C26.1905 36.3296 25.0145 36.87 23.7641 36.9537C22.5137 37.0373 21.2762 36.6584 20.2871 35.8889L20.161 35.7848L20.0384 35.8889C19.2337 36.5151 18.2599 36.8861 17.2425 36.9542L16.9015 36.9659C16.2226 36.9662 15.5505 36.831 14.9245 36.5685C14.2985 36.306 13.7311 35.9213 13.2554 35.437C12.2961 34.461 11.7587 33.147 11.7594 31.7785C11.76 30.4099 12.2985 29.0965 13.2588 28.1214L18.9664 22.3415C19.1227 22.183 19.309 22.0572 19.5144 21.9713C19.7197 21.8854 19.9401 21.8411 20.1627 21.8411C20.3853 21.8411 20.6057 21.8854 20.8111 21.9713C21.0165 22.0572 21.2027 22.183 21.359 22.3415ZM35.4357 13.2567C36.3305 14.1347 36.8712 15.311 36.9548 16.5618C37.0385 17.8125 36.6592 19.0504 35.8893 20.0397L35.7852 20.1623L35.8893 20.2883C36.5155 21.0931 36.8865 22.0668 36.9546 23.0842L36.9663 23.4253C36.9666 24.1041 36.8315 24.7762 36.5689 25.4022C36.3064 26.0282 35.9217 26.5956 35.4374 27.0713C34.4614 28.0307 33.1474 28.568 31.7789 28.5674C30.4103 28.5667 29.0969 28.0282 28.1218 27.0679L22.3419 21.3603C22.1834 21.204 22.0576 21.0177 21.9717 20.8124C21.8858 20.607 21.8415 20.3866 21.8415 20.164C21.8415 19.9414 21.8858 19.721 21.9717 19.5156C22.0576 19.3103 22.1834 19.124 22.3419 18.9677L28.1268 13.2567C29.1021 12.2984 30.4148 11.7614 31.7821 11.7614C33.1494 11.7614 34.4604 12.2984 35.4357 13.2567ZM12.2053 13.2601L17.9852 18.9677C18.1437 19.124 18.2695 19.3103 18.3554 19.5156C18.4414 19.721 18.4856 19.9414 18.4856 20.164C18.4856 20.3866 18.4414 20.607 18.3554 20.8124C18.2695 21.0177 18.1437 21.204 17.9852 21.3603L12.1986 27.0679C11.2233 28.0262 9.91067 28.5632 8.54334 28.5632C7.17601 28.5632 5.86338 28.0262 4.88808 27.0679C3.99375 26.1901 3.45335 25.0141 3.36971 23.7637C3.28606 22.5133 3.665 21.2758 4.43443 20.2867L4.5386 20.1606L4.43443 20.038C3.80824 19.2333 3.43724 18.2595 3.36918 17.2421L3.35742 16.9011C3.35742 15.53 3.90853 14.2161 4.8864 13.255C5.86236 12.2957 7.17632 11.7583 8.54487 11.759C9.91342 11.7596 11.2269 12.2981 12.202 13.2584M23.4223 3.36039C24.1011 3.36017 24.7732 3.49527 25.3992 3.7578C26.0253 4.02033 26.5927 4.40501 27.0683 4.88936C28.0277 5.86532 28.565 7.17928 28.5644 8.54783C28.5638 9.91638 28.0252 11.2298 27.065 12.2049L21.3573 17.9848C21.201 18.1433 21.0148 18.2691 20.8094 18.355C20.604 18.441 20.3837 18.4852 20.161 18.4852C19.9384 18.4852 19.718 18.441 19.5127 18.355C19.3073 18.2691 19.1211 18.1433 18.9647 17.9848L13.2571 12.1982C12.2988 11.2229 11.7618 9.91027 11.7618 8.54294C11.7618 7.17562 12.2988 5.86298 13.2571 4.88768C14.135 3.99335 15.311 3.45296 16.5614 3.36931C17.8117 3.28566 19.0492 3.6646 20.0384 4.43403L20.161 4.5382L20.2871 4.43403C21.0918 3.80784 22.0655 3.43684 23.0829 3.36879L23.4223 3.36039Z" fill="currentColor"/></svg>
                    <span>Habilidades:</span>
                  </p>
                  <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.logros.habilidades }}</p>
                </div>
              </div>

              <!-- Certificados y Logros -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 28" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M29.2208 16.7698V23.3766C29.2208 24.5391 28.759 25.6539 27.937 26.4759C27.115 27.7597 26.0001 27.7597 24.8376 27.7597H4.38311C3.22064 27.7597 2.10578 27.2979 1.28378 26.4759C0.461791 25.6539 0 24.5391 0 23.3766V16.7698L0.807954 17.1745C5.09015 19.3313 9.81823 20.4545 14.6129 20.454C19.4076 20.4535 24.1355 19.3293 28.4172 17.1716L29.2208 16.7698ZM17.5325 0C18.6949 0 19.8098 0.461791 20.6318 1.28378C21.4538 2.10578 21.9156 3.22064 21.9156 4.38311V5.84415H24.8376C26.0001 5.84415 27.115 6.30594 27.937 7.12794C28.759 7.94993 29.2208 9.06479 29.2208 10.2273V13.5029L27.1066 14.5607C23.3241 16.4675 19.1553 17.4834 14.9195 17.5305C10.6837 17.5776 6.49337 16.6547 2.66932 14.8325L1.72403 14.3649L0 13.5029V10.2273C0 9.06479 0.461791 7.94993 1.28378 7.12794C2.10578 6.30594 3.22064 5.84415 4.38311 5.84415H7.30519V4.38311C7.30519 3.22064 7.76698 2.10578 8.58897 1.28378C9.41097 0.461791 10.5258 0 11.6883 0H17.5325ZM14.6104 11.6883C14.2229 11.6883 13.8513 11.8422 13.5773 12.1162C13.3033 12.3902 13.1493 12.7619 13.1493 13.1493C13.1484 13.3412 13.1852 13.5314 13.2578 13.709C13.3303 13.8866 13.4371 14.0482 13.5721 14.1846C13.7071 14.3209 13.8676 14.4294 14.0445 14.5037C14.2214 14.578 14.4112 14.6167 14.6031 14.6177C14.7949 14.6186 14.9851 14.5818 15.1627 14.5093C15.3404 14.4367 15.502 14.3299 15.6383 14.1949C15.7747 14.0599 15.8831 13.8994 15.9574 13.7225C16.0317 13.5456 16.0705 13.3558 16.0714 13.164C16.0714 12.3428 15.4169 11.6883 14.6104 11.6883ZM17.5325 2.92208H11.6883C11.3008 2.92208 10.9292 3.07601 10.6552 3.35C10.3812 3.624 10.2273 3.99562 10.2273 4.38311V5.84415H18.9935V4.38311C18.9935 3.99562 18.8396 3.624 18.5656 3.35C18.2916 3.07601 17.9199 2.92208 17.5325 2.92208Z" fill="currentColor"/></svg>
                    <span>Certificados:</span>
                  </p>
                  <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.logros.certificados }}</p>
                </div>
                <div>
                  <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M3.42188 13.6873C3.42187 15.0353 3.6874 16.3702 4.20328 17.6156C4.71916 18.8611 5.47531 19.9927 6.42854 20.946C7.38176 21.8992 8.51341 22.6553 9.75887 23.1712C11.0043 23.6871 12.3392 23.9526 13.6873 23.9526C15.0353 23.9526 16.3702 23.6871 17.6156 23.1712C18.8611 22.6553 19.9927 21.8992 20.946 20.946C21.8992 19.9927 22.6553 18.8611 23.1712 17.6156C23.6871 16.3702 23.9526 15.0353 23.9526 13.6873C23.9526 12.3392 23.6871 11.0043 23.1712 9.75887C22.6553 8.51341 21.8992 7.38176 20.946 6.42854C19.9927 5.47531 18.8611 4.71916 17.6156 4.20328C16.3702 3.6874 15.0353 3.42188 13.6873 3.42188C12.3392 3.42188 11.0043 3.6874 9.75887 4.20328C8.51341 4.71916 7.38176 5.47531 6.42854 6.42854C5.47531 7.38176 4.71916 8.51341 4.20328 9.75887C3.6874 11.0043 3.42187 12.3392 3.42188 13.6873Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.98438 13.6874C7.98438 15.1999 8.58522 16.6505 9.65474 17.72C10.7243 18.7895 12.1748 19.3904 13.6874 19.3904C15.1999 19.3904 16.6505 18.7895 17.72 17.72C18.7895 16.6505 19.3904 15.1999 19.3904 13.6874C19.3904 12.1748 18.7895 10.7243 17.72 9.65474C16.6505 8.58522 15.1999 7.98438 13.6874 7.98438C12.1748 7.98438 10.7243 8.58522 9.65474 9.65474C8.58522 10.7243 7.98438 12.1748 7.98438 13.6874Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.69523 22.812L13.6873 13.6873L6.27344 20.5308" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5469 13.6872C12.5469 13.9897 12.667 14.2798 12.8809 14.4938C13.0949 14.7077 13.385 14.8278 13.6875 14.8278C13.99 14.8278 14.2801 14.7077 14.494 14.4938C14.7079 14.2798 14.8281 13.9897 14.8281 13.6872C14.8281 13.3847 14.7079 13.0946 14.494 12.8807C14.2801 12.6668 13.99 12.5466 13.6875 12.5466C13.385 12.5466 13.0949 12.6668 12.8809 12.8807C12.667 13.0946 12.5469 13.3847 12.5469 13.6872Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Logros:</span>
                  </p>
                  <p class="text-gray-800 whitespace-pre-line ml-5">{{ formCv.logros.logros }}</p>
                </div>
              </div>

              <!-- Proyectos -->
              <div class="text-xs pt-2 border-t border-gray-100">
                <p class="font-bold text-[#010C67] mb-1 flex items-center gap-1.5">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31 31" fill="none" class="w-3.5 h-3.5 text-[#010C67] shrink-0"><path d="M8.27646 26.2705C7.70018 26.2705 7.21939 26.0779 6.83409 25.6926C6.44878 25.3073 6.25572 24.8265 6.25488 24.2502V5.77326C6.25488 5.19781 6.44795 4.71743 6.83409 4.33213C7.22022 3.94683 7.70101 3.75376 8.27646 3.75293H18.1392L23.7686 9.38233V13.6407C23.3533 13.6965 22.9655 13.8233 22.6052 14.021C22.2449 14.2186 21.9113 14.4705 21.6044 14.7766L14.0985 22.2511V26.2705H8.27646ZM17.0808 26.2705V23.5059L23.7523 16.8669C23.8757 16.7585 24.0038 16.6793 24.1364 16.6293C24.2698 16.5775 24.4032 16.5517 24.5367 16.5517C24.6743 16.5517 24.8136 16.5784 24.9545 16.6318C25.0963 16.686 25.2205 16.7669 25.3273 16.8744L26.4845 18.0554C26.587 18.1788 26.6654 18.3072 26.7196 18.4407C26.773 18.5733 26.7997 18.7063 26.7997 18.8397C26.7997 18.9732 26.7743 19.1074 26.7234 19.2425C26.6725 19.3776 26.5933 19.5069 26.4857 19.6303L19.8443 26.2705H17.0808ZM24.5367 20.0344L25.6938 18.8385L24.5367 17.6588L23.3483 18.8472L24.5367 20.0344ZM17.5137 10.0078H22.5176L17.5137 5.00391V10.0078Z" fill="currentColor"/></svg>
                  <span>Proyectos de beneficio social:</span>
                </p>
                <p class="text-gray-800 ml-5 whitespace-pre-line">{{ formCv.logros.proyectos }}</p>
              </div>
            </div>

            <!-- Botones de Acción (Lado Derecho) -->
            <div class="lg:col-span-4 space-y-4 pt-4">
              <!-- Editar -->
              <button
                @click="pasoActual = 2"
                class="w-full py-2.5 px-6 rounded-full border border-black bg-white hover:bg-gray-50 text-gray-900 text-sm font-medium transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                style="font-family: 'Lora', Georgia, serif;"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 27 27" fill="none" class="shrink-0">
                  <path d="M25.875 5.625V7.875H24.75V9H23.625V10.125H22.5V11.25H21.375V10.125H20.25V9H19.125V7.875H18V6.75H16.875V5.625H15.75V4.5H16.875V3.375H18V2.25H19.125V1.125H21.375V2.25H22.5V3.375H23.625V4.5H24.75V5.625H25.875ZM19.125 11.25V10.125H18V9H16.875V7.875H15.75V6.75H13.5V7.875H12.375V9H11.25V10.125H10.125V11.25H9V12.375H7.875V13.5H6.75V14.625H5.625V15.75H4.5V16.875H3.375V18H2.25V19.125H1.125V25.875H7.875V24.75H9V23.625H10.125V22.5H11.25V21.375H12.375V20.25H13.5V19.125H14.625V18H15.75V16.875H16.875V15.75H18V14.625H19.125V13.5H20.25V11.25H19.125ZM16.875 13.5V14.625H15.75V15.75H14.625V16.875H13.5V18H12.375V19.125H11.25V20.25H10.125V21.375H9V22.5H7.875V23.625H3.375V19.125H4.5V18H5.625V16.875H6.75V15.75H7.875V14.625H9V13.5H10.125V12.375H11.25V11.25H12.375V10.125H13.5V9H15.75V10.125H16.875V11.25H18V13.5H16.875Z" fill="currentColor"/>
                </svg>
                <span>Editar</span>
              </button>

              <!-- Imprimir cv -->
              <button
                @click="imprimirCvDirecto"
                class="w-full py-2.5 px-6 rounded-full border border-black bg-white hover:bg-gray-50 text-[#00589B] text-sm font-medium transition-all shadow-sm flex items-center justify-center cursor-pointer"
                style="font-family: 'Lora', Georgia, serif;"
              >
                Imprimir cv
              </button>

              <!-- Guardar en servidor -->
              <button
                @click="guardarCvFinal"
                :disabled="cargandoGuardar"
                class="w-full py-2.5 px-6 rounded-full bg-[#010C67] hover:bg-[#01094f] text-white text-sm font-medium transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                style="font-family: 'Lora', Georgia, serif;"
              >
                <span v-if="cargandoGuardar" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ cargandoGuardar ? 'Guardando...' : 'Guardar' }}</span>
              </button>

              <!-- Descargar CV -->
              <button
                @click="descargarPdfDirecto"
                class="w-full py-2.5 px-6 rounded-full border border-black bg-white hover:bg-gray-50 text-[#00589B] text-sm font-medium transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                style="font-family: 'Lora', Georgia, serif;"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 17 17" fill="none" class="shrink-0">
                  <path d="M7.5 8.5L6.75 7.75C6.59722 7.59722 6.40278 7.52083 6.16667 7.52083C5.93056 7.52083 5.73611 7.59722 5.58334 7.75C5.43056 7.90278 5.35417 8.09722 5.35417 8.33333C5.35417 8.56944 5.43056 8.76389 5.58334 8.91667L7.75 11.0833C7.91667 11.25 8.11111 11.3333 8.33334 11.3333C8.55556 11.3333 8.75 11.25 8.91667 11.0833L11.0833 8.91667C11.2361 8.76389 11.3125 8.56944 11.3125 8.33333C11.3125 8.09722 11.2361 7.90278 11.0833 7.75C10.9306 7.59722 10.7361 7.52083 10.5 7.52083C10.2639 7.52083 10.0694 7.59722 9.91667 7.75L9.16667 8.5V5.83333C9.16667 5.59722 9.08667 5.39944 8.92667 5.24C8.76667 5.08056 8.56889 5.00056 8.33334 5C8.09778 4.99944 7.9 5.07944 7.74 5.24C7.58 5.40056 7.5 5.59833 7.5 5.83333V8.5ZM8.33334 16.6667C7.18056 16.6667 6.09722 16.4478 5.08334 16.01C4.06945 15.5722 3.1875 14.9786 2.4375 14.2292C1.6875 13.4797 1.09389 12.5978 0.656668 11.5833C0.219446 10.5689 0.00055661 9.48556 1.05485e-06 8.33333C-0.000554501 7.18111 0.218334 6.09778 0.656668 5.08333C1.095 4.06889 1.68861 3.18694 2.4375 2.4375C3.18639 1.68806 4.06834 1.09444 5.08334 0.656667C6.09834 0.218889 7.18167 0 8.33334 0C9.485 0 10.5683 0.218889 11.5833 0.656667C12.5983 1.09444 13.4803 1.68806 14.2292 2.4375C14.9781 3.18694 15.5719 4.06889 16.0108 5.08333C16.4497 6.09778 16.6683 7.18111 16.6667 8.33333C16.665 9.48556 16.4461 10.5689 16.01 11.5833C15.5739 12.5978 14.9803 13.4797 14.2292 14.2292C13.4781 14.9786 12.5961 15.5725 11.5833 16.0108C10.5706 16.4492 9.48722 16.6678 8.33334 16.6667ZM8.33334 15C10.1944 15 11.7708 14.3542 13.0625 13.0625C14.3542 11.7708 15 10.1944 15 8.33333C15 6.47222 14.3542 4.89583 13.0625 3.60417C11.7708 2.3125 10.1944 1.66667 8.33334 1.66667C6.47222 1.66667 4.89583 2.3125 3.60417 3.60417C2.3125 4.89583 1.66667 6.47222 1.66667 8.33333C1.66667 10.1944 2.3125 11.7708 3.60417 13.0625C4.89583 14.3542 6.47222 15 8.33334 15Z" fill="currentColor"/>
                </svg>
                <span>Descargar CV</span>
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- ========================================================= -->
      <!-- PASOS 1 A 4: WIZARD MULTIPASO                             -->
      <!-- ========================================================= -->
      <div 
        v-else
        class="relative w-full max-w-[850px] bg-white rounded-[22px] shadow-2xl overflow-hidden my-auto flex flex-col max-h-[92vh]"
      >
        <!-- Modal Verde "Perfil completado" superpuesto si esta activo (Captura 1) -->
        <div 
          v-if="mostrandoModalExito"
          class="absolute inset-0 z-30 flex items-center justify-center p-6 bg-black/40 backdrop-blur-[1px]"
        >
          <div class="relative w-full max-w-[460px] bg-[#10A957] text-white rounded-[18px] p-8 text-center shadow-2xl border border-[#10A957]">
            <!-- Botón Cerrar ✕ -->
            <button 
              @click="mostrandoModalExito = false"
              class="absolute top-4 right-4 text-white hover:text-gray-100 p-1 cursor-pointer text-xl"
            >
              ✕
            </button>

            <!-- Título con icono check -->
            <div class="flex items-center justify-center gap-2 mb-3">
              <span class="text-xl font-bold">✓</span>
              <h3 class="text-xl font-bold font-serif" style="font-family: 'Lora', Georgia, serif;">
                Perfil completado
              </h3>
            </div>

            <!-- Subtítulo -->
            <p class="text-sm font-sans mb-6 leading-snug">
              Su cv ah sido cargado con éxito, verifique sus datos antes de finalizar.
            </p>

            <!-- Botón Ver cv -->
            <button
              @click="verCvVistaPrevia"
              class="px-8 py-2.5 rounded-full border border-white text-white font-medium text-sm hover:bg-white/20 transition-all cursor-pointer inline-flex items-center justify-center"
              style="font-family: 'Lora', Georgia, serif;"
            >
              Ver cv
            </button>
          </div>
        </div>

        <!-- Header Banner Dinámico por Paso -->
        <div class="bg-[#010C67] text-white px-6 py-4 text-center shrink-0">
          <h3 class="text-base sm:text-lg font-bold font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            <template v-if="pasoActual === 1">Paso 1: Configuración del diseño</template>
            <template v-else-if="pasoActual === 2">Paso 2: Información de Perfil</template>
            <template v-else-if="pasoActual === 3">Paso 3: Objetivos y valores</template>
            <template v-else-if="pasoActual === 4">Paso 4: Logros</template>
          </h3>
        </div>

        <!-- Indicador de Stepper 1 - 4 -->
        <div class="px-8 pt-6 pb-2 border-b border-gray-100 shrink-0">
          <div class="flex items-center justify-between max-w-md mx-auto relative">
            <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-[2px] bg-gray-300 z-0"></div>
            
            <div 
              v-for="num in 4" 
              :key="num"
              class="relative z-10 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all"
              :class="pasoActual >= num ? 'bg-[#010C67] text-white' : 'bg-[#d1d5db] text-white'"
              style="font-family: 'Lora', Georgia, serif;"
            >
              {{ num }}
            </div>
          </div>
        </div>

        <!-- Contenido principal del Paso -->
        <div class="p-6 sm:p-8 overflow-y-auto flex-1 text-left">

          <!-- PASO 1: DISEÑO -->
          <div v-if="pasoActual === 1" class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <div class="space-y-6">
              <div>
                <h4 class="text-base font-bold text-gray-900 font-serif mb-3" style="font-family: 'Lora', Georgia, serif;">
                  Color de la Plantilla
                </h4>
                <div class="flex items-center gap-4">
                  <button 
                    type="button"
                    @click="formCv.diseno.color = '#010C67'"
                    class="w-9 h-9 rounded-full bg-[#010C67] cursor-pointer transition-transform"
                    :class="formCv.diseno.color === '#010C67' ? 'ring-4 ring-[#00589B]/40 scale-110' : ''"
                  ></button>
                  <button 
                    type="button"
                    @click="formCv.diseno.color = '#67000F'"
                    class="w-9 h-9 rounded-full bg-[#67000F] cursor-pointer transition-transform"
                    :class="formCv.diseno.color === '#67000F' ? 'ring-4 ring-[#67000F]/40 scale-110' : ''"
                  ></button>
                  <button 
                    type="button"
                    @click="formCv.diseno.color = '#1F4E5B'"
                    class="w-9 h-9 rounded-full bg-[#1F4E5B] cursor-pointer transition-transform"
                    :class="formCv.diseno.color === '#1F4E5B' ? 'ring-4 ring-[#1F4E5B]/40 scale-110' : ''"
                  ></button>
                </div>
              </div>

              <div>
                <h4 class="text-base font-bold text-gray-900 font-serif mb-2" style="font-family: 'Lora', Georgia, serif;">
                  Tipo de letra
                </h4>
                <select 
                  v-model="formCv.diseno.fuente"
                  class="w-full px-4 py-3 rounded-xl bg-[#ebebeb] border border-black text-gray-900 text-sm focus:outline-none cursor-pointer"
                >
                  <option value="Montserrat">Montserrat</option>
                  <option value="Lora">Lora</option>
                  <option value="Roboto">Roboto</option>
                  <option value="Inter">Inter</option>
                </select>
              </div>
            </div>

            <div class="bg-gray-100 rounded-2xl p-4 border border-gray-200 flex flex-col items-center justify-center">
              <div class="w-full bg-white rounded-xl shadow-md overflow-hidden text-xs">
                <div class="p-4 text-white text-center transition-colors" :style="{ backgroundColor: formCv.diseno.color }">
                  <div class="w-12 h-12 rounded-full bg-white/30 mx-auto mb-2 flex items-center justify-center text-sm font-bold">
                    👤
                  </div>
                  <h5 class="font-bold text-sm" :style="{ fontFamily: formCv.diseno.fuente }">DALLANA LUCIA CAMPOZ GARCIA</h5>
                  <p v-if="formCv.perfil.educacion" class="text-[10px] opacity-80">{{ formCv.perfil.educacion }}</p>
                </div>
                <div class="p-4 space-y-2 text-gray-600">
                  <div class="h-2 bg-gray-200 rounded w-3/4"></div>
                  <div class="h-2 bg-gray-200 rounded w-1/2"></div>
                  <div class="h-2 bg-gray-200 rounded w-5/6"></div>
                </div>
              </div>
              <span class="text-[11px] text-gray-500 mt-2">Vista previa de la plantilla</span>
            </div>
          </div>

          <!-- PASO 2: INFORMACIÓN DE PERFIL -->
          <div v-else-if="pasoActual === 2" class="space-y-6 px-2">
            <!-- Subir Foto Avatar (Izquierda) -->
            <div class="flex flex-col items-start mb-2 ml-1">
              <label class="relative cursor-pointer group flex flex-col items-center gap-1">
                <input type="file" accept="image/*" class="hidden" @change="handleSubirFoto" />
                <div class="relative w-28 h-28 rounded-full bg-white border border-black flex items-center justify-center overflow-hidden shrink-0 shadow-xs">
                  <img v-if="formCv.perfil.fotoUrl && !imgErrorWizard" :src="formCv.perfil.fotoUrl" @error="imgErrorWizard = true" class="w-full h-full object-cover" />
                  <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 95 97" fill="none" class="w-16 h-16 text-black">
                    <path d="M47.2461 0C53.5114 0 59.52 2.35451 63.9502 6.54555C68.3804 10.7366 70.8692 16.4209 70.8692 22.3479C70.8692 28.275 68.3804 33.9592 63.9502 38.1503C59.52 42.3413 53.5114 44.6958 47.2461 44.6958C40.9809 44.6958 34.9723 42.3413 30.5421 38.1503C26.1119 33.9592 23.6231 28.275 23.6231 22.3479C23.6231 16.4209 26.1119 10.7366 30.5421 6.54555C34.9723 2.35451 40.9809 0 47.2461 0ZM47.2461 55.8698C73.3496 55.8698 94.4923 65.8705 94.4923 78.2177L83.4054 88.2347L65.6386 94.5426L44.4171 96.3449L20.2345 91.8392L0 82.8278V78.2177C0 65.8705 21.1426 55.8698 47.2461 55.8698Z" fill="currentColor"/>
                  </svg>
                  <div class="absolute bottom-1 right-1 w-7 h-7 bg-black text-white rounded-full flex items-center justify-center border border-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" fill="none" class="w-4 h-4 text-white shrink-0">
                      <path d="M40.7409 15.0099V10.7214H36.4524V6.43283H40.7409V2.14429H45.0294V6.43283H49.318V10.7214H45.0294V15.0099H40.7409ZM23.5867 37.5247C26.2671 37.5247 28.5457 36.587 30.4227 34.7115C32.2996 32.8359 33.2374 30.5573 33.236 27.8755C33.2345 25.1938 32.2968 22.9158 30.4227 21.0417C28.5486 19.1676 26.2699 18.2292 23.5867 18.2263C20.9035 18.2235 18.6256 19.1619 16.753 21.0417C14.8803 22.9216 13.9418 25.1995 13.9375 27.8755C13.9332 30.5516 14.8717 32.8302 16.753 34.7115C18.6342 36.5927 20.9121 37.5305 23.5867 37.5247ZM23.5867 33.2362C22.0858 33.2362 20.8171 32.718 19.7807 31.6816C18.7443 30.6452 18.2261 29.3765 18.2261 27.8755C18.2261 26.3745 18.7443 25.1058 19.7807 24.0695C20.8171 23.0331 22.0858 22.5149 23.5867 22.5149C25.0877 22.5149 26.3564 23.0331 27.3928 24.0695C28.4292 25.1058 28.9474 26.3745 28.9474 27.8755C28.9474 29.3765 28.4292 30.6452 27.3928 31.6816C26.3564 32.718 25.0877 33.2362 23.5867 33.2362ZM6.43258 45.0297C5.25324 45.0297 4.244 44.6101 3.40487 43.771C2.56575 42.9319 2.14547 41.9219 2.14404 40.7411V15.0099C2.14404 13.8306 2.56432 12.8213 3.40487 11.9822C4.24543 11.1431 5.25466 10.7228 6.43258 10.7214H13.187L17.1539 6.43283H32.1638V15.0099H36.4524V19.2984H45.0294V40.7411C45.0294 41.9205 44.6099 42.9305 43.7708 43.771C42.9316 44.6116 41.9217 45.0311 40.7409 45.0297H6.43258Z" fill="currentColor"/>
                    </svg>
                  </div>
                </div>
                <span class="text-base font-serif text-black group-hover:underline mt-1" style="font-family: 'Lora', Georgia, serif;">Subir foto</span>
              </label>
            </div>

            <!-- Formulario en 2 Columnas Calcado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
              <!-- Columna Izquierda: Contacto -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 41" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M17 0C19.2543 0 21.4163 1.00197 23.0104 2.78549C24.6045 4.56901 25.5 6.98798 25.5 9.51026C25.5 12.0325 24.6045 14.4515 23.0104 16.235C21.4163 18.0185 19.2543 19.0205 17 19.0205C14.7457 19.0205 12.5837 18.0185 10.9896 16.235C9.39553 14.4515 8.5 12.0325 8.5 9.51026C8.5 6.98798 9.39553 4.56901 10.9896 2.78549C12.5837 1.00197 14.7457 0 17 0ZM17 23.7756C26.3925 23.7756 34 28.0315 34 33.2859L30.0108 37.5486L23.6179 40.233L15.9821 41L7.28072 39.0826L0 35.2477V33.2859C0 28.0315 7.6075 23.7756 17 23.7756Z" fill="currentColor"/></svg>
                    <span>Nombre completo:</span>
                  </label>
                  <input 
                    v-model="formCv.perfil.nombre" 
                    type="text" 
                    placeholder="Dallana Lucia Campoz Garcia" 
                    class="w-full px-4 py-2.5 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none shadow-xs" 
                    style="font-family: 'Lora', Georgia, serif;"
                  />
                </div>

                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41 41" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M15.5003 19.6459C14.3676 19.6459 13.2813 19.196 12.4804 18.395C11.6795 17.5941 11.2295 16.5078 11.2295 15.3751C11.2295 14.2424 11.6795 13.1561 12.4804 12.3551C13.2813 11.5542 14.3676 11.1042 15.5003 11.1042C16.633 11.1042 17.7193 11.5542 18.5203 12.3551C19.3212 13.1561 19.7712 14.2424 19.7712 15.3751C19.7712 15.9359 19.6607 16.4913 19.4461 17.0095C19.2314 17.5276 18.9168 17.9984 18.5203 18.395C18.1237 18.7916 17.6529 19.1062 17.1347 19.3208C16.6165 19.5354 16.0612 19.6459 15.5003 19.6459ZM15.5003 3.41675C12.3288 3.41675 9.28713 4.67664 7.04451 6.91926C4.80188 9.16189 3.54199 12.2035 3.54199 15.3751C3.54199 24.3438 15.5003 37.5834 15.5003 37.5834C15.5003 37.5834 27.4587 24.3438 27.4587 15.3751C27.4587 12.2035 26.1988 9.16189 23.9561 6.91926C21.7135 4.67664 18.6719 3.41675 15.5003 3.41675Z" fill="currentColor"/></svg>
                    <span>Dirección:</span>
                  </label>
                  <input 
                    v-model="formCv.perfil.direccion" 
                    type="text" 
                    placeholder="Usulután" 
                    class="w-full px-4 py-2.5 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none shadow-xs" 
                    style="font-family: 'Lora', Georgia, serif;"
                  />
                </div>

                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 39" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M32.5 29.25H29.25V15.0312L19.5 21.125L9.75 15.0312V29.25H6.5V9.75H8.45L19.5 16.6562L30.55 9.75H32.5M32.5 6.5H6.5C4.69625 6.5 3.25 7.94625 3.25 9.75V29.25C3.25 30.112 3.59241 30.9386 4.2019 31.5481C4.8114 32.1576 5.63805 32.5 6.5 32.5H32.5C33.362 32.5 34.1886 32.1576 34.7981 31.5481C35.4076 30.9386 35.75 30.112 35.75 29.25V9.75C35.75 8.88805 35.4076 8.0614 34.7981 7.4519C34.1886 6.84241 33.362 6.5 32.5 6.5Z" fill="currentColor"/></svg>
                    <span>Email</span>
                  </label>
                  <input 
                    v-model="formCv.perfil.email" 
                    type="email" 
                    placeholder="usss002419@ugb.edu.sv" 
                    class="w-full px-4 py-2.5 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none shadow-xs" 
                    style="font-family: 'Lora', Georgia, serif;"
                  />
                </div>

                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 34" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M26.0809 31.1668H26.6051C27.0442 31.1526 27.4551 30.926 27.7101 30.5576L30.9259 25.9251C31.1384 25.6135 31.2234 25.231 31.1526 24.8485C31.1178 24.662 31.046 24.4844 30.9414 24.3263C30.8367 24.1681 30.7014 24.0325 30.5434 23.9276L23.5876 19.2951C23.0067 18.9126 22.2276 18.9976 21.7601 19.5076L19.0967 22.3835C18.0201 21.746 16.2209 20.5985 14.8042 19.1818C13.3876 17.7651 12.2401 15.966 11.6026 14.9035L14.4784 12.2401C14.9884 11.7726 15.0876 10.9935 14.6909 10.4126L10.0584 3.4568C9.8459 3.14513 9.52007 2.91847 9.15173 2.84763C8.76923 2.7768 8.38673 2.84763 8.07507 3.0743L3.44257 6.27597C3.07423 6.53097 2.84757 6.9418 2.8334 7.38097C2.7909 8.3868 2.60673 17.3543 9.61923 24.3526C15.9376 30.671 23.8426 31.1526 26.0809 31.1526V31.1668Z" fill="currentColor"/></svg>
                    <span>Teléfono:</span>
                  </label>
                  <input 
                    v-model="formCv.perfil.telefono" 
                    type="text" 
                    placeholder="7200 - 6452" 
                    class="w-full px-4 py-2.5 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none shadow-xs" 
                    style="font-family: 'Lora', Georgia, serif;"
                  />
                </div>
              </div>

              <!-- Columna Derecha: Sobre mí y Educación -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 35 35" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M29.5312 0H3.28125C1.47656 0 0 1.47656 0 3.28125V31.7188C0 33.5234 1.47656 35 3.28125 35H29.5312C31.3359 35 32.8125 33.5234 32.8125 31.7188V3.28125C32.8125 1.47656 31.3359 0 29.5312 0ZM28.4375 30.625H4.375V4.375H28.4375V30.625ZM8.75 19.6875H24.0625V21.875H8.75V19.6875ZM8.75 24.0625H24.0625V26.25H8.75V24.0625ZM10.9375 9.84375C10.9376 9.41271 11.0227 8.98591 11.1878 8.58773C11.3529 8.18956 11.5948 7.8278 11.8996 7.5231C12.2045 7.21841 12.5665 6.97676 12.9648 6.81193C13.363 6.64711 13.7899 6.56236 14.2209 6.5625C14.652 6.56264 15.0788 6.64769 15.477 6.81277C15.8751 6.97786 16.2369 7.21975 16.5416 7.52465C16.8463 7.82955 17.0879 8.19147 17.2528 8.58976C17.4176 8.98804 17.5023 9.41489 17.5022 9.84594C17.5019 10.7165 17.1558 11.5512 16.54 12.1666C15.9243 12.7819 15.0893 13.1275 14.2188 13.1272C13.3482 13.1269 12.5135 12.7808 11.8981 12.165C11.2827 11.5493 10.9372 10.7143 10.9375 9.84375ZM16.4062 13.125H12.0312C10.2266 13.125 8.75 14.1094 8.75 15.3125V17.5H19.6875V15.3125C19.6875 14.1094 18.2109 13.125 16.4062 13.125Z" fill="currentColor"/></svg>
                    <span>Sobre mi:</span>
                  </label>
                  <textarea 
                    v-model="formCv.perfil.sobreMi" 
                    rows="5" 
                    placeholder="ej.Soy una persona apasionada por la tecnología, con un profundo interés en el campo de la ciberseguridad." 
                    class="w-full px-4 py-3 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none resize-none shadow-xs"
                    style="font-family: 'Lora', Georgia, serif;"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-bold text-black mb-1.5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 43 43" fill="none" class="w-4 h-4 text-black shrink-0"><g clip-path="url(#clip0_2001_411_c)"><path d="M21.5 3.5835L0 16.1252L21.5 28.6668L39.4167 18.216V31.3543H43V16.1252L21.5 3.5835ZM7.16488 24.1697V32.2502C8.83279 34.4764 10.9966 36.2832 13.4847 37.5272C15.9727 38.7712 18.7165 39.4182 21.4982 39.4168C24.2802 39.4185 27.0243 38.7716 29.5127 37.5276C32.0011 36.2836 34.1652 34.4766 35.8333 32.2502V24.1715L21.5 32.5332L7.16488 24.1697Z" fill="currentColor"/></g><defs><clipPath id="clip0_2001_411_c"><rect width="43" height="43" fill="white"/></clipPath></defs></svg>
                    <span>Educación:</span>
                  </label>
                  <textarea 
                    v-model="formCv.perfil.educacion" 
                    rows="3" 
                    placeholder="Egresada de Ingeniería en sistemas y redes informáticas" 
                    class="w-full px-4 py-3 rounded-[14px] bg-white border border-black text-sm text-gray-800 focus:outline-none resize-none shadow-xs"
                    style="font-family: 'Lora', Georgia, serif;"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- PASO 3: OBJETIVOS Y VALORES -->
          <div v-else-if="pasoActual === 3" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 5" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M1 2.30965C1 2.657 1.13798 2.99011 1.38359 3.23572C1.6292 3.48133 1.96231 3.61931 2.30965 3.61931C2.657 3.61931 2.99011 3.48133 3.23572 3.23572C3.48133 2.99011 3.61931 2.657 3.61931 2.30965C3.61931 1.96231 3.48133 1.6292 3.23572 1.38359C2.99011 1.13798 2.657 1 2.30965 1C1.96231 1 1.6292 1.13798 1.38359 1.38359C1.13798 1.6292 1 1.96231 1 2.30965Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Mi objetivo:</span>
              </label>
              <textarea v-model="formCv.objetivos.objetivo" rows="4" placeholder="Ej. Incorporarme y consolidarme en un equipo de trabajo donde pueda aplicar los conocimientos y habilidades adquiridos..." class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 46 46" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M23.9322 25.0333L30.3294 31.5133C31.4032 32.6061 32.0048 34.0769 32.0048 35.6089C32.0048 37.141 31.4032 38.6118 30.3294 39.7046C29.3458 40.7067 28.0281 41.3122 26.627 41.4059C25.226 41.4996 23.8394 41.075 22.7311 40.2129L22.5899 40.0962L22.4525 40.2129C21.5508 40.9145 20.4597 41.3302 19.3198 41.4065L18.9376 41.4197C18.177 41.4199 17.4239 41.2685 16.7225 40.9744C16.021 40.6802 15.3852 40.2492 14.8523 39.7065C13.7773 38.6129 13.1752 37.1407 13.1759 35.6072C13.1766 34.0738 13.7801 32.6021 14.856 31.5095L21.2513 25.0333C21.4265 24.8557 21.6352 24.7147 21.8653 24.6184C22.0954 24.5222 22.3423 24.4726 22.5918 24.4726C22.8412 24.4726 23.0881 24.5222 23.3183 24.6184C23.5484 24.7147 23.7571 24.8557 23.9322 25.0333ZM39.7049 14.8539C40.7075 15.8376 41.3133 17.1557 41.407 18.5571C41.5007 19.9586 41.0758 21.3456 40.2132 22.4541L40.0965 22.5915L40.2132 22.7327C40.9148 23.6344 41.3305 24.7254 41.4068 25.8654L41.4199 26.2476C41.4202 27.0082 41.2688 27.7613 40.9746 28.4627C40.6805 29.1642 40.2495 29.7999 39.7067 30.3329C38.6132 31.4079 37.1409 32.0099 35.6075 32.0092C34.0741 32.0085 32.6023 31.4051 31.5098 30.3291L25.0335 23.9338C24.8559 23.7587 24.7149 23.55 24.6187 23.3199C24.5224 23.0898 24.4729 22.8428 24.4729 22.5934C24.4729 22.344 24.5224 22.097 24.6187 21.8669C24.7149 21.6368 24.8559 21.4281 25.0335 21.253L31.5154 14.8539C32.6082 13.7801 34.079 13.1785 35.6111 13.1785C37.1432 13.1785 38.6121 13.7801 39.7049 14.8539ZM13.6756 14.8577L20.1519 21.253C20.3295 21.4281 20.4705 21.6368 20.5667 21.8669C20.663 22.097 20.7126 22.344 20.7126 22.5934C20.7126 22.8428 20.663 23.0898 20.5667 23.3199C20.4705 23.55 20.3295 23.7587 20.1519 23.9338L13.6681 30.3291C12.5753 31.4029 11.1045 32.0046 9.57245 32.0046C8.04039 32.0046 6.5696 31.4029 5.47679 30.3291C4.47472 29.3455 3.86921 28.0278 3.77548 26.6268C3.68176 25.2258 4.10635 23.8392 4.96848 22.7308L5.08521 22.5896L4.96848 22.4522C4.26685 21.5505 3.85115 20.4595 3.7749 19.3195L3.76172 18.9373C3.76172 17.4011 4.37922 15.9289 5.47491 14.852C6.56846 13.777 8.04073 13.175 9.57416 13.1757C11.1076 13.1764 12.5793 13.7798 13.6719 14.8558M26.2441 3.76522C27.0047 3.76498 27.7578 3.91636 28.4592 4.21052C29.1607 4.50468 29.7964 4.93571 30.3294 5.47842C31.4044 6.57196 32.0064 8.04423 32.0057 9.57767C32.005 11.1111 31.4016 12.5828 30.3256 13.6754L23.9303 20.1516C23.7552 20.3292 23.5465 20.4702 23.3164 20.5665C23.0863 20.6627 23.8393 20.7123 22.5899 20.7123C22.3405 20.7123 22.0935 20.6627 21.8634 20.5665C21.6333 20.4702 21.4246 20.3292 21.2495 20.1516L14.8542 13.6679C13.7804 12.575 13.1787 11.1043 13.1787 9.57219C13.1787 8.04013 13.7804 6.56934 14.8542 5.47653C15.8378 4.47446 17.1555 3.86895 18.5565 3.77522C19.9575 3.6815 21.3441 4.10609 22.4525 4.96822L22.5899 5.08495L22.7311 4.96822C23.6328 4.26659 24.7238 3.85089 25.8638 3.77464L26.2441 3.76522Z" fill="currentColor"/></svg>
                <span>Valores profesionales:</span>
              </label>
              <textarea v-model="formCv.objetivos.valores" rows="4" placeholder="Ej. - Liderazgo&#10;- Compromiso&#10;- Trabajo en equipo&#10;- Responsabilidad" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 39" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M35.6383 21.8333V29.1586C35.6383 30.4474 35.1263 31.6835 34.2149 32.5949C33.3035 33.5063 32.0674 34.0183 30.7785 34.0183H8.09952C6.81063 34.0183 5.57453 33.5063 4.66314 32.5949C3.75176 31.6835 3.23975 30.4474 3.23975 29.1586V21.8333L4.13556 22.282C8.88345 24.6733 14.1257 25.9187 19.4418 25.9181C24.7579 25.9176 29.9999 24.6711 34.7473 22.2787L35.6383 21.8333ZM22.6789 3.23975C23.9677 3.23975 25.2039 3.75176 26.1152 4.66314C27.0266 5.57453 27.5386 6.81063 27.5386 8.09952V9.71945H30.7785C32.0674 9.71945 33.3035 10.2315 34.2149 11.1428C35.1263 12.0542 35.6383 13.2903 35.6383 14.5792V18.2111L33.2942 19.3839C29.1003 21.4981 24.4781 22.6244 19.7817 22.6767C15.0853 22.7289 10.4393 21.7056 6.19935 19.6852L5.15126 19.1669L3.23975 18.2111V14.5792C3.23975 13.2903 3.75176 12.0542 6.81063 9.71945 8.09952 9.71945H11.3394V8.09952C11.3394 6.81063 11.8514 5.57453 12.7628 4.66314C13.6742 3.75176 14.9103 3.23975 16.1992 3.23975H22.6789ZM19.439 16.1992C19.0094 16.1992 18.5973 16.3698 18.2935 16.6736C17.9897 16.9774 17.8191 17.3894 17.8191 17.8191C17.818 18.0318 17.8589 18.2427 17.9393 18.4396C18.0197 18.6366 18.1381 18.8157 18.2878 18.9669C18.4375 19.1181 18.6155 19.2383 18.8116 19.3207C19.0077 19.4031 19.2182 19.446 19.4309 19.4471C19.6436 19.4482 19.8545 19.4073 20.0514 19.3269C20.2484 19.2465 20.4276 19.128 20.5787 18.9784C20.7299 18.8287 20.8501 18.6507 20.9325 18.4546C21.0149 18.2584 21.0579 18.048 21.0589 17.8353C21.0589 16.9249 20.3332 16.1992 19.439 16.1992ZM22.6789 6.4796H16.1992C15.7695 6.4796 15.3575 6.65027 15.0537 6.95406C14.7499 7.25786 14.5792 7.66989 14.5792 8.09952V9.71945H24.2988V8.09952C24.2988 7.66989 24.1281 7.25786 23.8243 6.95406C23.5205 6.65027 23.1085 6.4796 22.6789 6.4796Z" fill="currentColor"/></svg>
                <span>Conocimientos:</span>
              </label>
              <textarea v-model="formCv.objetivos.conocimientos" rows="4" placeholder="Ej. - Conocimientos en redes (Protocolos, enrutamientos, IPv4 y IPv6)&#10;- Análisis de seguridad" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 34 34" fill="none" class="w-4 h-4 text-black shrink-0"><g clip-path="url(#clip0_2016_494_c)"><path d="M30.5226 0.175559C30.6635 0.269819 30.7791 0.397321 30.859 0.546808C30.939 0.696295 30.9809 0.863171 30.9811 1.0327V16.5232C30.9811 16.7295 30.9193 16.931 30.8037 17.1018C30.6881 17.2726 30.524 17.4049 30.3326 17.4816L30.3264 17.4837L30.314 17.4899L30.2665 17.5084C29.995 17.6165 29.7217 17.7198 29.4465 17.8183C28.9013 18.0145 28.1433 18.2788 27.2841 18.5411C25.5987 19.0616 23.4032 19.6213 21.6868 19.6213C19.9374 19.6213 18.4896 19.043 17.2297 18.537L17.1718 18.5164C15.8624 17.9897 14.747 17.5559 13.4252 17.5559C11.9794 17.5559 10.042 18.031 8.39179 18.5411C7.65303 18.7718 6.9209 19.0232 6.19626 19.295V32.0138C6.19626 32.2877 6.08746 32.5503 5.89379 32.744C5.70012 32.9377 5.43745 33.0465 5.16356 33.0465C4.88967 33.0465 4.627 32.9377 4.43333 32.744C4.23966 32.5503 4.13086 32.2877 4.13086 32.0138V1.0327C4.13086 0.758813 4.23966 0.496141 4.43333 0.302472C4.627 0.108802 4.88967 0 5.16356 0C5.43745 0 5.70012 0.108802 5.89379 0.302472C6.08746 0.496141 6.19626 0.758813 6.19626 1.0327V1.61515C6.66305 1.45198 7.22071 1.26403 7.82793 1.07814C9.51331 0.56179 11.7109 0 13.4252 0C15.1601 0 16.5729 0.572117 17.8059 1.07195L17.8947 1.10912C19.1794 1.62754 20.2989 2.06541 21.6868 2.06541C23.1326 2.06541 25.0699 1.59036 26.7202 1.08021C27.6604 0.785826 28.5897 0.457905 29.5064 0.097074L29.5457 0.0826162L29.5539 0.0784854H29.556" fill="currentColor"/></g><defs><clipPath id="clip0_2016_494_c"><rect width="33.0465" height="33.0465" fill="white"/></clipPath></defs></svg>
                <span>Idiomas:</span>
              </label>
              <textarea v-model="formCv.objetivos.idiomas" rows="4" placeholder="Ej. - Español nativo&#10;- Inglés básico" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>
          </div>

          <!-- PASO 4: LOGROS -->
          <div v-else-if="pasoActual === 4" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 28" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M29.2208 16.7698V23.3766C29.2208 24.5391 28.759 25.6539 27.937 26.4759C27.115 27.7597 26.0001 27.7597 24.8376 27.7597H4.38311C3.22064 27.7597 2.10578 27.2979 1.28378 26.4759C0.461791 25.6539 0 24.5391 0 23.3766V16.7698L0.807954 17.1745C5.09015 19.3313 9.81823 20.4545 14.6129 20.454C19.4076 20.4535 24.1355 19.3293 28.4172 17.1716L29.2208 16.7698ZM17.5325 0C18.6949 0 19.8098 0.461791 20.6318 1.28378C21.4538 2.10578 21.9156 3.22064 21.9156 4.38311V5.84415H24.8376C26.0001 5.84415 27.115 6.30594 27.937 7.12794C28.759 7.94993 29.2208 9.06479 29.2208 10.2273V13.5029L27.1066 14.5607C23.3241 16.4675 19.1553 17.4834 14.9195 17.5305C10.6837 17.5776 6.49337 16.6547 2.66932 14.8325L1.72403 14.3649L0 13.5029V10.2273C0 9.06479 0.461791 7.94993 1.28378 7.12794C2.10578 6.30594 3.22064 5.84415 4.38311 5.84415H7.30519V4.38311C7.30519 3.22064 7.76698 2.10578 8.58897 1.28378C9.41097 0.461791 10.5258 0 11.6883 0H17.5325ZM14.6104 11.6883C14.2229 11.6883 13.8513 11.8422 13.5773 12.1162C13.3033 12.3902 13.1493 12.7619 13.1493 13.1493C13.1484 13.3412 13.1852 13.5314 13.2578 13.709C13.3303 13.8866 13.4371 14.0482 13.5721 14.1846C13.7071 14.3209 13.8676 14.4294 14.0445 14.5037C14.2214 14.578 14.4112 14.6167 14.6031 14.6177C14.7949 14.6186 14.9851 14.5818 15.1627 14.5093C15.3404 14.4367 15.502 14.3299 15.6383 14.1949C15.7747 14.0599 15.8831 13.8994 15.9574 13.7225C16.0317 13.5456 16.0705 13.3558 16.0714 13.164C16.0714 12.3428 15.4169 11.6883 14.6104 11.6883ZM17.5325 2.92208H11.6883C11.3008 2.92208 10.9292 3.07601 10.6552 3.35C10.3812 3.624 10.2273 3.99562 10.2273 4.38311V5.84415H18.9935V4.38311C18.9935 3.99562 18.8396 3.624 18.5656 3.35C18.2916 3.07601 17.9199 2.92208 17.5325 2.92208Z" fill="currentColor"/></svg>
                <span>Certificados, talleres y cursos:</span>
              </label>
              <textarea v-model="formCv.logros.certificados" rows="4" placeholder="Ej. - CCNAv7: Introduction to Networks&#10;- Ciberseguridad empresarial" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 41 41" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M21.359 22.3415L27.0683 28.1248C28.0266 29.1001 28.5636 30.4127 28.5636 31.78C28.5636 33.1473 28.0266 34.46 27.0683 35.4353C26.1905 36.3296 25.0145 36.87 23.7641 36.9537C22.5137 37.0373 21.2762 36.6584 20.2871 35.8889L20.161 35.7848L20.0384 35.8889C19.2337 36.5151 18.2599 36.8861 17.2425 36.9542L16.9015 36.9659C16.2226 36.9662 15.5505 36.831 14.9245 36.5685C14.2985 36.306 13.7311 35.9213 13.2554 35.437C12.2961 34.461 11.7587 33.147 11.7594 31.7785C11.76 30.4099 12.2985 29.0965 13.2588 28.1214L18.9664 22.3415C19.1227 22.183 19.309 22.0572 19.5144 21.9713C19.7197 21.8854 19.9401 21.8411 20.1627 21.8411C20.3853 21.8411 20.6057 21.8854 20.8111 21.9713C21.0165 22.0572 21.2027 22.183 21.359 22.3415ZM35.4357 13.2567C36.3305 14.1347 36.8712 15.311 36.9548 16.5618C37.0385 17.8125 36.6592 19.0504 35.8893 20.0397L35.7852 20.1623L35.8893 20.2883C36.5155 21.0931 36.8865 22.0668 36.9546 23.0842L36.9663 23.4253C36.9666 24.1041 36.8315 24.7762 36.5689 25.4022C36.3064 26.0282 35.9217 26.5956 35.4374 27.0713C34.4614 28.0307 33.1474 28.568 31.7789 28.5674C30.4103 28.5667 29.0969 28.0282 28.1218 27.0679L22.3419 21.3603C22.1834 21.204 22.0576 21.0177 21.9717 20.8124C21.8858 20.607 21.8415 20.3866 21.8415 20.164C21.8415 19.9414 21.8858 19.721 21.9717 19.5156C22.0576 19.3103 22.1834 19.124 22.3419 18.9677L28.1268 13.2567C29.1021 12.2984 30.4148 11.7614 31.7821 11.7614C33.1494 11.7614 34.4604 12.2984 35.4357 13.2567ZM12.2053 13.2601L17.9852 18.9677C18.1437 19.124 18.2695 19.3103 18.3554 19.5156C18.4414 19.721 18.4856 19.9414 18.4856 20.164C18.4856 20.3866 18.4414 20.607 18.3554 20.8124C18.2695 21.0177 18.1437 21.204 17.9852 21.3603L12.1986 27.0679C11.2233 28.0262 9.91067 28.5632 8.54334 28.5632C7.17601 28.5632 5.86338 28.0262 4.88808 27.0679C3.99375 26.1901 3.45335 25.0141 3.36971 23.7637C3.28606 22.5133 3.665 21.2758 4.43443 20.2867L4.5386 20.1606L4.43443 20.038C3.80824 19.2333 3.43724 18.2595 3.36918 17.2421L3.35742 16.9011C3.35742 15.53 3.90853 14.2161 4.8864 13.255C5.86236 12.2957 7.17632 11.7583 8.54487 11.759C9.91342 11.7596 12.2269 12.2981 12.202 13.2584M23.4223 3.36039C24.1011 3.36017 24.7732 3.49527 25.3992 3.7578C26.0253 4.02033 26.5927 4.40501 27.0683 4.88936C28.0277 5.86532 28.565 7.17928 28.5644 8.54783C28.5638 9.91638 28.0252 11.2298 27.065 12.2049L21.3573 17.9848C21.201 18.1433 21.0148 18.2691 20.8094 18.355C20.604 18.441 20.3837 18.4852 20.161 18.4852C19.9384 18.4852 19.718 18.441 19.5127 18.355C19.3073 18.2691 19.1211 18.1433 18.9647 17.9848L13.2571 12.1982C12.2988 11.2229 11.7618 9.91027 11.7618 8.54294C11.7618 7.17562 12.2988 5.86298 13.2571 4.88768C14.135 3.99335 15.311 3.45296 16.5614 3.36931C17.8117 3.28566 19.0492 3.6646 20.0384 4.43403L20.161 4.5382L20.2871 4.43403C21.0918 3.80784 22.0655 3.43684 23.0829 3.36879L23.4223 3.36039Z" fill="currentColor"/></svg>
                <span>Habilidades:</span>
              </label>
              <textarea v-model="formCv.logros.habilidades" rows="4" placeholder="Ej. - Facilidad para expresarme de forma verbal&#10;- Facilidad para trabajar en equipo" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M3.42188 13.6873C3.42187 15.0353 3.6874 16.3702 4.20328 17.6156C4.71916 18.8611 5.47531 19.9927 6.42854 20.946C7.38176 21.8992 8.51341 22.6553 9.75887 23.1712C11.0043 23.6871 12.3392 23.9526 13.6873 23.9526C15.0353 23.9526 16.3702 23.6871 17.6156 23.1712C18.8611 22.6553 19.9927 21.8992 20.946 20.946C21.8992 19.9927 22.6553 18.8611 23.1712 17.6156C23.6871 16.3702 23.9526 15.0353 23.9526 13.6873C23.9526 12.3392 23.6871 11.0043 23.1712 9.75887C22.6553 8.51341 21.8992 7.38176 20.946 6.42854C19.9927 5.47531 18.8611 4.71916 17.6156 4.20328C16.3702 3.6874 15.0353 3.42188 13.6873 3.42188C12.3392 3.42188 11.0043 3.6874 9.75887 4.20328C8.51341 4.71916 7.38176 5.47531 6.42854 6.42854C5.47531 7.38176 4.71916 8.51341 4.20328 9.75887C3.6874 11.0043 3.42187 12.3392 3.42188 13.6873Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.98438 13.6874C7.98438 15.1999 8.58522 16.6505 9.65474 17.72C10.7243 18.7895 12.1748 19.3904 13.6874 19.3904C15.1999 19.3904 16.6505 18.7895 17.72 17.72C18.7895 16.6505 19.3904 15.1999 19.3904 13.6874C19.3904 12.1748 18.7895 10.7243 17.72 9.65474C16.6505 8.58522 15.1999 7.98438 13.6874 7.98438C12.1748 7.98438 10.7243 8.58522 9.65474 9.65474C8.58522 10.7243 7.98438 12.1748 7.98438 13.6874Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.69523 22.812L13.6873 13.6873L6.27344 20.5308" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5469 13.6872C12.5469 13.9897 12.667 14.2798 12.8809 14.4938C13.0949 14.7077 13.385 14.8278 13.6875 14.8278C13.99 14.8278 14.2801 14.7077 14.494 14.4938C14.7079 14.2798 14.8281 13.9897 14.8281 13.6872C14.8281 13.3847 14.7079 13.0946 14.494 12.8807C14.2801 12.6668 13.99 12.5466 13.6875 12.5466C13.385 12.5466 13.0949 12.6668 12.8809 12.8807C12.667 13.0946 12.5469 13.3847 12.5469 13.6872Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Logros:</span>
              </label>
              <textarea v-model="formCv.logros.logros" rows="4" placeholder="Ej. Primer lugar a nivel de sede en innovación - Rally Latinoamericano 2023" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31 31" fill="none" class="w-4 h-4 text-black shrink-0"><path d="M8.27646 26.2705C7.70018 26.2705 7.21939 26.0779 6.83409 25.6926C6.44878 25.3073 6.25572 24.8265 6.25488 24.2502V5.77326C6.25488 5.19781 6.44795 4.71743 6.83409 4.33213C7.22022 3.94683 7.70101 3.75376 8.27646 3.75293H18.1392L23.7686 9.38233V13.6407C23.3533 13.6965 22.9655 13.8233 22.6052 14.021C22.2449 14.2186 21.9113 14.4705 21.6044 14.7766L14.0985 22.2511V26.2705H8.27646ZM17.0808 26.2705V23.5059L23.7523 16.8669C23.8757 16.7585 24.0038 16.6793 24.1364 16.6293C24.2698 16.5775 24.4032 16.5517 24.5367 16.5517C24.6743 16.5517 24.8136 16.5784 24.9545 16.6318C25.0963 16.686 25.2205 16.7669 25.3273 16.8744L26.4845 18.0554C26.587 18.1788 26.6654 18.3072 26.7196 18.4407C26.773 18.5733 26.7997 18.7063 26.7997 18.8397C26.7997 18.9732 26.7743 19.1074 26.7234 19.2425C26.6725 19.3776 26.5933 19.5069 26.4857 19.6303L19.8443 26.2705H17.0808ZM24.5367 20.0344L25.6938 18.8385L24.5367 17.6588L23.3483 18.8472L24.5367 20.0344ZM17.5137 10.0078H22.5176L17.5137 5.00391V10.0078Z" fill="currentColor"/></svg>
                <span>Proyectos:</span>
              </label>
              <textarea v-model="formCv.logros.proyectos" rows="4" placeholder="Ej. Asociación Juvenil del Bajo Lempa (AJUBAL) - Miembro activo desde 2021" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>
          </div>

        </div>

        <!-- Botones de Navegación del Wizard -->
        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between shrink-0">
          <button 
            type="button" 
            @click="anteriorPaso"
            class="w-[130px] h-[45px] rounded-[18px] bg-[#67000F] hover:bg-[#52000c] text-white text-sm font-medium transition-all cursor-pointer shadow-md"
            style="font-family: 'Lora', Georgia, serif;"
          >
            Atrás
          </button>

          <button 
            type="button" 
            @click="siguientePaso"
            class="w-[140px] h-[45px] rounded-[18px] bg-[#010C67] hover:bg-[#01094f] text-white text-sm font-medium transition-all cursor-pointer shadow-md flex items-center justify-center gap-2"
            style="font-family: 'Lora', Georgia, serif;"
          >
            <span>Siguiente</span>
          </button>
        </div>
      </div>
    </div>
    </Teleport>

  </div>
</template>
