<script setup>
import { ref, computed } from 'vue'

const emit = defineEmits(['login', 'registro'])

const activeTab = ref('inicio')
const areaSlideIndex = ref(0)
const alcanceSlideIndex = ref(0)

const areasPasantia = [
  {
    titulo: 'Desarrollo de Software y Web',
    desc: 'Participación en el ciclo de vida de aplicaciones, manejo de frameworks (Frontend/Backend), gestión de bases de datos SQL/NoSQL y metodologías ágiles.',
    img: '/images/desarollo_web.png'
  },
  {
    titulo: 'Infraestructura y Redes',
    desc: 'Configuración y mantenimiento de redes locales (LAN/WAN), administración de servidores, gestión de servicios en la nube (Cloud) y soporte a equipos de comunicación.',
    img: '/images/redes_infraestructura.png'
  },
  {
    titulo: 'Aseguramiento de Calidad (QA) y Testing',
    desc: 'Ejecución de pruebas funcionales, no funcionales, garantiza la viabilidad del software, diseño de pruebas, detección de errores y uso de herramientas para la automatización de pruebas.',
    img: '/images/seguramiento_QA.png'
  },
  {
    titulo: 'Seguridad Informática',
    desc: 'Implementación de protocolos de seguridad, análisis de vulnerabilidades, gestión de identidades y auditoría de sistemas para la protección de activos digitales.',
    img: '/images/areas_seguridad_informatica.png'
  }
]

const alcancesList = [
  {
    titulo: 'Evaluación',
    desc: 'Seguimiento continuo por parte de un tutor empresarial y un supervisor docente para validar el crecimiento de competencias técnicas y habilidades blandas.',
    img: '/images/alcances_evaluacion.png'
  },
  {
    titulo: 'Formación Práctica',
    desc: 'Ejecución de tareas vinculadas directamente a la especialidad del estudiante.',
    img: '/images/alcances_formacion_practica.png'
  },
  {
    titulo: 'Duración',
    desc: 'Cumplimiento de las horas sociales o profesionales estipuladas en el reglamento académico.',
    img: '/images/alcances_duracion.png'
  },
  {
    titulo: 'Vinculación Profesional',
    desc: 'Creación de redes de contacto estratégicas dentro del sector tecnológico y posibilidad de transición a una plaza fija, basada en el desempeño demostrado.',
    img: '/images/alcances_vinculacion_profesional.png'
  }
]

const visibleAreas = computed(() => {
  return areaSlideIndex.value === 0 
    ? areasPasantia.slice(0, 3) 
    : areasPasantia.slice(1, 4)
})

const visibleAlcances = computed(() => {
  return alcanceSlideIndex.value === 0 
    ? alcancesList.slice(0, 3) 
    : alcancesList.slice(1, 4)
})

