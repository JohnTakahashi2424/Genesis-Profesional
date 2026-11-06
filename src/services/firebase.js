import { logger } from '../utils/logger'

/**
 * Almacenamiento y Persistencia de Medios (Opción 1: Base64 en Base de Datos PostgreSQL).
 * Elimina la dependencia de servicios de terceros como Imgur para evitar cuotas horarias (HTTP 429)
 * y garantiza que las fotos y evidencias persistan permanentemente ante reinicios de Render (disco efímero).
 */

/**
 * Subir una imagen en formato base64 / Data URL para almacenamiento persistente.
 * Retorna la cadena DataURL directamente para almacenamiento en PostgreSQL.
 * @param {string} dataUrl - Cadena en formato data:image/...;base64,...
 * @param {string} destino - Nombre o referencia del archivo (opcional)
 * @returns {Promise<string>} Data URL persistente en Base de Datos
 */
export async function subirImagenBase64(dataUrl, destino = '') {
  if (!dataUrl) return ''
  // Si ya es un Data URL Base64 o una URL absoluta/relativa, retornarla directamente
  return dataUrl
}

/**
 * Convertir un archivo (File o Blob) a Data URL Base64 para almacenamiento en Base de Datos.
 * @param {File|Blob} archivo - Archivo seleccionado del input
 * @param {string} destino - Nombre o referencia del archivo (opcional)
 * @returns {Promise<string>} Data URL en Base64
 */
export async function subirArchivoFirebase(archivo, destino = '') {
  if (!archivo) return ''

  if (typeof archivo === 'string') {
    return archivo
  }

  // Convertir a DataURL Base64 de forma local e instantánea (0ms de latencia de red, 0 errores 429)
  return new Promise((resolve) => {
    try {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.onerror = () => {
        logger.warn('Error al leer archivo local a Base64')
        resolve('')
      }
      reader.readAsDataURL(archivo)
    } catch (err) {
      logger.warn('Excepción al convertir archivo a DataURL:', err)
      resolve('')
    }
  })
}
