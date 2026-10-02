import axios from 'axios'
import { store } from './store'
import * as Sentry from '@sentry/browser'
import { AXIOS_TIMEOUT } from '../../../vanilla/controllers/environment'

export const requests = {
  /**
   * Filter front statistics
   */
  filter (functionReturn: Function) {
    const ajaxUrl = store.props.ajaxurl + '?territoire=' + store.state.filters.territoire

    axios
      .get(ajaxUrl, { timeout: AXIOS_TIMEOUT })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.error(error)
        Sentry.captureException(new Error(error))
        functionReturn('error')
      })
  }
}