const cambiarTab = (tab) => {
  activeTab.value = tab
  areaSlideIndex.value = 0
  alcanceSlideIndex.value = 0
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const nextAreaSlide = () => { areaSlideIndex.value = 1 }
const prevAreaSlide = () => { areaSlideIndex.value = 0 }
const nextAlcanceSlide = () => { alcanceSlideIndex.value = 1 }
const prevAlcanceSlide = () => { alcanceSlideIndex.value = 0 }
</script>

<template>
  <div class="web-informativa">
    <!-- Header / Barra de Navegación -->
    <header class="navbar-ugb">
      <a href="#" @click.prevent="cambiarTab('inicio')" class="navbar-brand-ugb">
        <img 
          src="/images/LOGO_WEB_INF.png" 
          alt="Logo UGB" 
          class="logo-img"
        />
      </a>

      <nav class="navbar-links">
        <button 
          type="button"
          @click="cambiarTab('empresas')" 
          class="nav-item-ugb" 
          :class="{ 'active-tab': activeTab === 'empresas' }"
        >
          Empresas socias
        </button>
        <button 
          type="button"
          @click="cambiarTab('areas')" 
          class="nav-item-ugb" 
          :class="{ 'active-tab': activeTab === 'areas' }"
        >
          Áreas de pasantías
        </button>
        <button 
          type="button"
          @click="cambiarTab('requisitos')" 
          class="nav-item-ugb" 
          :class="{ 'active-tab': activeTab === 'requisitos' }"
        >
          Requisitos
        </button>
        <button 
          type="button"
          @click="cambiarTab('alcance')" 
          class="nav-item-ugb" 
          :class="{ 'active-tab': activeTab === 'alcance' }"
        >
          Alcance
        </button>
      </nav>

      <div class="nav-actions">
        <button type="button" @click="emit('registro')" class="btn-register-ugb">
          Registrarse
        </button>
        <button type="button" @click="emit('login')" class="btn-login-ugb">
          Iniciar sesión
        </button>
      </div>
    </header>

    <!-- Contenido Principal Dinámico -->
    <main class="main-container">
      <Transition name="fade" mode="out-in">
        
        <!-- ── VISTA INICIAL (INICIO) ── -->
        <div v-if="activeTab === 'inicio'" key="inicio">
          <section class="hero-row">
            <div class="hero-text-block">
              <h2 class="hero-title">
                Conectamos el talento técnico con las empresas líderes del sector tecnológico para transformar el aprendizaje en experiencia profesional real.
              </h2>
            </div>
            <div class="hero-image-block">
              <img src="/images/Web_info_imagen_1.png" alt="Mentoría tecnológica" class="hero-img" />
            </div>
          </section>

          <section class="hero-row reverse-mobile">
            <div class="hero-image-block">
              <img src="/images/web_info_imagen_2.png" alt="Equipo de desarrollo" class="hero-img" />
            </div>
            <div class="hero-text-block">
              <h2 class="hero-title">
                Génesis Profesional es el puente entre tus estudios y tu primera experiencia en el mundo laboral. Encuentra la pasantía ideal y potencia tus habilidades.
              </h2>
            </div>
          </section>
        </div>

        <!-- ── VISTA EMPRESAS SOCIAS ── -->
        <div v-else-if="activeTab === 'empresas'" key="empresas">
          <article class="asymmetric-block">
            <div class="block-text">
              <h2 class="hero-title">
                Contamos con una red estratégica de aliados comprometidos con el desarrollo del talento joven. Nuestras empresas socias pertenecen a diversos sectores productivos.
              </h2>
            </div>
            <div class="block-img-container">
              <img src="/images/empresas_socias_imagen_1.png" alt="Alianza estratégica" class="block-img" />
            </div>
          </article>

          <article class="asymmetric-block block-reverse">
            <div class="block-text">
              <h2 class="hero-title">
                Brindamos espacios de aprendizaje real en áreas de tecnología. Cada convenio garantiza que el estudiante se integre a un entorno profesional de alto nivel.
              </h2>
            </div>
            <div class="block-img-container">
              <img src="/images/empresas_socias_imagen_2.png" alt="Espacio de trabajo" class="block-img" />
            </div>
          </article>
        </div>

        <!-- ── VISTA ÁREAS DE PASANTÍAS ── -->
        <div v-else-if="activeTab === 'areas'" key="areas">
          <section class="hero-row mb-5">
            <div class="hero-text-block">
              <h2 class="hero-title">
                Contamos con vacantes especializadas para fortalecer el perfil técnico de nuestros futuros ingenieros en las siguientes dimensiones:
              </h2>
            </div>
            <div class="hero-image-block">
              <img src="/images/areas_imagen_1.png" alt="Ingenieros trabajando" class="hero-img" />
            </div>
          </section>

          <!-- Slider / Carrusel de Dimensiones -->
          <section class="slider-wrapper">
            <button 
              type="button"
              class="slider-btn" 
              @click="prevAreaSlide" 
              v-if="areaSlideIndex > 0"
              aria-label="Anterior"
            >
              <i class="bi bi-chevron-left"></i>
            </button>
            
            <div class="slider-cards-row">
              <div v-for="area in visibleAreas" :key="area.titulo" class="card-ds">
                <img :src="area.img" class="card-ds-img" :alt="area.titulo" />
                <div class="card-ds-body">
                  <strong>{{ area.titulo }}:</strong> {{ area.desc }}
                </div>
              </div>
            </div>

            <button 
              type="button"
              class="slider-btn" 
              @click="nextAreaSlide" 
              v-if="areaSlideIndex === 0"
              aria-label="Siguiente"
            >
              <i class="bi bi-chevron-right"></i>
            </button>
          </section>
        </div>

        <!-- ── VISTA REQUISITOS ── -->
        <div v-else-if="activeTab === 'requisitos'" key="requisitos">
          <h3 class="hero-title mb-5">Requisitos para el proceso de pasantía:</h3>
          
          <article class="asymmetric-block">
            <div class="block-img-container">
              <img src="/images/requisitos_imagen_1.png" alt="Estudiante en laptop" class="block-img" />
            </div>
            <div class="block-text">
              <ul class="requisito-list">
                <li>
                  <strong>Estado Académico:</strong> Ser estudiante activo de la carrera de Ingeniería en Sistemas y Redes Informáticas y haber aprobado al menos el 70% de las unidades valorativas del plan de estudios.
                </li>
                <li>
                  <strong>Rendimiento:</strong> Poseer un Coeficiente de Unidades de Mérito (CUM) igual o mayor a 7.0.
                </li>
              </ul>
            </div>
          </article>

          <article class="asymmetric-block block-reverse">
            <div class="block-img-container">
              <img src="/images/requisitos_imagen_2.png" alt="Reunión de asesoría" class="block-img" />
            </div>
            <div class="block-text">
              <ul class="requisito-list">
                <li>
                  <strong>Competencias Técnicas:</strong> Aprobar las evaluaciones previas para determinar si es aprobado o no para el proceso de pasantías en el área solicitada.
                </li>
                <li>
                  <strong>Disponibilidad Horaria:</strong> Disponer del tiempo necesario para cumplir con la jornada acordada con la empresa, sin afectar el horario de las materias inscritas en el ciclo actual.
                </li>
              </ul>
            </div>
          </article>
        </div>

        <!-- ── VISTA ALCANCE ── -->
        <div v-else-if="activeTab === 'alcance'" key="alcance">
          <section class="hero-row mb-5">
            <div class="hero-text-block">
              <h2 class="hero-title">
                El programa de pasantías tiene como objetivo la transición efectiva del aula al entorno laboral.
              </h2>
            </div>
            <div class="hero-image-block">
              <img src="/images/alcances_imagen_1.png" alt="Reunión académica" class="hero-img" />
            </div>
          </section>

          <h3 class="hero-title mb-4">El alcance comprende:</h3>

          <section class="slider-wrapper">
            <button 
              type="button"
              class="slider-btn" 
              @click="prevAlcanceSlide" 
              v-if="alcanceSlideIndex > 0"
              aria-label="Anterior"
            >
              <i class="bi bi-chevron-left"></i>
            </button>
            
            <div class="slider-cards-row">
              <div v-for="alcance in visibleAlcances" :key="alcance.titulo" class="card-ds">
                <img :src="alcance.img" class="card-ds-img" :alt="alcance.titulo" />
                <div class="card-ds-body">
                  <strong>{{ alcance.titulo }}:</strong> {{ alcance.desc }}
                </div>
              </div>
            </div>

            <button 
              type="button"
              class="slider-btn" 
              @click="nextAlcanceSlide" 
              v-if="alcanceSlideIndex === 0"
              aria-label="Siguiente"
            >
              <i class="bi bi-chevron-right"></i>
            </button>
          </section>
        </div>

      </Transition>
    </main>

    <!-- Footer -->
    <footer class="footer-ugb">
      <div class="footer-inner">
        <div class="footer-grid">
          <!-- Logo -->
          <div class="footer-col-logo">
            <img 
              src="/images/LOGO_WEB_INF.png" 
              alt="Logo UGB" 
              class="footer-logo-img"
            />
          </div>

          <!-- Redes Sociales -->
          <div class="footer-col-social">
            <h4 class="footer-section-title">Redes sociales</h4>
            <div class="footer-social-icons">
              <a 
                href="https://www.instagram.com/ugb.sv?igsh=anBwMTl5MnlxanAz" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="footer-social-icon"
                aria-label="Instagram"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29" fill="none">
                  <path d="M14.149 0.00404078C11.4214 0.00404078 9.44139 0.0363675 8.24935 0.0990003C7.18641 0.13997 6.13349 0.320665 5.11771 0.63643C4.08417 1.0045 3.14547 1.59794 2.36969 2.37373C1.5939 3.14952 1.00046 4.08821 0.632389 5.12175C0.316624 6.13753 0.135929 7.19045 0.0949596 8.25339C0.0323267 9.45352 0 11.4254 0 14.153C0 16.8806 0.0323267 18.8606 0.0949596 20.0526C0.133347 21.1133 0.313164 22.174 0.632389 23.1842C1.0001 24.2167 1.59209 25.1562 2.36995 25.932C3.1377 26.7038 4.07922 27.2776 5.11771 27.6049C6.12185 27.9645 7.17853 28.1666 8.24935 28.203C9.44948 28.2656 11.4214 28.2979 14.149 28.2979C16.8765 28.2979 18.8565 28.2656 20.0486 28.203C21.1115 28.162 22.1644 27.9813 23.1802 27.6655C24.2131 27.2962 25.1511 26.7023 25.9267 25.9267C26.7023 25.1511 27.2962 24.213 27.6655 23.1802C27.9834 22.1646 28.1625 21.1207 28.203 20.0486C28.2656 18.8484 28.2979 16.8765 28.2979 14.149C28.2979 11.4214 28.2777 9.4616 28.2353 8.26955C28.1788 7.19914 27.9874 6.14014 27.6655 5.11771C27.2977 4.08437 26.7045 3.14581 25.9291 2.37004C25.1537 1.59427 24.2154 1.00072 23.1822 0.632389C22.1664 0.316624 21.1135 0.135929 20.0506 0.0949593C18.8478 0.031653 16.8812 0 14.151 0L14.149 0.00404078ZM13.1388 25.8653C11.4551 25.8653 10.1485 25.8445 9.21915 25.8027C8.0639 25.783 6.91613 25.6132 5.80465 25.2976C5.18976 25.0558 4.63129 24.6899 4.16409 24.2227C3.6969 23.7555 3.33099 23.1971 3.08921 22.5822C2.77363 21.4707 2.60384 20.3229 2.58411 19.1677C2.54168 18.2423 2.52148 16.9452 2.52148 15.2481V13.2276C2.52148 11.544 2.54235 10.2374 2.58411 9.30805C2.59623 8.15439 2.76595 7.00477 3.08921 5.89355C3.31751 5.27147 3.67848 4.70653 4.14705 4.23796C4.61561 3.7694 5.18055 3.40843 5.80263 3.18013C6.91411 2.86455 8.06189 2.69476 9.21713 2.67503C10.1425 2.6326 11.4396 2.61239 13.1367 2.61239H15.1571C16.8408 2.61239 18.1474 2.63327 19.0767 2.67503C20.2304 2.68715 21.38 2.85687 22.4912 3.18013C23.1137 3.40819 23.679 3.76904 24.148 4.23762C24.6169 4.7062 24.9782 5.27126 25.2067 5.89355C25.5299 7.00275 25.6997 8.15641 25.7118 9.30805C25.7522 10.193 25.7744 11.4901 25.7744 13.2276V15.2481C25.7744 16.9317 25.7535 18.2383 25.7118 19.1677C25.6921 20.3229 25.5223 21.4707 25.2067 22.5822C24.9649 23.1971 24.599 23.7555 24.1318 24.2227C23.6646 24.6899 23.1061 25.0558 22.4912 25.2976C21.3841 25.6209 20.2284 25.7906 19.0767 25.8027C18.1938 25.8431 16.8947 25.8653 15.1571 25.8653H13.1388ZM21.7255 4.85303C21.3757 4.85075 21.0332 4.95288 20.7417 5.14637C20.4503 5.33987 20.2233 5.61591 20.0896 5.93919C19.956 6.26246 19.9218 6.61826 19.9915 6.96106C20.0612 7.30386 20.2316 7.61805 20.4809 7.86345C20.7216 8.1145 21.0328 8.28659 21.3733 8.35694C21.7139 8.42729 22.0678 8.39259 22.3882 8.25743C22.5971 8.16632 22.7844 8.03205 22.9378 7.86345C23.261 7.52402 23.455 7.08559 23.4934 6.61888C23.4856 6.15244 23.2968 5.7073 22.967 5.37743C22.6371 5.04757 22.1919 4.86083 21.7255 4.85303ZM14.149 7.1159C12.9058 7.10798 11.6838 7.43757 10.6132 8.06953C9.54242 8.6898 8.65141 9.5808 8.0271 10.6557C7.40645 11.7307 7.07971 12.9501 7.07971 14.1914C7.07971 15.4327 7.40645 16.6521 8.0271 17.7271C8.65017 18.7998 9.54298 19.6909 10.6169 20.3118C11.6908 20.9328 12.9085 21.262 14.149 21.2669C16.0234 21.26 17.819 20.5123 19.1444 19.1869C20.4699 17.8615 21.2175 16.0658 21.2244 14.1914C21.2244 12.9589 20.8951 11.7265 20.2708 10.6557C19.6505 9.58175 18.7586 8.68988 17.6847 8.06953C16.6141 7.43757 15.3921 7.10798 14.149 7.1159ZM14.149 18.7333C12.9433 18.7333 11.787 18.2543 10.9345 17.4018C10.082 16.5493 9.60303 15.393 9.60303 14.1873C9.60303 12.9817 10.082 11.8254 10.9345 10.9729C11.787 10.1204 12.9433 9.64141 14.149 9.64141C15.3546 9.64141 16.5109 10.1204 17.3634 10.9729C18.2159 11.8254 18.6949 12.9817 18.6949 14.1873C18.6949 15.393 18.2159 16.5493 17.3634 17.4018C16.5109 18.2543 15.3546 18.7333 14.149 18.7333Z" fill="white"/>
                </svg>
              </a>
              <a 
                href="https://www.facebook.com/share/1BLV1hfAJB/" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="footer-social-icon"
                aria-label="Facebook"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="31" height="31" viewBox="0 0 31 31" fill="none">
                  <g clip-path="url(#clip0_2209_235)">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.73862C0 5.63095 3.7389e-08 4.57711 0.410244 3.77419C0.77236 3.06771 1.34725 2.49282 2.05373 2.1307C2.85916 1.72046 3.91049 1.72046 6.01816 1.72046H21.5748C23.6825 1.72046 24.7363 1.72046 25.5392 2.1307C26.2457 2.49282 26.8206 3.06771 27.1827 3.77419C27.593 4.57962 27.593 5.63095 27.593 7.73862V23.2953C27.593 25.4029 27.593 26.4568 27.1827 27.2597C26.822 27.9672 26.2467 28.5425 25.5392 28.9032C24.7338 29.3134 23.6825 29.3134 21.5748 29.3134H6.01816C3.91049 29.3134 2.85665 29.3134 2.05373 28.9032C1.34623 28.5425 0.770963 27.9672 0.410244 27.2597C3.7389e-08 26.4543 0 25.4029 0 23.2953V7.73862ZM6.02192 2.97126H21.5786C22.6537 2.97126 23.3851 2.97126 23.9497 3.01894C24.4992 3.06285 24.7815 3.14439 24.9772 3.24476C25.4489 3.48564 25.8328 3.86953 26.0737 4.34125C26.174 4.53696 26.2556 4.81799 26.2995 5.36874C26.3459 5.9333 26.3472 6.66095 26.3472 7.73987V23.2965C26.3472 24.3717 26.3459 25.1031 26.2995 25.6676C26.2543 26.2171 26.1728 26.4994 26.0737 26.6951C25.8331 27.1672 25.4493 27.5511 24.9772 27.7916C24.7815 27.892 24.4992 27.9735 23.9497 28.0174C23.3851 28.0626 22.6575 28.0639 21.5786 28.0639H18.0909V17.6635H21.014L21.4732 14.4518H18.0984V13.0718C18.0984 12.8376 18.1139 12.6256 18.1448 12.4357C18.2791 11.629 18.7207 11.2313 19.6503 11.2313H21.5071V8.15764L21.487 8.15388C21.1533 8.10872 20.467 8.01588 19.1911 8.01588C16.4687 8.01588 14.8754 9.44608 14.8754 12.7205V14.4518H11.6637V17.6635H14.8754V28.0639H6.03071C4.95554 28.0639 4.22413 28.0639 3.65957 28.0162C3.11007 27.9723 2.8278 27.8907 2.63208 27.7904C2.15998 27.5498 1.77615 27.166 1.53559 26.6939C1.43523 26.4982 1.35368 26.2171 1.30977 25.6664C1.26335 25.1018 1.26335 24.3742 1.26335 23.2953V7.73862C1.26335 6.66345 1.26335 5.93204 1.30977 5.36749C1.35493 4.81799 1.43648 4.53571 1.53559 4.34C1.77647 3.86828 2.16037 3.48438 2.63208 3.2435C2.8278 3.14314 3.11007 3.06159 3.66083 3.01768C4.22538 2.97252 4.95303 2.97126 6.03196 2.97126H6.02192Z" fill="white"/>
                  </g>
                  <defs>
                    <clipPath id="clip0_2209_235">
                      <rect width="30.1096" height="30.1096" fill="white"/>
                    </clipPath>
                  </defs>
                </svg>
              </a>
            </div>
          </div>

          <!-- Contacto -->
          <div class="footer-col-contact">
            <div class="footer-contact-block">
              <h4 class="footer-section-title footer-contact-title">Contacto:</h4>
              <div class="footer-contact-item">
                <svg class="footer-contact-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 37 37" fill="none">
                  <path d="M28.2604 33.7712H28.8283C29.3042 33.7559 29.7494 33.5103 30.0257 33.1112L33.5103 28.0915C33.7405 27.7538 33.8326 27.3393 33.7559 26.9249C33.7182 26.7229 33.6404 26.5304 33.527 26.359C33.4136 26.1876 33.267 26.0408 33.0958 25.9271L25.5586 20.9075C24.9293 20.493 24.085 20.5851 23.5784 21.1377L20.6925 24.2539C19.5259 23.5631 17.5763 22.3197 16.0413 20.7846C14.5062 19.2496 13.2628 17.3001 12.5721 16.1488L15.6882 13.2629C16.2408 12.7563 16.3483 11.912 15.9185 11.2826L10.8988 3.74551C10.6686 3.40779 10.3155 3.16218 9.91641 3.08543C9.50194 3.00868 9.08748 3.08543 8.74976 3.33104L3.73012 6.80027C3.33101 7.07658 3.0854 7.52175 3.07005 7.99762C3.024 9.08751 2.82444 18.8044 10.423 26.3876C17.2693 33.234 25.835 33.7559 28.2604 33.7559V33.7712Z" fill="white"/>
                </svg>
                <span>(503) 2645 6500</span>
              </div>
              <div class="footer-contact-item">
                <svg class="footer-contact-icon" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 33 33" fill="none">
                  <g clip-path="url(#clip0_2197_225)">
                    <path d="M29.8604 28.9648H26.7579V15.1796L17.4506 21.0875L8.14334 15.1796V28.9648H5.04091V10.0594H6.90237L17.4506 16.755L27.9989 10.0594H29.8604M29.8604 6.90845H5.04091C3.31906 6.90845 1.93848 8.3106 1.93848 10.0594V28.9648C1.93848 29.8005 2.26534 30.6019 2.84716 31.1928C3.42898 31.7837 4.21809 32.1157 5.04091 32.1157H29.8604C30.6832 32.1157 31.4723 31.7837 32.0541 31.1928C32.6359 30.6019 32.9628 29.8005 32.9628 28.9648V10.0594C32.9628 9.22368 32.6359 8.42224 32.0541 7.83133C31.4723 7.24042 30.6832 6.90845 29.8604 6.90845Z" fill="white"/>
                  </g>
                  <defs>
                    <clipPath id="clip0_2197_225">
                      <rect width="32.9634" height="32.9634" fill="white"/>
                    </clipPath>
                  </defs>
                </svg>
                <span>consultas@ugb.edu.sv</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.web-informativa {
  --color-ugb-blue: #000B58;
  --color-text-dark: #000000;
  --color-bg-light: #F4F4F4;
  --font-title: 'Lora', serif;
  --font-body: 'Inter', sans-serif;

  font-family: var(--font-body);
  background-color: var(--color-bg-light);
  color: var(--color-text-dark);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header / Navbar */
.navbar-ugb {
  background-color: var(--color-ugb-blue);
  padding: 18px 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: none;
}

.navbar-brand-ugb {
  display: flex;
  align-items: center;
  text-decoration: none;
}

.logo-img {
  width: 196px;
  height: 101.165px;
  transform: rotate(0.921deg);
  aspect-ratio: 31/16;
  object-fit: contain;
  filter: brightness(0) invert(1);
}

.navbar-links {
  display: flex;
  gap: 36px;
  align-items: center;
}

.nav-item-ugb {
  background: transparent;
  border: none;
  color: #ffffff;
  font-family: var(--font-body);
  font-size: 17px;
  font-weight: 400;
  opacity: 0.95;
  padding: 6px 4px;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.2s ease;
  white-space: nowrap;
}

.nav-item-ugb:hover {
  opacity: 1;
}

.nav-item-ugb.active-tab {
  background-color: transparent;
  text-decoration: underline;
  text-underline-offset: 8px;
  text-decoration-thickness: 2px;
  text-decoration-color: #ffffff;
  opacity: 1;
  font-weight: 500;
}

.nav-actions {
  display: flex;
  gap: 28px;
  align-items: center;
}

.btn-register-ugb {
  background: transparent;
  border: none;
  color: #ffffff;
  font-family: var(--font-body);
  font-size: 17px;
  font-weight: 400;
  cursor: pointer;
  transition: opacity 0.2s;
  padding: 6px 8px;
  opacity: 0.95;
}

.btn-register-ugb:hover {
  opacity: 1;
}

.btn-login-ugb {
  border: 1.5px solid #ffffff;
  border-radius: 9999px;
  color: #ffffff;
  background-color: transparent;
  padding: 8px 26px;
  font-family: var(--font-title);
  font-size: 17px;
  font-weight: 400;
  letter-spacing: 0.3px;
  cursor: pointer;
  transition: background-color 0.2s ease, opacity 0.2s ease;
  box-shadow: none !important;
  text-shadow: none !important;
  outline: none;
}

.btn-login-ugb:hover {
  background-color: rgba(255, 255, 255, 0.14);
  color: #ffffff;
  box-shadow: none !important;
}

.btn-login-ugb:focus,
.btn-login-ugb:active {
  box-shadow: none !important;
  outline: none;
}

/* Container */
.main-container {
  max-width: 1300px;
  width: 100%;
  margin: 56px auto;
  padding: 0 48px;
  flex: 1;
}

/* Hero Rows */
.hero-row {
  display: flex;
  align-items: center;
  margin-bottom: 64px;
  gap: 56px;
}

.hero-text-block {
  flex: 1;
}

.hero-title {
  font-family: var(--font-title);
  font-size: 30px;
  font-weight: 500;
  line-height: 1.45;
  color: var(--color-text-dark);
  margin: 0;
}

.hero-image-block {
  flex: 1.1;
}

.hero-img {
  width: 100%;
  height: auto;
  border-radius: 20px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
  object-fit: cover;
  display: block;
}

/* Asymmetric Blocks */
.asymmetric-block {
  background-color: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.04);
  display: flex;
  align-items: stretch;
  overflow: hidden;
  margin-bottom: 48px;
}

.block-reverse {
  flex-direction: row-reverse;
}

.asymmetric-block .block-text {
  flex: 1;
  padding: 48px;
  display: flex;
  align-items: center;
}

.asymmetric-block .block-img-container {
  flex: 1;
}

.asymmetric-block .block-img {
  width: 100%;
  height: 100%;
  min-height: 320px;
  object-fit: cover;
  display: block;
}

/* Requisitos List */
.requisito-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.requisito-list li {
  font-size: 17px;
  line-height: 1.6;
  position: relative;
  padding-left: 24px;
}

.requisito-list li::before {
  content: "•";
  color: #000000;
  font-size: 26px;
  position: absolute;
  left: 0;
  top: -2px;
}

/* Slider Cards */
.slider-wrapper {
  display: flex;
  align-items: center;
  gap: 20px;
  position: relative;
  margin: 40px 0;
}

.slider-cards-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  width: 100%;
}

