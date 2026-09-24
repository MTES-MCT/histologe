import axios, { type AxiosRequestConfig } from 'axios'
import * as Sentry from '@sentry/browser'
import type { AddressesResponse, SettingsResponse } from './types'
import { store } from './composables/useAddressesHistoryStore'

/**
 * Configuration API pour les requêtes addresses-history
 */
class AddressesHistoryApi {
  private baseConfig: AxiosRequestConfig = {
    timeout: 15000,
    headers: {
      'Content-Type': 'application/json',
    },
  }

  /**
   * Récupère la liste des adresses avec les filtres appliqués
   */
  async getAddresses(
    ajaxUrl: string,
    options: AxiosRequestConfig = {}
  ): Promise<AddressesResponse> {
    try {
      const response = await axios.get<AddressesResponse>(ajaxUrl, {
        ...this.baseConfig,
        ...options,
      })
      return response.data
    } catch (error) {
      if (axios.isCancel(error)) {
        console.warn('Request cancelled:', error.message)
        throw new Error('Request cancelled')
      }
      console.error('Error fetching addresses:', error)
      Sentry.captureException(error)
      throw error
    }
  }

  /**
   * Récupère les paramètres et options pour les filtres
   */
  async getSettings(
    ajaxUrl: string,
    territoryId?: string
  ): Promise<SettingsResponse> {
    try {
      const url = territoryId
        ? `${ajaxUrl}?context=addresses-history&territoryId=${encodeURIComponent(territoryId)}`
        : `${ajaxUrl}?context=addresses-history`

      const response = await axios.get<SettingsResponse>(url, this.baseConfig)
      return response.data
    } catch (error) {
      console.error('Error fetching settings:', error)
      Sentry.captureException(error)
      throw error
    }
  }

  /**
   * Export (CSV ou XLSX) des adresses correspondant aux filtres actuellement appliqués
   */
  async exportAddresses(
    ajaxUrl: string,
    format: string
  ): Promise<{ blob: Blob; filename: string }> {
    try {
      const filters = store.state.input.filters as Record<string, unknown>
      const params: Record<string, unknown> = { format }
      for (const [key, value] of Object.entries(filters)) {
        if (Array.isArray(value)) {
          if (value.length > 0) {
            params[key] = value
          }
        } else if (value !== undefined && value !== null && value !== '') {
          params[key] = value
        }
      }

      const response = await axios.get(ajaxUrl, {
        ...this.baseConfig,
        params,
        responseType: 'blob',
      })

      return {
        blob: response.data,
        filename: this.getFilenameFromResponse(response.headers['content-disposition'], format)
      }
    } catch (error) {
      console.error('Error exporting addresses:', error)
      Sentry.captureException(error)
      throw error
    }
  }

  /**
   * Extrait le nom de fichier de l'en-tête Content-Disposition renvoyé par le serveur
   */
  private getFilenameFromResponse(contentDisposition: string | undefined, format: string): string {
    const match = contentDisposition?.match(/filename="?([^";]+)"?/)
    return match?.[1] || `export-adresses.${format}`
  }
}

// Instance singleton de l'API
export const addressesHistoryApi = new AddressesHistoryApi()

/**
 * Composable Vue 3 pour gérer les requêtes API addresses-history
 */
export function useAddressesHistoryApi(props: {
  ajaxurlAddresses: string
  ajaxurlSettings: string
  ajaxurlExportCsv: string
  token?: string
}) {
  /**
   * Récupère les adresses avec gestion d'état intégrée
   */
  const fetchAddresses = async (
    options: AxiosRequestConfig = {}
  ): Promise<AddressesResponse> => {
    return await addressesHistoryApi.getAddresses(
      props.ajaxurlAddresses,
      options
    )
  }

  /**
   * Récupère les settings avec territoire optionnel
   */
  const fetchSettings = async (
    territoryId?: string
  ): Promise<SettingsResponse> => {
    return await addressesHistoryApi.getSettings(
      props.ajaxurlSettings,
      territoryId
    )
  }

  /**
   * Exporte la liste des adresses (au format demandé) et déclenche son téléchargement
   */
  const downloadList = async (format: 'csv' | 'xlsx'): Promise<void> => {
    const { blob, filename } = await addressesHistoryApi.exportAddresses(
      props.ajaxurlExportCsv,
      format
    )

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  }

  return {
    fetchAddresses,
    fetchSettings,
    downloadList,
  }
}
