import axios from 'axios'
import { store } from './store'
import * as Sentry from '@sentry/browser'

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
  },
  // TODO factoriser avec un doPostRequest ?
  saveSearch (payload: { name: string; params: any }, csrfToken: string, functionReturn: Function) {
    const ajaxUrlSaveSearch = decodeURIComponent(store.props.ajaxurlSaveSearch)
    axios
      .post(ajaxUrlSaveSearch, {
        _token: csrfToken,
        name: payload.name,
        params: payload.params
      })
      .then(response => {
        functionReturn(response)
      })
      .catch(error => {
        if (error.response !== undefined) {
          functionReturn(error.response)
        } else {
          const customResponse = {
            status: 500,
            message: 'Une erreur est survenue lors de la sauvegarde de votre recherche.'
          }
          functionReturn(customResponse)
        }
      })
  },
  deleteSearch (id: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlDeleteSearch = decodeURIComponent(store.props.ajaxurlDeleteSearch)
    ajaxurlDeleteSearch = ajaxurlDeleteSearch.replace('<id>', id)
    axios
      .post(ajaxurlDeleteSearch, {
        _token: csrfToken
      })
      .then(response => {
        functionReturn(response, id)
      })
      .catch(error => {
        if (error.response !== undefined) {
          functionReturn(error.response)
        } else {
          const customResponse = {
            status: 500,
            message: 'Une erreur est survenue lors de la suppression de votre recherche.'
          }
          functionReturn(customResponse)
        }
      })
  },
  editSearch (id: string, newName: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlEditSearch = decodeURIComponent(store.props.ajaxurlEditSearch)
    ajaxurlEditSearch = ajaxurlEditSearch.replace('<id>', id)
    axios
      .post(ajaxurlEditSearch, {
        _token: csrfToken,
        name: newName
      })
      .then(response => {
        functionReturn(response, id, newName)
      })
      .catch(error => {
        if (error.response !== undefined) {
          functionReturn(error.response)
        } else {
          const customResponse = {
            status: 500,
            message: 'Une erreur est survenue lors de l\'édition de votre recherche.'
          }
          functionReturn(customResponse)
        }
      })
  },
}
