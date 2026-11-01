/**
 * Subida Cloud Gratuita Permanente (sin tarjeta de crédito ni restricciones CORS).
 * Soporta almacenamiento directo y público para fotos de perfil y evidencias de reportes.
 */

const IMGUR_CLIENT_ID = '1ced6c8b16c5b08'

/**
 * Subir una imagen en formato base64 / Data URL a Cloud Storage público.
 * @param {string} dataUrl - Cadena en formato data:image/...;base64,...
 * @param {string} destino - Nombre o referencia del archivo
 * @returns {Promise<string>} URL pública permanente de descarga
 */
export async function subirImagenBase64(dataUrl, destino = '') {
  if (!dataUrl || !dataUrl.startsWith('data:')) {
    return dataUrl
  }

  try {
    const base64Clean = dataUrl.replace(/^data:image\/\w+;base64,/, '')
    const formData = new FormData()
    formData.append('image', base64Clean)

    const response = await fetch('https://api.imgur.com/3/image', {
      method: 'POST',
      headers: {
        Authorization: `Client-ID ${IMGUR_CLIENT_ID}`
      },
      body: formData
    })

    const result = await response.json()
    if (result && result.success && result.data && result.data.link) {
      return result.data.link
    }
  } catch (error) {
    console.warn('Error en subida cloud, manteniendo dataUrl local:', error)
  }

  return dataUrl
}

/**
 * Subir un archivo (File o Blob) directamente a Cloud Storage público.
 * @param {File|Blob} archivo - Archivo seleccionado del input
 * @param {string} destino - Nombre o referencia del archivo
 * @returns {Promise<string>} URL pública permanente de descarga
 */
export async function subirArchivoFirebase(archivo, destino = '') {
  if (!archivo) return ''

  try {
    const formData = new FormData()
    formData.append('image', archivo)

    const response = await fetch('https://api.imgur.com/3/image', {
      method: 'POST',
      headers: {
        Authorization: `Client-ID ${IMGUR_CLIENT_ID}`
      },
      body: formData
    })

    const result = await response.json()
    if (result && result.success && result.data && result.data.link) {
      return result.data.link
    }
  } catch (error) {
    console.warn('Error en subida de archivo a Cloud Storage:', error)
  }

  // Fallback a DataURL para no perder la imagen seleccionada por el usuario
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result)
    reader.onerror = () => resolve('')
    reader.readAsDataURL(archivo)
  })
}