.card-ds {
  background-color: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.card-ds-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.card-ds-body {
  padding: 24px;
  font-size: 15px;
  line-height: 1.6;
  color: #333333;
}

.slider-btn {
  background: none;
  border: none;
  font-size: 34px;
  color: #000000;
  cursor: pointer;
  transition: transform 0.2s;
  padding: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.slider-btn:hover {
  transform: scale(1.15);
}

/* Footer */
.footer-ugb {
  background-color: var(--color-ugb-blue);
  color: #ffffff;
  padding: 60px 0;
  margin-top: auto;
}

.footer-inner {
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 48px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1.5fr;
  align-items: center;
  gap: 32px;
}

.footer-col-logo {
  display: flex;
  justify-content: flex-start;
}

.footer-logo-img {
  width: 100%;
  max-width: 397px;
  height: auto;
  max-height: 205px;
  object-fit: contain;
  filter: brightness(0) invert(1);
}

.footer-col-social {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.footer-section-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  letter-spacing: 0.5px;
}

.footer-social-icons {
  display: flex;
  gap: 16px;
  align-items: center;
}

.footer-social-icon {
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s ease, transform 0.2s ease;
  text-decoration: none;
}

.footer-social-icon:hover {
  opacity: 0.8;
  transform: scale(1.08);
}

.footer-col-contact {
  display: flex;
  justify-content: center;
}

.footer-contact-block {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.footer-contact-title {
  text-align: center;
}

.footer-contact-item {
  display: flex;
  align-items: center;
  gap: 14px;
  font-size: 15px;
  opacity: 0.92;
}

.footer-contact-icon {
  flex-shrink: 0;
  display: block;
}

/* Transiciones */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Margen utilitario */
.mb-4 { margin-bottom: 24px; }
.mb-5 { margin-bottom: 36px; }

/* Responsive */
@media (max-width: 991px) {
  .navbar-ugb {
    padding: 20px 24px;
    flex-direction: column;
    gap: 16px;
  }
  .navbar-links {
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
  }
  .main-container {
    margin: 32px auto;
    padding: 0 20px;
  }
  .hero-row {
    flex-direction: column;
    gap: 32px;
    margin-bottom: 48px;
  }
  .hero-row.reverse-mobile {
    flex-direction: column-reverse;
  }
  .asymmetric-block {
    flex-direction: column !important;
  }
  .asymmetric-block .block-text {
    padding: 24px;
  }
  .slider-cards-row {
    grid-template-columns: 1fr;
  }
  .footer-grid {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .footer-col-logo,
  .footer-col-social,
  .footer-col-contact {
    justify-content: center;
    align-items: center;
  }
  .footer-social-icons {
    justify-content: center;
  }
  .footer-inner {
    padding: 0 20px;
  }
}

@media (max-width: 576px) {
  .hero-title {
    font-size: 22px;
  }
  .logo-img {
    height: 55px;
  }
}
</style>
