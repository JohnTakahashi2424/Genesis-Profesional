<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const emit = defineEmits(['volver'])

// Estado del usuario activo
const usuario = ref(JSON.parse(localStorage.getItem('genesis_usuario') || '{}'))

// Estado del formulario
const form = ref({
  titulo: '',
  link: ''
})

const modoEdicion = ref(false)
const idEditando = ref(null)
const cargando = ref(false)
const guardando = ref(false)
const errorMensaje = ref('')
const exitoMensaje = ref('')

// Lista de enlaces de portafolio
const portafolios = ref([])

const getAuthHeaders = () => {
  const token = localStorage.getItem('genesis_token')
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
    console.warn('Error al cargar enlaces de portafolio:', err)
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
              titulo: 'My portafolio',
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
}

const guardarPortafolio = async () => {
  if (!form.value.titulo.trim() || !form.value.link.trim()) {
    errorMensaje.value = 'Por favor, completa tanto el título como el enlace del portafolio.'
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
    console.error('Error al guardar portafolio:', err)
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
    titulo: item.titulo || 'My portafolio',
    link: item.link_portafolio || item.link || ''
  }
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
    console.error('Error al eliminar portafolio:', err)
    alert(err.response?.data?.mensaje || 'No fue posible eliminar el enlace.')
  }
}

onMounted(() => {
  cargarPortafolios()
})
</script>

