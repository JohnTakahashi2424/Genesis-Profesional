<script setup>
import { authStorage } from '../utils/storage'

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
  <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 font-sans">
    
    <!-- Encabezado de Sección -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-700 shadow-2xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
              Portafolio Profesional
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
              Gestiona y comparte enlaces a tus proyectos, repositorios y perfiles web.
            </p>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
          <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
          {{ portafolios.length }} {{ portafolios.length === 1 ? 'enlace registrado' : 'enlaces registrados' }}
        </span>
      </div>
    </div>

    <!-- Mensaje de éxito -->
    <div 
      v-if="exitoMensaje" 
      class="mb-6 px-4 py-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-xs transition-all"
    >
      <div class="flex items-center gap-2.5">
        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </span>
        <span>{{ exitoMensaje }}</span>
      </div>
      <button @click="exitoMensaje = ''" class="text-emerald-700 hover:text-emerald-900 p-1 cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Mensaje de error -->
    <div 
      v-if="errorMensaje" 
      class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-xs transition-all"
    >
      <div class="flex items-center gap-2.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ errorMensaje }}</span>
      </div>
      <button @click="errorMensaje = ''" class="text-rose-600 hover:text-rose-900 font-bold ml-4">✕</button>
    </div>

    <!-- GRID DE 2 COLUMNAS -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start min-w-0">
      
      <!-- ========================================================================= -->
      <!-- COLUMNA IZQUIERDA: FORMULARIO                                            -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-5 min-w-0">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs min-w-0">
          
          <div class="flex items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
            <div>
              <h2 class="text-lg font-bold text-slate-900">
                {{ modoEdicion ? 'Editar enlace' : 'Añadir nuevo enlace' }}
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ modoEdicion ? 'Actualiza los datos del enlace seleccionado.' : 'Completa el título y la URL pública.' }}
              </p>
            </div>
            <span v-if="modoEdicion" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
              En edición
            </span>
          </div>

          <form @submit.prevent="guardarPortafolio" novalidate class="space-y-4">
            <!-- Campo 1: Título -->
            <div class="min-w-0">
              <label for="porta-titulo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Título del proyecto / enlace <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input 
                  id="porta-titulo"
                  v-model="form.titulo"
                  type="text"
                  placeholder="Ej. Mi Portafolio Web, Repositorio GitHub"
                  :class="intentoGuardar && !form.titulo.trim() ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-100 bg-rose-50/20' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100'"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white border text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4 transition-all shadow-2xs"
                />
              </div>
              <p v-if="intentoGuardar && !form.titulo.trim()" class="flex items-center gap-1.5 text-xs text-rose-500 font-medium mt-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>El título es obligatorio.</span>
              </p>
            </div>

            <!-- Campo 2: Link del portafolio -->
            <div class="min-w-0">
              <label for="porta-link" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                URL / Enlace del portafolio <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input 
                  id="porta-link"
                  v-model="form.link"
                  type="text"
                  placeholder="https://ejemplo.com/mi-portafolio"
                  :class="intentoGuardar && !form.link.trim() ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-100 bg-rose-50/20' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100'"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-white border text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-4 transition-all shadow-2xs font-mono text-xs sm:text-sm"
                />
              </div>
              <p v-if="intentoGuardar && !form.link.trim()" class="flex items-center gap-1.5 text-xs text-rose-500 font-medium mt-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>El link es obligatorio.</span>
              </p>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-3 flex items-center gap-3">
              <button 
                type="submit"
                :disabled="guardando"
                class="flex-1 bg-[#00135c] hover:bg-[#002080] text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-all shadow-xs hover:shadow-md cursor-pointer disabled:opacity-60"
              >
                <svg v-if="guardando" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ guardando ? 'Guardando...' : (modoEdicion ? 'Actualizar enlace' : 'Agregar enlace') }}</span>
              </button>

              <button 
                v-if="modoEdicion"
                type="button"
                @click="resetFormulario"
                class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-300 hover:bg-slate-50 transition-colors cursor-pointer"
              >
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- COLUMNA DERECHA: LISTADO DE ENLACES                                      -->
      <!-- ========================================================================= -->
      <div class="lg:col-span-7 min-w-0">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-7 shadow-xs min-h-[380px] flex flex-col min-w-0">
          
          <!-- Encabezado de la tarjeta derecha -->
          <div class="flex items-center justify-between gap-3 mb-4 pb-4 border-b border-slate-100">
            <div>
              <h2 class="text-lg font-bold text-slate-900">
                Enlaces Guardados
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Tus proyectos visibles para tutores y evaluadores.
              </p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
              {{ portafolios.length }} {{ portafolios.length === 1 ? 'ítem' : 'ítems' }}
            </span>
          </div>

          <!-- Cabecera de columnas para el listado -->
          <div v-if="portafolios.length > 0" class="bg-slate-50/80 rounded-xl px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 grid grid-cols-12 gap-3 mb-3 border border-slate-100 select-none">
            <div class="col-span-4 text-left">Título</div>
            <div class="col-span-5 text-left">Enlace</div>
            <div class="col-span-3 text-right">Acciones</div>
          </div>

          <!-- Spinner de carga -->
          <div v-if="cargando" class="flex-1 flex flex-col items-center justify-center py-16 text-slate-400">
            <svg class="animate-spin h-8 w-8 text-[#00135c] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-sm font-medium text-slate-600">Cargando portafolios...</span>
          </div>

          <!-- Estado vacío -->
          <div 
            v-else-if="portafolios.length === 0" 
            class="flex-1 flex flex-col items-center justify-center py-16 text-center"
          >
            <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-slate-400 mb-4 shadow-2xs">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Aún no tienes enlaces añadidos</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm">
              Utiliza el formulario de la izquierda para registrar tus repositorios o sitios de proyectos.
            </p>
          </div>

          <!-- Lista de enlaces -->
          <div v-else class="space-y-2.5 flex-1">
            <div 
              v-for="item in portafolios" 
              :key="item.id"
              class="border border-slate-200/90 hover:border-blue-300 rounded-xl px-4 py-3 bg-white hover:bg-slate-50/60 grid grid-cols-12 items-center gap-3 transition-all shadow-2xs group min-w-0"
              :class="modoEdicion && idEditando === item.id ? 'ring-2 ring-blue-500 border-blue-400 bg-blue-50/20' : ''"
            >
              <!-- 1. Título con icono -->
              <div class="col-span-4 min-w-0 flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </div>
                <span 
                  class="font-semibold text-slate-900 text-sm truncate block" 
                  :title="item.titulo"
                >
                  {{ item.titulo || 'Mi portafolio' }}
                </span>
              </div>

              <!-- 2. Link -->
              <div class="col-span-5 min-w-0">
                <a 
                  :href="(item.link_portafolio || item.link || '').startsWith('http') ? (item.link_portafolio || item.link) : 'https://' + (item.link_portafolio || item.link)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-xs sm:text-sm text-blue-600 hover:text-blue-800 font-mono hover:underline truncate inline-flex items-center gap-1.5 max-w-full"
                  :title="item.link_portafolio || item.link"
                >
                  <span class="truncate">{{ item.link_portafolio || item.link }}</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400 group-hover:text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>
              </div>

              <!-- 3. Acciones (Editar y Eliminar) -->
              <div class="col-span-3 flex items-center justify-end gap-1 shrink-0">
                <!-- Botón Editar -->
                <button 
                  type="button"
                  @click="iniciarEdicion(item)"
                  title="Editar enlace"
                  class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                  </svg>
                </button>

                <!-- Botón Eliminar -->
                <button 
                  type="button"
                  @click="eliminarPortafolio(item.id)"
                  title="Eliminar enlace"
                  class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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

