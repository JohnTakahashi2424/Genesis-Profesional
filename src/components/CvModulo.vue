<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import jsPDF from 'jspdf'

const emit = defineEmits(['volver'])

// Obtener datos del usuario en sesión
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{"id":1,"nombres":"Dallana Lucia","apellidos":"Campoz Garcia","correo":"usss002419@ugb.edu.sv"}'))

// Estado del módulo
const cvs = ref([])
const cargando = ref(false)
const mostrandoWizard = ref(false)
const pasoActual = ref(0) // 0: Nombre, 1: Diseño, 2: Perfil, 3: Objetivos, 4: Logros, 5: Modal Exito Green, 6: Vista previa
const mostrandoModalExito = ref(false)
const cargandoGuardar = ref(false)
const mensajeExito = ref('')
const mensajeError = ref('')

// Formulario del CV
const formCv = ref({
  id: null,
  tituloCv: '',
  diseno: {
    color: '#010C67',
    fuente: 'Montserrat'
  },
  perfil: {
    fotoUrl: '',
    nombre: '',
    profesion: 'Egresada de Ingeniería en sistemas y redes informáticas',
    direccion: 'Usulután',
    email: 'usss002419@ugb.edu.sv',
    telefono: '7200 - 6452',
    sobreMi: 'Soy una persona apasionada por la tecnología, con un profundo interés en el campo de la ciberseguridad.',
    educacion: 'Universidad Gerardo Barrios (egresado)'
  },
  objetivos: {
    objetivo: 'Incorporarme y consolidarme en un equipo de trabajo donde pueda aplicar los conocimientos y habilidades adquiridos a lo largo de mi formación académica y profesional',
    valores: '- Liderazgo.\n- Compromiso.\n- Trabajo en equipo.\n- Responsabilidad.\n- Sinceridad.',
    conocimientos: '-Conocimientos en redes Protocolos, enrutamientos, IPv4 y IPv6, NAT, túneles, ACL estándar, nombrada y extendida, direccionamiento estático y dinámico. -Conocimientos en seguridad Análisis de casos de estudio, Phishing Emails',
    idiomas: '- Español nativo\n- Inglés básico'
  },
  logros: {
    certificados: '- CCNAv7: Introduction to Networks.\n- CCNAv7: Enterprise Networking, Security, and Automation.\n- Ciberseguridad y privacidad empresarial.',
    habilidades: '- Facilidad para expresarme de forma verbal, y que esta a su vez sea fluida, entendible y coherente frente a las personas.\n- Facilidad para trabajar en equipo.',
    logros: 'Primer lugar a nivel de sede en la categoría innovación - Rally Latinoamericano de innovación 2023. - Tercer lugar en investigación de cátedra - Universidad Gerardo Barrios Usulután 2023.',
    proyectos: 'Asociación Juvenil del Bajo Lempa (AJUBAL).\n• Miembro activo desde el año 2021 hasta la actualidad.'
  }
})

// Cargar CVs del usuario desde la API
const cargarCvs = async () => {
  cargando.value = true
  try {
    const res = await axios.get(`/api/cv/obtener/${usuario.value.id || 1}`)
    if (res.data && res.data.cvs) {
      cvs.value = res.data.cvs
    }
  } catch (err) {
    console.warn('Error al cargar CVs:', err)
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  cargarCvs()
})

