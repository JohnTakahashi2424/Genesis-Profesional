<script setup>
import { authStorage } from '../utils/storage'
import { logger } from '../utils/logger'

import { ref, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver'])

// Estado del usuario activo
const usuario = ref(authStorage.getUser())

// Estado del formulario
const form = ref({
  titulo: '',
  link: ''
})

const modoEdicion = ref(false)
const idEditando = ref(null)
const cargando = ref(false)
const guardando = ref(false)
const intentoGuardar = ref(false)
const errorMensaje = ref('')
const exitoMensaje = ref('')

// Lista de enlaces de portafolio
const portafolios = ref([])

const getAuthHeaders = () => {
  const token = authStorage.getToken()
  return token ? { Authorization: `Bearer ${token}` } : {}
}

const cargarPortafolios = async () => {
  cargando.value = true
  errorMensaje.value = ''
  try {
    const userId = usuario.value?.id || usuario.value?.user_id || 0
    const res = await axios.get('/api/portafolio', {
      headers: getAuthHeaders(),
      params: { usuario_id: userId }
    })
    if (res.data && res.data.status === 'success') {
      portafolios.value = res.data.data || []
    }
  } catch (err) {
    logger.warn('Error al cargar enlaces de portafolio:', err)
    // Fallback a ruta individual heredada si existiese
    try {
      const userId = usuario.value?.id || usuario.value?.user_id || 0
      if (userId) {
        const resOld = await axios.get(`/api/portafolio/obtener/${userId}`, {
          headers: getAuthHeaders()
        })
        if (resOld.data?.link_portafolio) {
          portafolios.value = [
            {
              id: 1,
              titulo: 'Mi portafolio',
              link_portafolio: resOld.data.link_portafolio
            }
          ]
        }
      }
    } catch {}
  } finally {
    cargando.value = false
  }
}

const resetFormulario = () => {
  form.value = {
    titulo: '',
    link: ''
  }
  modoEdicion.value = false
  idEditando.value = null
  intentoGuardar.value = false
}

const guardarPortafolio = async () => {
  intentoGuardar.value = true
  if (!form.value.titulo.trim() || !form.value.link.trim()) {
    return
  }

  guardando.value = true
  errorMensaje.value = ''
  exitoMensaje.value = ''

  try {
    const userId = usuario.value?.id || usuario.value?.user_id || 0
    const headers = getAuthHeaders()

    let linkFinal = form.value.link.trim()
    if (!linkFinal.startsWith('http://') && !linkFinal.startsWith('https://')) {
      linkFinal = 'https://' + linkFinal
    }

    if (modoEdicion.value && idEditando.value) {
      // Actualizar enlace existente
      const res = await axios.put(`/api/portafolio/${idEditando.value}`, {
        usuario_id: userId,
        titulo: form.value.titulo.trim(),
        link: linkFinal,
        link_portafolio: linkFinal
      }, { headers })

      if (res.data && res.data.status === 'success') {
        exitoMensaje.value = 'Enlace de portafolio actualizado correctamente.'
        resetFormulario()
        await cargarPortafolios()
      } else {
        errorMensaje.value = res.data?.mensaje || 'Error al actualizar.'
      }
    } else {
      // Crear nuevo enlace
      const res = await axios.post('/api/portafolio', {
        usuario_id: userId,
        titulo: form.value.titulo.trim(),
        link: linkFinal,
        link_portafolio: linkFinal
      }, { headers })

      if (res.data && res.data.status === 'success') {
        exitoMensaje.value = 'Enlace de portafolio agregado correctamente.'
        resetFormulario()
        await cargarPortafolios()
      } else {
        errorMensaje.value = res.data?.mensaje || 'Error al agregar.'
      }
    }
  } catch (err) {
    logger.error('Error al guardar portafolio:', err)
    errorMensaje.value = err.response?.data?.mensaje || 'Error de conexión al guardar el portafolio.'
  } finally {
    guardando.value = false
    setTimeout(() => {
      exitoMensaje.value = ''
    }, 4000)
  }
}

const iniciarEdicion = (item) => {
  modoEdicion.value = true
  idEditando.value = item.id
  form.value = {
    titulo: item.titulo || '',
    link: item.link_portafolio || item.link || ''
  }
  intentoGuardar.value = false
  errorMensaje.value = ''
  exitoMensaje.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const eliminarPortafolio = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar este enlace de tu portafolio?')) {
    return
  }

  try {
    const userId = usuario.value?.id || usuario.value?.user_id || 0
    await axios.delete(`/api/portafolio/${id}`, {
      headers: getAuthHeaders(),
      params: { usuario_id: userId }
    })

    if (modoEdicion.value && idEditando.value === id) {
      resetFormulario()
    }
    await cargarPortafolios()
  } catch (err) {
    logger.error('Error al eliminar portafolio:', err)
    alert(err.response?.data?.mensaje || 'No fue posible eliminar el enlace.')
  }
}

