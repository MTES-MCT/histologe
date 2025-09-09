import axios from 'axios'
import { store } from './store'
import * as Sentry from '@sentry/browser'

// Debounce timer pour éviter les appels trop rapides
let debounceTimer: number | null = null
const DEBOUNCE_DELAY = 300 // 300ms

export const requests = {
  doRequest (ajaxUrl: string, functionReturn: Function, options = {}) {
    const defaultOptions = { timeout: 15000 }

    axios
      .get(ajaxUrl, { ...defaultOptions, ...options })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        if (axios.isCancel(error)) {
          console.warn('Request cancelled', error.message)
        } else {
          console.error(error)
          Sentry.captureException(new Error(error))
          functionReturn('error')
        }
      })
  },
  getSignalements (functionReturn: Function, options = {}) {
    const ajaxUrl = store.props.ajaxurlSignalement
    this.doRequest(ajaxUrl, functionReturn, options)
  },
  getSignalementsDebounced (functionReturn: Function, options = {}) {
    // Annuler le timer précédent si il existe
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    
    // Créer un nouveau timer
    debounceTimer = window.setTimeout(() => {
      const ajaxUrl = store.props.ajaxurlSignalement
      this.doRequest(ajaxUrl, functionReturn, options)
      debounceTimer = null
    }, DEBOUNCE_DELAY)
  },
  deleteSignalement (uuid: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlRemoveSignalement = decodeURIComponent(store.props.ajaxurlRemoveSignalement)
    ajaxurlRemoveSignalement = ajaxurlRemoveSignalement.replace('<uuid>', uuid)

    axios
      .post(ajaxurlRemoveSignalement, { _token: csrfToken })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        Sentry.captureException(new Error(error))
        if (error.response !== undefined) {
          functionReturn(error.response)
        } else {
          const customResponse = {
            status: 500,
            message: 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.'
          }
          functionReturn(customResponse)
        }
      })
  },
  getSettings (functionReturn: Function) {
    let url = store.props.ajaxurlSettings
    if (store.state.currentTerritoryId.length > 0) {
      url += `?territoryId=${store.state.currentTerritoryId}`
    }

    requests.doRequest(url, functionReturn)
  }
}
