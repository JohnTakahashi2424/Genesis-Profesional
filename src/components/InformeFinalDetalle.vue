<script setup>
import { computed } from 'vue'

const props = defineProps({
  informe: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['volver'])

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
  return []
})

// Formatear montos
const formatearMonto = (valor) => {
  const num = Number(valor)
  if (isNaN(num)) return '0'
  return num % 1 === 0 ? num.toString() : num.toFixed(2)
}

// Formatear tamaño de archivo
const formatearTamano = (bytes) => {
  if (!bytes) return ''
  if (typeof bytes === 'string' && bytes.includes('MB')) return bytes
  const b = Number(bytes)
  if (isNaN(b)) return ''
  if (b >= 1048576) {
    return (b / 1048576).toFixed(2) + ' MB'
  }
  return (b / 1024).toFixed(0) + ' KB'
}
</script>

<template>
  <div class="space-y-6 pb-16">
    
    <!-- ENLACE SUPERIOR: ← Reportes/ Informe final -->
    <div class="flex items-center justify-between max-w-5xl mx-auto">
      <button 
        @click="emit('volver')"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-[#000B58] transition cursor-pointer"
      >
        <span class="text-base font-bold">←</span>
        <span>Reportes/ Informe final</span>
      </button>

      <button 
        @click="emit('volver')"
        class="px-4 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold cursor-pointer transition shadow-2xs"
      >
        Volver al listado
      </button>
    </div>

    <!-- CONTENEDOR PRINCIPAL BLANCO CON BORDE AZUL CLARO (CALCADO A LAS CAPTURAS) -->
    <div class="relative bg-white border border-[#3b82f6] rounded-2xl p-6 sm:p-10 shadow-xs max-w-5xl mx-auto">
      
      <!-- BADGE DE ESTADO EN LA ESQUINA SUPERIOR DERECHA -->
      <div class="absolute top-6 right-6 sm:top-10 sm:right-10">
        <span 
          v-if="informe.estado === 'En revisión' || informe.estado === 'en_espera' || informe.estado === 'Enviado'"
          class="select-none"
          style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: var(--color-acento, #FFC071); align-items: center; justify-content: center; gap: 8px;"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 16 16" fill="none" class="shrink-0">
                  <circle cx="8" cy="8" r="6.4" stroke="rgba(0, 0, 0, 0.66)" stroke-width="1.6"/>
                  <path d="M4.8 8.0L6.9 10.1L11.2 5.8" stroke="black" stroke-opacity="0.6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
          <span class="text-black font-bold text-[13px] leading-none">En revisión</span>
        </span>

        <span 
          v-else-if="informe.estado === 'Aprobado' || informe.estado === 'aprobado'"
          class="select-none"
          style="display: flex; width: 120px; height: 26px; border-radius: 8px; background: #00B633; align-items: center; justify-content: center; gap: 8px;"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 16 16" fill="none" class="shrink-0">
                  <circle cx="8" cy="8" r="6.4" stroke="white" stroke-width="1.6"/>
                  <path d="M4.8 8.0L6.9 10.1L11.2 5.8" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
          <span class="text-white font-bold text-[13px] leading-none">Aprobado</span>
        </span>

        <span 
          v-else
          class="select-none text-xs font-semibold text-slate-600 px-3 py-1 bg-slate-100 rounded-lg"
        >
          {{ informe.estado || 'Enviado' }}
        </span>
      </div>

      <!-- ENCABEZADO INSTITUCIONAL UGB -->
      <div class="flex flex-col items-center justify-center text-center mb-8 pr-16 sm:pr-0">
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

      <!-- CAMPOS DE INFORMACIÓN PRINCIPALES -->
      <div class="space-y-6">
        
        <!-- FILA 1: Nombre del pasante e Institución (2 Columnas) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          
          <!-- Nombre del pasante -->
          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-1.5">
              Nombre del pasante
            </label>
            <div class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-gray-900 text-sm font-medium">
              {{ informe.nombre_pasante || '' }}
            </div>
          </div>

          <!-- Institución dónde se realizó la pasantía -->
          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-1.5">
              Institución dónde se realizó la pasantía
            </label>
            <div class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-gray-900 text-sm font-medium">
              {{ informe.institucion || '' }}
            </div>
          </div>
        </div>

        <!-- SECCIÓN: Presentación -->
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-1.5">
            Presentación
          </label>
          <div class="w-full p-4 rounded-lg border border-slate-200 bg-slate-50/70 text-gray-800 text-sm whitespace-pre-line leading-relaxed min-h-[90px]">
            {{ informe.presentacion || 'Sin presentación registrada.' }}
          </div>
        </div>

        <!-- SECCIÓN: Generalidades -->
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-1.5">
            Generalidades
          </label>
          <div class="w-full p-4 rounded-lg border border-slate-200 bg-slate-50/70 text-gray-800 text-sm whitespace-pre-line leading-relaxed min-h-[90px]">
            {{ informe.generalidades || 'Sin generalidades registradas.' }}
          </div>
        </div>

        <!-- SECCIÓN: Procedimiento para el proceso de la pasantía -->
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-1.5">
            Procedimiento para el proceso de la pasantía
          </label>
          <div class="w-full p-4 rounded-lg border border-slate-200 bg-slate-50/70 text-gray-800 text-sm whitespace-pre-line leading-relaxed min-h-[90px]">
            {{ informe.procedimiento_pasantia || 'Sin procedimiento registrado.' }}
          </div>
        </div>

        <!-- SECCIÓN: Descripción de la empresa CASS -->
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-1.5">
            Descripción de la empresa {{ informe.institucion ? informe.institucion : 'CASS' }}
          </label>
          <div class="w-full p-4 rounded-lg border border-slate-200 bg-slate-50/70 text-gray-800 text-sm whitespace-pre-line leading-relaxed min-h-[90px]">
            {{ informe.descripcion_empresa || 'Sin descripción registrada.' }}
          </div>
        </div>

        <!-- SECCIÓN: Descripción de la pasantía -->
        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-1.5">
            Descripción de la pasantía
          </label>
          <div class="w-full p-4 rounded-lg border border-slate-200 bg-slate-50/70 text-gray-800 text-sm whitespace-pre-line leading-relaxed min-h-[90px]">
            {{ informe.descripcion_pasantia || 'Sin descripción de la pasantía registrada.' }}
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SECCIÓN REPORTES MENSUALES ADJUNTOS                                    -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <h3 class="text-sm font-semibold text-gray-900 mb-3">
            Reportes mensuales adjuntos
          </h3>

          <!-- Encabezado de la tabla -->
          <div class="border border-[#93c5fd] rounded-xl px-5 py-2.5 mb-3 bg-white text-xs text-gray-600 font-medium grid grid-cols-12 gap-4 text-center">
            <div class="col-span-3 text-left pl-2">Mes del reporte</div>
            <div class="col-span-4">Periodo</div>
            <div class="col-span-3">Actividades desarrolladas</div>
            <div class="col-span-2">Estado</div>
          </div>

          <!-- Filas de reportes adjuntos -->
          <div v-if="informe.reportes && informe.reportes.length > 0" class="space-y-2.5">
            <div 
              v-for="rep in informe.reportes" 
              :key="rep.id"
              class="border border-[#93c5fd] rounded-xl px-5 py-3.5 bg-white grid grid-cols-12 gap-4 items-center shadow-2xs"
            >
              <!-- Mes con icono -->
              <div class="col-span-3 flex items-center gap-3 pl-2">
                <svg class="w-5 h-5 text-gray-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="16" rx="2" />
                  <circle cx="8.5" cy="9.5" r="1.5" />
                  <line x1="12.5" y1="8.5" x2="17.5" y2="8.5" />
                  <line x1="12.5" y1="11.5" x2="17.5" y2="11.5" />
                  <line x1="6.5" y1="15.5" x2="17.5" y2="15.5" />
                </svg>
                <span class="text-sm font-medium text-gray-900 capitalize">{{ rep.mes }}</span>
              </div>

              <!-- Periodo -->
              <div class="col-span-4 text-center text-xs text-gray-700 font-normal">
                {{ rep.periodo || (rep.fecha_inicio && rep.fecha_fin ? rep.fecha_inicio + ' – ' + rep.fecha_fin : 'Periodo mensual') }}
              </div>

              <!-- Conteo Actividades -->
              <div class="col-span-3 text-center text-sm font-bold text-gray-800">
                {{ rep.actividades_desarrolladas ?? (rep.actividades ? rep.actividades.length : 0) }}
              </div>

              <!-- Badge Estado -->
              <div class="col-span-2 flex justify-center">
                <span 
                  v-if="rep.estado === 'Aprobado' || rep.estado === 'aprobado'"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold text-white bg-[#00B633]"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  Aprobado
                </span>
                <span 
                  v-else
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold text-black bg-[#FFC071]"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                  </svg>
                  {{ rep.estado || 'En revisión' }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-slate-500 italic p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
            No se adjuntaron reportes mensuales específicos.
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SECCIÓN: Anexos                                                       -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <h2 class="text-xl font-serif font-bold text-gray-900 mb-2">
            Anexos
          </h2>

          <div v-if="informe.anexos && informe.anexos.length > 0" class="space-y-3">
            <div 
              v-for="(archivo, idx) in informe.anexos" 
              :key="idx"
              class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 shadow-2xs"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center shrink-0 text-red-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate max-w-md">{{ archivo.nombre }}</p>
                  <p class="text-[11px] text-gray-500">{{ formatearTamano(archivo.tamano) }}</p>
                </div>
              </div>
              
              <a 
                v-if="archivo.url"
                :href="archivo.url" 
                target="_blank"
                download
                class="px-3.5 py-1.5 bg-[#000B58] hover:bg-[#000840] text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Descargar</span>
              </a>
            </div>
          </div>
          <div v-else class="text-xs text-slate-500 italic p-3 bg-slate-50 rounded-lg border border-slate-200 text-center">
            No se adjuntaron archivos o anexos complementarios.
          </div>
        </div>

        <!-- ===================================================================== -->
        <!-- SECCIÓN: Presupuesto (Tabla exacta a la captura)                     -->
        <!-- ===================================================================== -->
        <div class="pt-4">
          <h2 class="text-xl font-serif font-bold text-gray-900 mb-1">
            Presupuesto
          </h2>
          <p class="text-sm text-gray-700 mb-4">
            Presupuesto de enero a julio
          </p>

          <!-- TABLA DEL PRESUPUESTO -->
          <div class="border border-slate-300 rounded-xl overflow-hidden shadow-xs bg-white">
            <div class="overflow-x-auto">
              <table class="w-full text-center border-collapse">
                <!-- Cabecera Azul Oscuro Exacta -->
                <thead>
                  <tr class="bg-[#000B58] text-white text-xs font-medium">
                    <th class="py-3 px-4 text-left font-semibold">Gasto</th>
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
                <tbody class="divide-y divide-slate-200 text-sm">
                  <tr 
                    v-for="item in presupuestoItems" 
                    :key="item.id"
                    class="hover:bg-slate-50/60 transition"
                  >
                    <!-- Nombre del Gasto -->
                    <td class="py-3.5 px-4 text-left font-serif font-bold text-gray-900 text-base">
                      {{ item.gasto }}
                    </td>

                    <!-- Montos por Columna/Mes -->
                    <td 
                      v-for="col in presupuestoColumnas" 
                      :key="col"
                      class="py-3.5 px-2 text-center text-slate-700 font-medium"
                    >
                      ${{ formatearMonto(item.montos?.[col] ?? 0) }}
                    </td>

                    <!-- Total de la Fila -->
                    <td class="py-3.5 px-4 text-center font-semibold text-gray-900">
                      ${{ item.total ?? '0.00' }}
                    </td>
                  </tr>

                  <!-- Fila de Total General -->
                  <tr class="bg-slate-50/80 font-bold border-t-2 border-slate-300">
                    <td class="py-4 px-4 text-left font-serif font-bold text-gray-900 text-base">
                      Total
                    </td>
                    <td :colspan="presupuestoColumnas.length"></td>
                    <td class="py-4 px-4 text-center">
                      <span class="inline-block px-4 py-1.5 rounded-lg border border-slate-300 bg-white text-gray-900 font-bold text-sm shadow-2xs">
                        ${{ informe.presupuesto_total || '0.00' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <!-- BOTONES INFERIORES -->
      <div class="flex justify-end gap-3 pt-8 border-t border-slate-200 mt-8">
        <button 
          @click="emit('volver')"
          class="px-6 py-2.5 bg-[#000B58] hover:bg-[#000840] text-white rounded-lg text-sm font-semibold transition cursor-pointer shadow-xs"
        >
          Volver a reportes
        </button>
      </div>

    </div>

  </div>
</template>

