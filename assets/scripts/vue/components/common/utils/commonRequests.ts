import axios from 'axios'
import * as Sentry from '@sentry/browser'

export const commonRequests = {
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
  }
}