onMounted(() => {
  cargarPortafolios()
})
</script>

<template>
  <div class="w-full max-w-7xl mx-auto px-2 sm:px-6 py-6 font-sans">
    
    <!-- Mensaje de éxito -->
    <div 
      v-if="exitoMensaje" 
      class="mb-6 px-4 py-3 rounded-lg bg-[#EAFBF1] border border-[#6CE9A6] text-[#027A48] text-sm font-medium flex items-center justify-between shadow-2xs transition-all"
    >
      <div class="flex items-center gap-2.5">
        <span class="w-5 h-5 rounded-full bg-[#12B76A] text-white flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </span>
        <span>{{ exitoMensaje }}</span>
      </div>
      <button @click="exitoMensaje = ''" class="text-[#027A48] hover:opacity-80 p-1 cursor-pointer">✕</button>
    </div>

    <!-- Mensaje de error -->
    <div 
      v-if="errorMensaje" 
      class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-xs transition-all"
    >
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ errorMensaje }}</span>
      </div>
      <button @click="errorMensaje = ''" class="text-rose-600 hover:text-rose-900 font-bold ml-4">✕</button>
    </div>

    <!-- CONTENEDOR PRINCIPAL: 2 COLUMNAS (ALTA FIDELIDAD CON CAPTURA FIGMA - IMAGEN 2) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start min-w-0">
      
      <!-- ========================================================================= -->
      <!-- COLUMNA IZQUIERDA: FORMULARIO "Añade un nuevo enlace de portafolio"      -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-5 min-w-0 lg:mt-[29px]">
        <div class="bg-[#F0F4FA] border border-[#7FA9E0] rounded-[16px] p-7 sm:p-9 shadow-xs min-w-0">
          
          <!-- Título elegante en fuente Lora/Serif centrado a dos líneas -->
          <h2 
            class="text-2xl sm:text-[26px] font-normal text-center text-black mb-8 leading-snug tracking-normal px-2 break-words"
            style="font-family: 'Lora', Georgia, serif;"
          >
            <template v-if="modoEdicion">
              Editar enlace de<br>portafolio
            </template>
            <template v-else>
              Añade un nuevo enlace de<br>portafolio
            </template>
          </h2>

          <form @submit.prevent="guardarPortafolio" novalidate class="space-y-5">
            <!-- Campo 1: Título* -->
            <div class="min-w-0">
              <label for="porta-titulo" class="block text-sm font-semibold text-gray-900 mb-1.5">
                Título<span class="text-red-500">*</span>
              </label>
              <input 
                id="porta-titulo"
                v-model="form.titulo"
                type="text"
                placeholder="Mi portafolio"
                :class="intentoGuardar && !form.titulo.trim() ? 'border-red-500 focus:border-red-600 bg-red-50/20' : 'border-[#7FA9E0] focus:border-blue-600'"
                class="w-full px-4 py-2.5 rounded-[8px] bg-white border text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-2xs transition-colors"
              />
              <p v-if="intentoGuardar && !form.titulo.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Este campo es obligatorio.</span>
              </p>
            </div>

            <!-- Campo 2: Link del portafolio* -->
            <div class="min-w-0">
              <label for="porta-link" class="block text-sm font-semibold text-gray-900 mb-1.5">
                Link del portafolio<span class="text-red-500">*</span>
              </label>
              <input 
                id="porta-link"
                v-model="form.link"
                type="text"
                placeholder="http://portafolio.com"
                :class="intentoGuardar && !form.link.trim() ? 'border-red-500 focus:border-red-600 bg-red-50/20' : 'border-[#7FA9E0] focus:border-blue-600'"
                class="w-full px-4 py-2.5 rounded-[8px] bg-white border text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-2xs transition-colors"
              />
              <p v-if="intentoGuardar && !form.link.trim()" class="flex items-center gap-1.5 text-xs text-red-500 font-medium mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Este campo es obligatorio.</span>
              </p>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-2 flex items-center gap-3">
              <button 
                type="submit"
                :disabled="guardando"
                class="bg-[#000B58] hover:bg-[#001064] text-white px-5 py-2.5 rounded-[8px] text-sm font-medium inline-flex items-center gap-2.5 transition-colors shadow-2xs cursor-pointer disabled:opacity-60"
              >
                <!-- Ícono de disquete (Floppy Disk) idéntico a la captura -->
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ guardando ? 'Guardando...' : (modoEdicion ? 'Actualizar portafolio' : 'Agregar portafolio') }}</span>
              </button>

              <button 
                v-if="modoEdicion"
                type="button"
                @click="resetFormulario"
                class="px-4 py-2.5 rounded-[8px] text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-200/60 transition-colors cursor-pointer"
              >
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- COLUMNA DERECHA: LISTADO DE ENLACES (ALTA FIDELIDAD CON CAPTURA FIGMA)   -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-7 min-w-0">
        
        <!-- CABECERA SUPERIOR: Título, Link, Acciones (Alineados fuera del recuadro) -->
        <div class="grid grid-cols-12 text-sm text-gray-500 font-normal px-5 mb-2 select-none">
          <div class="col-span-4 pl-1">Título</div>
          <div class="col-span-5 pl-1">Link</div>
          <div class="col-span-3 text-right pr-4">Acciones</div>
        </div>

        <!-- RECUADRO AZUL PRINCIPAL QUE CONTIENE LAS CARDS BLANCAS -->
        <div class="border border-[#7FA9E0] rounded-[16px] p-5 sm:p-6 bg-white min-h-[420px] flex flex-col justify-start min-w-0">
          
          <!-- Spinner de carga -->
          <div v-if="cargando" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-[#000B58] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-xs">Cargando portafolios...</span>
          </div>

          <!-- Estado vacío si no hay enlaces aún -->
          <div 
            v-else-if="portafolios.length === 0" 
            class="flex flex-col items-center justify-center py-20 text-center text-gray-400"
          >
            <p class="text-sm font-medium text-gray-500">Aún no tienes enlaces añadidos</p>
            <p class="text-xs text-gray-400 mt-1">Usa el formulario de la izquierda para agregar tu portafolio.</p>
          </div>

          <!-- Lista de enlaces (Cards blancas con borde azul #7FA9E0) -->
          <div v-else class="space-y-4">
            <div 
              v-for="item in portafolios" 
              :key="item.id"
              class="border border-[#7FA9E0] rounded-[12px] px-5 py-3.5 bg-white grid grid-cols-12 items-center min-w-0 shadow-2xs hover:shadow-xs transition-shadow"
              :class="modoEdicion && idEditando === item.id ? 'ring-2 ring-[#000B58]' : ''"
            >
              <!-- 1. Título -->
              <div class="col-span-4 min-w-0 pr-2">
                <span 
                  class="font-bold text-gray-900 text-sm truncate block break-words [overflow-wrap:anywhere]" 
                  :title="item.titulo"
                  style="overflow-wrap: anywhere; word-break: break-word;"
                >
                  {{ item.titulo || 'Mi portafolio' }}
                </span>
              </div>

              <!-- 2. Link -->
              <div class="col-span-5 min-w-0 pr-2">
                <a 
                  :href="(item.link_portafolio || item.link || '').startsWith('http') ? (item.link_portafolio || item.link) : 'https://' + (item.link_portafolio || item.link)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-sm text-gray-700 hover:text-blue-600 hover:underline truncate block [overflow-wrap:anywhere]"
                  :title="item.link_portafolio || item.link"
                  style="overflow-wrap: anywhere; word-break: break-all;"
                >
                  {{ item.link_portafolio || item.link }}
                </a>
              </div>

              <!-- 3. Acciones (Lápiz y Basurero Rojo de Figma) -->
              <div class="col-span-3 flex items-center justify-end gap-3 pr-1 shrink-0">
                
                <!-- Botón Editar (Lápiz) -->
                <button 
                  type="button"
                  @click="iniciarEdicion(item)"
                  title="Editar enlace"
                  class="text-gray-800 hover:text-[#000B58] p-1 rounded-sm transition-colors cursor-pointer shrink-0"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                  </svg>
                </button>

                <!-- Botón Eliminar (Basurero Rojo exactamente igual a la captura de Figma) -->
                <button 
                  type="button"
                  @click="eliminarPortafolio(item.id)"
                  title="Eliminar enlace"
                  class="hover:opacity-80 p-1 transition-opacity cursor-pointer shrink-0"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[19px]" viewBox="0 0 34 37" fill="none">
                    <path d="M12.3636 30.0625V13.1042C12.3636 12.8793 12.2912 12.6947 12.1463 12.5501C12.0014 12.4056 11.8163 12.3333 11.5909 12.3333H10.0455C9.82008 12.3333 9.63494 12.4056 9.49006 12.5501C9.34517 12.6947 9.27273 12.8793 9.27273 13.1042V30.0625C9.27273 30.2873 9.34517 30.472 9.49006 30.6165C9.63494 30.7611 9.82008 30.8333 10.0455 30.8333H11.5909C11.8163 30.8333 12.0014 30.7611 12.1463 30.6165C12.2912 30.472 12.3636 30.2873 12.3636 30.0625ZM18.5455 30.0625V13.1042C18.5455 12.8793 18.473 12.6947 18.3281 12.5501C18.1832 12.4056 17.9981 12.3333 17.7727 12.3333H16.2273C16.0019 12.3333 15.8168 12.4056 15.6719 12.5501C15.527 12.6947 15.4545 12.8793 15.4545 13.1042V30.0625C15.4545 30.2873 15.527 30.472 15.6719 30.6165C15.8168 30.7611 16.0019 30.8333 16.2273 30.8333H17.7727C17.9981 30.8333 18.1832 30.7611 18.3281 30.6165C18.473 30.472 18.5455 30.2873 18.5455 30.0625ZM24.7273 30.0625V13.1042C24.7273 12.8793 24.6548 12.6947 24.5099 12.5501C24.3651 12.4056 24.1799 12.3333 23.9545 12.3333H22.4091C22.1837 12.3333 21.9986 12.4056 21.8537 12.5501C21.7088 12.6947 21.6364 12.8793 21.6364 13.1042V30.0625C21.6364 30.2873 21.7088 30.472 21.8537 30.6165C21.9986 30.7611 22.1837 30.8333 22.4091 30.8333H23.9545C24.1799 30.8333 24.3651 30.7611 24.5099 30.6165C24.6548 30.472 24.7273 30.2873 24.7273 30.0625ZM11.5909 6.16667H22.4091L21.25 3.34831C21.1373 3.20378 21.0005 3.11545 20.8395 3.08333H13.1847C13.0237 3.11545 12.8868 3.20378 12.7741 3.34831L11.5909 6.16667ZM34 6.9375V8.47917C34 8.70399 33.9276 8.88867 33.7827 9.0332C33.6378 9.17773 33.4527 9.25 33.2273 9.25H30.9091V32.0859C30.9091 33.4188 30.5308 34.5711 29.7741 35.5426C29.0175 36.5142 28.108 37 27.0455 37H6.95455C5.89205 37 4.98248 36.5303 4.22585 35.5908C3.46922 34.6514 3.09091 33.5152 3.09091 32.1823V9.25H0.772727C0.547348 9.25 0.362216 9.17773 0.21733 9.0332C0.0724432 8.88867 0 8.70399 0 8.47917V6.9375C0 6.71267 0.0724432 6.52799 0.21733 6.38346C0.362216 6.23893 0.547348 6.16667 0.772727 6.16667H8.23438L9.92472 2.14388C10.1662 1.5497 10.6009 1.04384 11.2287 0.626302C11.8565 0.208767 12.4924 0 13.1364 0H20.8636C21.5076 0 22.1435 0.208767 22.7713 0.626302C23.3991 1.04384 23.8338 1.5497 24.0753 2.14388L25.7656 6.16667H33.2273C33.4527 6.16667 33.6378 6.23893 33.7827 6.38346C33.9276 6.52799 34 6.71267 34 6.9375Z" fill="#DF0000" fill-opacity="0.88"/>
                  </svg>
                </button>

              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

  </div>
</template>
