import axios from 'axios'
import { store } from './store'
import * as Sentry from '@sentry/browser'

export const requests = {
  doRequest (ajaxUrl: string, functionReturn: Function) {
    axios
      .get(ajaxUrl, { timeout: 15000 })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.error(error)
        Sentry.captureException(new Error(error))
        functionReturn('error')
      })
  },
  getSignalements (functionReturn: Function) {
    const ajaxUrl = store.props.ajaxurlSignalement
    this.doRequest(ajaxUrl, functionReturn)
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
      url += `?territory=${store.state.currentTerritoryId}`
    }

    requests.doRequest(url, functionReturn)
  }
}