<template>
  <div class="w-full max-w-7xl mx-auto px-2 sm:px-6 py-6 font-sans">
    
    <!-- Mensaje de éxito flotante/alerta -->
    <div 
      v-if="exitoMensaje" 
      class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-xs transition-all"
    >
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ exitoMensaje }}</span>
      </div>
      <button @click="exitoMensaje = ''" class="text-emerald-600 hover:text-emerald-900 font-bold ml-4">✕</button>
    </div>

    <!-- Mensaje de error flotante/alerta -->
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

    <!-- CONTENEDOR PRINCIPAL: 2 COLUMNAS (ALTA FIDELIDAD CON CAPTURA 1) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start min-w-0">
      
      <!-- ========================================================================= -->
      <!-- COLUMNA IZQUIERDA: FORMULARIO "Añade un nuevo enlace de portafolio"      -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-5 min-w-0">
        <div class="bg-[#F4F7FD] border border-[#7FA9E0] rounded-2xl p-7 sm:p-9 shadow-[2px_2px_0px_0px_rgba(0,11,88,0.06)] min-w-0">
          
          <!-- Título elegante en fuente Lora/Serif centrado -->
          <h2 
            class="text-xl sm:text-2xl font-bold text-center text-gray-900 mb-7 leading-snug break-words"
            style="font-family: 'Lora', Georgia, serif;"
          >
            {{ modoEdicion ? 'Editar enlace de portafolio' : 'Añade un nuevo enlace de portafolio' }}
          </h2>

          <form @submit.prevent="guardarPortafolio" class="space-y-5">
            <!-- Campo 1: Título* -->
            <div class="min-w-0">
              <label class="block text-sm font-semibold text-gray-900 mb-1.5">
                Título<span class="text-red-500">*</span>
              </label>
              <input 
                v-model="form.titulo"
                type="text"
                placeholder="My portafolio"
                required
                class="w-full px-4 py-2.5 rounded-[8px] bg-white border border-[#7FA9E0] text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-500 shadow-xs transition-colors"
              />
            </div>

            <!-- Campo 2: Link del portafolio* -->
            <div class="min-w-0">
              <label class="block text-sm font-semibold text-gray-900 mb-1.5">
                Link del portafolio<span class="text-red-500">*</span>
              </label>
              <input 
                v-model="form.link"
                type="text"
                placeholder="http://portafolio.com"
                required
                class="w-full px-4 py-2.5 rounded-[8px] bg-white border border-[#7FA9E0] text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-500 shadow-xs transition-colors"
              />
            </div>

            <!-- Botones de Acción -->
            <div class="pt-2 flex items-center gap-3">
              <button 
                type="submit"
                :disabled="guardando"
                class="bg-[#000B58] hover:bg-[#010C67] text-white px-5 py-2.5 rounded-[8px] text-sm font-medium flex items-center gap-2.5 transition-colors shadow-xs cursor-pointer disabled:opacity-60"
              >
                <!-- Ícono de disquete (Floppy Disk) de la captura -->
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
      <!-- COLUMNA DERECHA: LISTADO DE ENLACES DE PORTAFOLIO                         -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-7 min-w-0">
        
        <!-- Contenedor exterior con borde azul claro tipo maqueta -->
        <div class="bg-white border border-[#7FA9E0] rounded-2xl p-6 sm:p-8 min-h-[360px] shadow-[2px_2px_0px_0px_rgba(0,11,88,0.06)] min-w-0">
          
          <!-- Encabezados de Columna (Título / Link / Acciones) -->
          <div class="grid grid-cols-12 text-sm font-medium text-gray-500 mb-3 px-4 select-none">
            <div class="col-span-4 text-left font-medium">Título</div>
            <div class="col-span-5 text-left font-medium">Link</div>
            <div class="col-span-3 text-right font-medium pr-2">Acciones</div>
          </div>

          <!-- Spinner de carga -->
          <div v-if="cargando" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-[#000B58] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-xs">Cargando portafolios...</span>
          </div>

          <!-- Estado vacío si no hay enlaces aún -->
          <div 
            v-else-if="portafolios.length === 0" 
            class="flex flex-col items-center justify-center py-16 text-center text-gray-400"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <p class="text-sm font-medium text-gray-600">Aún no tienes enlaces añadidos</p>
            <p class="text-xs text-gray-400 mt-1">Usa el formulario de la izquierda para agregar tu primer enlace.</p>
          </div>

          <!-- Lista de enlaces en píldoras redondeadas con borde azul claro -->
          <div v-else class="space-y-3">
            <div 
              v-for="item in portafolios" 
              :key="item.id"
              class="border border-[#7FA9E0] rounded-[10px] px-4 py-3 bg-white grid grid-cols-12 items-center gap-3 transition-all hover:border-blue-500 shadow-2xs min-w-0"
              :class="modoEdicion && idEditando === item.id ? 'ring-2 ring-[#000B58] bg-blue-50/20' : ''"
            >
              <!-- 1. Título -->
              <div class="col-span-4 min-w-0 pr-2">
                <span 
                  class="font-bold text-gray-900 text-sm truncate block break-words [overflow-wrap:anywhere]" 
                  :title="item.titulo"
                  style="overflow-wrap: anywhere; word-break: break-word;"
                >
                  {{ item.titulo || 'My portafolio' }}
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

              <!-- 3. Acciones (Lápiz y Basurero Rojo) -->
              <div class="col-span-3 flex items-center justify-end gap-3 pr-1 shrink-0">
                
                <!-- Botón Editar (Lápiz) -->
                <button 
                  type="button"
                  @click="iniciarEdicion(item)"
                  title="Editar enlace"
                  class="text-gray-800 hover:text-[#000B58] p-1 rounded-sm transition-colors cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                  </svg>
                </button>

                <!-- Botón Eliminar (Basurero Rojo exactamente igual a la captura) -->
                <button 
                  type="button"
                  @click="eliminarPortafolio(item.id)"
                  title="Eliminar enlace"
                  class="text-red-500 hover:text-red-700 p-1 rounded-sm transition-colors cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#EF4444">
                    <path d="M6 7H18V20C18 20.5304 17.7893 21.0391 17.4142 21.4142C17.0391 21.7893 16.5304 22 16 22H8C7.46957 22 6.96086 21.7893 6.58579 21.4142C6.21071 21.0391 6 20.5304 6 20V7Z" />
                    <path d="M4 4H20V6H4V4Z" />
                    <path d="M9 2H15V4H9V2Z" />
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

