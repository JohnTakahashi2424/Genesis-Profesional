<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CvModulo from './CvModulo.vue'

const emit = defineEmits(['volver'])

// Usuario actual
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{"id":1,"nombres":"Dallana Lucia","apellidos":"Campoz Garcia","correo":"usss002419@ugb.edu.sv"}'))

// Estado
const editandoCv = ref(false)
const cargando = ref(false)
const perfilData = ref({
  nombre: (usuario.value.nombres || 'Dallana Lucia') + ' ' + (usuario.value.apellidos || 'Campoz Garcia'),
  profesion: 'Egresada de Ingeniería en sistemas y redes informáticas',
  direccion: 'Usulután',
  email: usuario.value.correo || usuario.value.email || 'usss002419@ugb.edu.sv',
  telefono: '7200 - 6452',
  educacion: 'Universidad Gerardo Barrios (egresado)',
  idiomas: '- Español nativo\n- Ingles básico',
  sobreMi: 'Soy una persona apasionada por la tecnología, con un profundo interés en el campo de la ciberseguridad.',
  conocimientos: '-Conocimientos en redes Protocolos,\nenrutamientos, IPv4 y IPv6, NAT\n-Conocimientos en seguridad Análisis\nde casos de estudio, Phishing Emails',
  valores: '- Liderazgo.\n- Compromiso.\n- Trabajo en equipo.\n- Responsabilidad.\n- Sinceridad.',
  objetivo: 'Incorporarme y consolidarme en un equipo de trabajo donde pueda aplicar los conocimientos y habilidades adquiridos a lo largo de mi formación académica y profesional',
  habilidades: '- Facilidad para expresarme de forma verbal,\ny que esta a su vez sea fluida, entendible y\ncoherente frente a las personas.\n- Facilidad para trabajar en equipo.',
  certificados: '- CCNAv7: Introduction to Networks.\n- CCNAv7: Enterprise Networking, Security, and\nAutomation.\n- Ciberseguridad y privacidad empresarial.',
  logros: 'Primer lugar a nivel de sede en la categoría\ninnovación - Rally Latinoamericano de\ninnovación 2023. - Tercer lugar en\ninvestigación de cátedra - Universidad\nGerardo Barrios Usulután 2023.',
  proyectos: 'Asociación Juvenil del Bajo Lempa (AJUBAL).\n• Miembro activo desde el año 2021 hasta la\nactualidad.'
})

// Cargar CV mas reciente del usuario
const cargarCvPerfil = async () => {
  cargando.value = true
  try {
    const res = await axios.get(`/api/cv/obtener/${usuario.value.id || 1}`)
    if (res.data && res.data.cvs && res.data.cvs.length > 0) {
      const cvReciente = res.data.cvs[0]
      if (cvReciente.nombre_completo) perfilData.value.nombre = cvReciente.nombre_completo
      if (cvReciente.profesion) perfilData.value.profesion = cvReciente.profesion
      if (cvReciente.direccion) perfilData.value.direccion = cvReciente.direccion
      if (cvReciente.email) perfilData.value.email = cvReciente.email
      if (cvReciente.telefono) perfilData.value.telefono = cvReciente.telefono
      if (cvReciente.educacion) perfilData.value.educacion = typeof cvReciente.educacion === 'string' ? cvReciente.educacion : JSON.stringify(cvReciente.educacion)
      if (cvReciente.sobre_mi) perfilData.value.sobreMi = cvReciente.sobre_mi
      if (cvReciente.conocimientos) perfilData.value.conocimientos = typeof cvReciente.conocimientos === 'string' ? cvReciente.conocimientos : JSON.stringify(cvReciente.conocimientos)
      if (cvReciente.valores) perfilData.value.valores = typeof cvReciente.valores === 'string' ? cvReciente.valores : JSON.stringify(cvReciente.valores)
      if (cvReciente.objetivo) perfilData.value.objetivo = cvReciente.objetivo
      if (cvReciente.habilidades) perfilData.value.habilidades = typeof cvReciente.habilidades === 'string' ? cvReciente.habilidades : JSON.stringify(cvReciente.habilidades)
      if (cvReciente.certificados) perfilData.value.certificados = typeof cvReciente.certificados === 'string' ? cvReciente.certificados : JSON.stringify(cvReciente.certificados)
      if (cvReciente.logros) perfilData.value.logros = typeof cvReciente.logros === 'string' ? cvReciente.logros : JSON.stringify(cvReciente.logros)
      if (cvReciente.proyectos_sociales) perfilData.value.proyectos = typeof cvReciente.proyectos_sociales === 'string' ? cvReciente.proyectos_sociales : JSON.stringify(cvReciente.proyectos_sociales)
    }
  } catch (err) {
    console.warn('Error al cargar perfil CV:', err)
  } finally {
    cargando.value = false
  }
}

onMounted(() => {
  cargarCvPerfil()
})

const handleGuardadoExitoso = () => {
  editandoCv.value = false
  emit('volver')
}
</script>