// Inicializar un nuevo CV
const abrirCrearCv = () => {
  formCv.value = {
    id: null,
    tituloCv: '',
    diseno: {
      color: '#010C67',
      fuente: 'Montserrat'
    },
    perfil: {
      fotoUrl: '',
      nombre: (usuario.value.nombres || 'Dallana Lucia') + ' ' + (usuario.value.apellidos || 'Campoz Garcia'),
      profesion: 'Egresada de Ingeniería en sistemas y redes informáticas',
      direccion: 'Usulután',
      email: usuario.value.correo || usuario.value.email || 'usss002419@ugb.edu.sv',
      telefono: '7200 - 6452',
      sobreMi: 'Soy una persona apasionada por la tecnología, con un profundo interés en el campo de la ciberseguridad.',
      educacion: 'Universidad Gerardo Barrios (egresado)'
    },
    objetivos: {
      objetivo: 'Incorporarme y consolidarme en un equipo de trabajo donde pueda aplicar los conocimientos y habilidades adquiridos a lo largo de mi formación académica y profesional',
      valores: '- Liderazgo.\n- Compromiso.\n- Trabajo en equipo.\n- Responsabilidad.\n- Sinceridad.',
      conocimientos: '-Conocimientos en redes Protocolos, enrutamientos, IPv4 y IPv6, NAT, túneles, ACL estándar, nombrada y extendida, direccionamiento estático y dinámico. -Conocimientos en seguridad Análisis de casos de estudio, Phishing Emails',
      idiomas: '- Español nativo\n- Inglés básico'
    },
    logros: {
      certificados: '- CCNAv7: Introduction to Networks.\n- CCNAv7: Enterprise Networking, Security, and Automation.\n- Ciberseguridad y privacidad empresarial.',
      habilidades: '- Facilidad para expresarme de forma verbal, y que esta a su vez sea fluida, entendible y coherente frente a las personas.\n- Facilidad para trabajar en equipo.',
      logros: 'Primer lugar a nivel de sede en la categoría innovación - Rally Latinoamericano de innovación 2023. - Tercer lugar en investigación de cátedra - Universidad Gerardo Barrios Usulután 2023.',
      proyectos: 'Asociación Juvenil del Bajo Lempa (AJUBAL).\n• Miembro activo desde el año 2021 hasta la actualidad.'
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
    mostrandoWizard.value = false
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
  doc.text(formCv.value.perfil.profesion || 'Egresada de Ingeniería en sistemas y redes informáticas', 15, 26)

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
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
            :href="cv.url_publica"
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
              <span class="text-lg">🏷️</span>
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
              @click="mostrandoWizard = false"
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
                  <p class="text-xs opacity-90 font-sans">
                    {{ formCv.perfil.profesion || 'Egresada de Ingeniería en sistemas y redes informáticas' }}
                  </p>
                </div>
              </div>

              <!-- Contacto y Educación -->
              <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                  <p class="font-bold text-red-900">📍 Dirección: <span class="font-normal text-gray-800">{{ formCv.perfil.direccion }}</span></p>
                  <p class="font-bold text-red-900 mt-1">✉️ Email: <span class="font-normal text-gray-800">{{ formCv.perfil.email }}</span></p>
                  <p class="font-bold text-red-900 mt-1">📞 Teléfono: <span class="font-normal text-gray-800">{{ formCv.perfil.telefono }}</span></p>
                </div>
                <div>
                  <p class="font-bold text-red-900">🎓 Educación:</p>
                  <p class="text-gray-800">{{ formCv.perfil.educacion }}</p>
                  <p class="font-bold text-red-900 mt-2">🚩 Idiomas:</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.objetivos.idiomas }}</p>
                </div>
              </div>

              <!-- Sobre mi -->
              <div class="text-xs pt-2 border-t border-gray-100">
                <p class="font-bold text-red-900 mb-1">🎴 Sobre mí:</p>
                <p class="text-gray-800">{{ formCv.perfil.sobreMi }}</p>
              </div>

              <!-- Conocimientos y Valores -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-red-900 mb-1">💼 Conocimientos</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.objetivos.conocimientos }}</p>
                </div>
                <div>
                  <p class="font-bold text-red-900 mb-1">🍀 Valores profesionales</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.objetivos.valores }}</p>
                </div>
              </div>

              <!-- Objetivo y Habilidades -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-red-900 mb-1">🎯 Mi objetivo:</p>
                  <p class="text-gray-800">{{ formCv.objetivos.objetivo }}</p>
                </div>
                <div>
                  <p class="font-bold text-red-900 mb-1">🍀 Habilidades:</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.logros.habilidades }}</p>
                </div>
              </div>

              <!-- Certificados y Logros -->
              <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-100">
                <div>
                  <p class="font-bold text-red-900 mb-1">💼 Certificados:</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.logros.certificados }}</p>
                </div>
                <div>
                  <p class="font-bold text-red-900 mb-1">🎯 Logros:</p>
                  <p class="text-gray-800 whitespace-pre-line">{{ formCv.logros.logros }}</p>
                </div>
              </div>

              <!-- Proyectos -->
              <div class="text-xs pt-2 border-t border-gray-100">
                <p class="font-bold text-red-900 mb-1">📄 Proyectos de beneficio social:</p>
                <p class="text-gray-800 whitespace-pre-line">{{ formCv.logros.proyectos }}</p>
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
                <span>Editar</span>
                <span>✏️</span>
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
                class="w-full py-2.5 px-6 rounded-full border border-black bg-white hover:bg-gray-50 text-[#00589B] text-sm font-medium transition-all shadow-sm flex items-center justify-center cursor-pointer"
                style="font-family: 'Lora', Georgia, serif;"
              >
                Descargar CV
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
          <div class="relative w-full max-w-[460px] bg-[#00C62E] text-white rounded-[18px] p-8 text-center shadow-2xl border border-emerald-400">
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
                  <p class="text-[10px] opacity-80">Egresada de Ingeniería en sistemas</p>
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
          <div v-else-if="pasoActual === 2" class="space-y-5">
            <div class="flex flex-col items-start mb-4">
              <div class="relative w-20 h-20 rounded-full bg-gray-200 border-2 border-black flex items-center justify-center overflow-hidden mb-1">
                <img v-if="formCv.perfil.fotoUrl" :src="formCv.perfil.fotoUrl" class="w-full h-full object-cover" />
                <span v-else class="text-3xl text-gray-600">👤</span>
              </div>
              <label class="text-xs font-bold text-gray-900 cursor-pointer flex items-center gap-1 hover:underline">
                <input type="file" accept="image/*" class="hidden" @change="handleSubirFoto" />
                <span>📷 Subir foto</span>
              </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">👤 Nombre completo:</label>
                  <input v-model="formCv.perfil.nombre" type="text" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">📍 Dirección:</label>
                  <input v-model="formCv.perfil.direccion" type="text" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">✉️ Email:</label>
                  <input v-model="formCv.perfil.email" type="email" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">📞 Teléfono:</label>
                  <input v-model="formCv.perfil.telefono" type="text" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm" />
                </div>
              </div>

              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">🎴 Sobre mi:</label>
                  <textarea v-model="formCv.perfil.sobreMi" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-900 mb-1">🎓 Educación:</label>
                  <textarea v-model="formCv.perfil.educacion" rows="3" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- PASO 3: OBJETIVOS Y VALORES -->
          <div v-else-if="pasoActual === 3" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">🎯 Mi objetivo:</label>
              <textarea v-model="formCv.objetivos.objetivo" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">🍀 Valores profesionales:</label>
              <textarea v-model="formCv.objetivos.valores" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">💼 Conocimientos:</label>
              <textarea v-model="formCv.objetivos.conocimientos" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">🚩 Idiomas:</label>
              <textarea v-model="formCv.objetivos.idiomas" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>
          </div>

          <!-- PASO 4: LOGROS -->
          <div v-else-if="pasoActual === 4" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">💼 Certificados, talleres y cursos:</label>
              <textarea v-model="formCv.logros.certificados" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">🍀 Habilidades:</label>
              <textarea v-model="formCv.logros.habilidades" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">🎯 Logros:</label>
              <textarea v-model="formCv.logros.logros" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-900 mb-1">📄 Proyectos:</label>
              <textarea v-model="formCv.logros.proyectos" rows="4" class="w-full px-3 py-2 rounded-xl bg-[#ebebeb] border border-black text-sm resize-none"></textarea>
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

  </div>
</template>
