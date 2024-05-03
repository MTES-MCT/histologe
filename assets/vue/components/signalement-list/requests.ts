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
        console.error(error)
        Sentry.captureException(new Error(error))
        functionReturn('error')
      })
  },
  getSettings (functionReturn: Function) {
    const url = store.props.ajaxurlSettings
    requests.doRequest(url, functionReturn)
  }
}