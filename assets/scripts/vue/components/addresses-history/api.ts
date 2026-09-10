import axios, { type AxiosRequestConfig } from 'axios'
import * as Sentry from '@sentry/browser'
import type { AddressesResponse, SettingsResponse } from './types'

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
   * Export CSV des adresses
  async exportCsv(
    ajaxUrl: string,
    params: Record<string, unknown> = {}
  ): Promise<Blob> {
    try {
      const response = await axios.get(ajaxUrl, {
        ...this.baseConfig,
        params,
        responseType: 'blob',
      })
      return response.data
    } catch (error) {
      console.error('Error exporting CSV:', error)
      Sentry.captureException(error)
      throw error
    }
  }
   */
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
   * Export CSV avec téléchargement automatique
  const downloadCsv = async (
    params: Record<string, unknown> = {},
    filename = 'export-addresses.csv'
  ): Promise<void> => {
    try {
      const blob = await addressesHistoryApi.exportCsv(
        props.ajaxurlExportCsv,
        params
      )

      // Créer un lien de téléchargement temporaire
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Error downloading CSV:', error)
      throw error
    }
  }
   */

  return {
    fetchAddresses,
    fetchSettings,
    // downloadCsv,
  }
}