<template>
  <div>
    <!-- Si entra en modo edicion -> Abre el Wizard de CvModulo -->
    <div v-if="editandoCv">
      <CvModulo @volver="handleGuardadoExitoso" />
    </div>

    <!-- Vista Calcada de Perfil Profesional (Captura de Pantalla) -->
    <div v-else class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200 text-left max-w-5xl mx-auto relative">
      
      <!-- Boton Editar en la esquina superior derecha -->
      <button
        @click="editandoCv = true"
        class="absolute top-8 right-8 px-6 py-2 rounded-full border border-black bg-white hover:bg-gray-50 text-gray-900 text-sm font-medium transition-all shadow-xs flex items-center gap-2 cursor-pointer"
        style="font-family: 'Lora', Georgia, serif;"
      >
        <span>Editar</span>
        <span>✏️</span>
      </button>

      <!-- Encabezado con Avatar y Titulos -->
      <div class="flex items-center gap-6 mb-6 pt-2">
        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-900 shrink-0 bg-gray-200 flex items-center justify-center">
          <img :src="perfilData.fotoUrl || '/images/para los cv.jpg'" alt="Avatar" class="w-full h-full object-cover" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-gray-950 font-serif tracking-tight" style="font-family: 'Lora', Georgia, serif;">
            {{ perfilData.nombre }}
          </h2>
          <p class="text-sm font-medium text-gray-800 mt-1">
            {{ perfilData.profesion }}
          </p>
        </div>
      </div>

      <!-- Datos de contacto y Educacion -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 text-sm">
        <div class="space-y-2">
          <p class="flex items-center gap-2">
            <span class="font-bold text-[#010C67]">📍 Dirección:</span>
            <span class="text-gray-900">{{ perfilData.direccion }}</span>
          </p>
          <p class="flex items-center gap-2">
            <span class="font-bold text-[#010C67]">✉️ Email:</span>
            <span class="text-gray-900">{{ perfilData.email }}</span>
          </p>
          <p class="flex items-center gap-2">
            <span class="font-bold text-[#010C67]">📞 Teléfono:</span>
            <span class="text-gray-900">{{ perfilData.telefono }}</span>
          </p>
        </div>

        <div class="space-y-3">
          <div>
            <p class="font-bold text-[#010C67]">🎓 Educación:</p>
            <p class="text-gray-900 mt-0.5">{{ perfilData.educacion }}</p>
          </div>
          <div>
            <p class="font-bold text-[#010C67]">🚩 Idiomas:</p>
            <p class="text-gray-900 whitespace-pre-line mt-0.5">{{ perfilData.idiomas }}</p>
          </div>
        </div>
      </div>

      <!-- Sobre mi -->
      <div class="mb-6 pt-4 border-t border-gray-100">
        <p class="font-bold text-[#010C67] text-sm mb-1">🎴 Sobre mí:</p>
        <p class="text-sm text-gray-900 leading-relaxed max-w-3xl">
          {{ perfilData.sobreMi }}
        </p>
      </div>

      <!-- Conocimientos y Valores -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6 pt-4 border-t border-gray-100 text-sm">
        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>💼</span>
            <span>Conocimientos</span>
          </p>
          <p class="text-gray-900 whitespace-pre-line leading-relaxed">
            {{ perfilData.conocimientos }}
          </p>
        </div>

        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>🍀</span>
            <span>Valores profesionales</span>
          </p>
          <p class="text-gray-900 whitespace-pre-line leading-relaxed">
            {{ perfilData.valores }}
          </p>
        </div>
      </div>

      <!-- Objetivo y Habilidades -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6 pt-4 border-t border-gray-100 text-sm">
        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>🎯</span>
            <span>Mi objetivo:</span>
          </p>
          <p class="text-gray-900 leading-relaxed">
            {{ perfilData.objetivo }}
          </p>
        </div>

        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>🍀</span>
            <span>Habilidades:</span>
          </p>
          <p class="text-gray-900 whitespace-pre-line leading-relaxed">
            {{ perfilData.habilidades }}
          </p>
        </div>
      </div>

      <!-- Certificados y Logros -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6 pt-4 border-t border-gray-100 text-sm">
        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>💼</span>
            <span>Certificados:</span>
          </p>
          <p class="text-gray-900 whitespace-pre-line leading-relaxed">
            {{ perfilData.certificados }}
          </p>
        </div>

        <div>
          <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
            <span>🎯</span>
            <span>Logros:</span>
          </p>
          <p class="text-gray-900 whitespace-pre-line leading-relaxed">
            {{ perfilData.logros }}
          </p>
        </div>
      </div>

      <!-- Proyectos -->
      <div class="pt-4 border-t border-gray-100 text-sm">
        <p class="font-bold text-[#010C67] mb-2 flex items-center gap-1.5">
          <span>📄</span>
          <span>Proyectos de beneficio social:</span>
        </p>
        <p class="text-gray-900 whitespace-pre-line leading-relaxed">
          {{ perfilData.proyectos }}
        </p>
      </div>

    </div>
  </div>
</template>
