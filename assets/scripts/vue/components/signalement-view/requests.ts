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
  doPostRequest(
    ajaxUrl: string,
    payload: Record<string, any>,
    functionReturn: Function,
    extraArgs: any[] = []
  ) {
    axios
      .post(ajaxUrl, payload)
      .then(response => functionReturn(response, ...extraArgs))
      .catch(error => {
        if (error.response !== undefined) {
          functionReturn(error.response, ...extraArgs)
        } else {
          const customResponse = {
            status: 500,
            message: 'Une erreur est survenue lors de la requête.'
          }
          functionReturn(customResponse, ...extraArgs)
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
    this.doPostRequest(ajaxurlRemoveSignalement, { _token: csrfToken }, functionReturn)
  },
  getSettings (functionReturn: Function) {
    let url = store.props.ajaxurlSettings
    if (store.state.currentTerritoryId.length > 0) {
      url += `?territoryId=${store.state.currentTerritoryId}`
    }

    requests.doRequest(url, functionReturn)
  },
  saveSearch (payload: { name: string; params: any }, csrfToken: string, functionReturn: Function) {
    const ajaxUrlSaveSearch = decodeURIComponent(store.props.ajaxurlSaveSearch)
    this.doPostRequest(
      ajaxUrlSaveSearch,
      { _token: csrfToken, name: payload.name, params: payload.params },
      functionReturn
    )
  },
  deleteSearch (id: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlDeleteSearch = decodeURIComponent(store.props.ajaxurlDeleteSearch)
    ajaxurlDeleteSearch = ajaxurlDeleteSearch.replace('<id>', id)
    this.doPostRequest(ajaxurlDeleteSearch, { _token: csrfToken }, functionReturn, [id])
  },
  editSearch (id: string, newName: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlEditSearch = decodeURIComponent(store.props.ajaxurlEditSearch)
    ajaxurlEditSearch = ajaxurlEditSearch.replace('<id>', id)
    this.doPostRequest(ajaxurlEditSearch, { _token: csrfToken, name: newName }, functionReturn, [id, newName])
  }
}
