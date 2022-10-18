import axios from 'axios'
import { store } from './store'

export const requests = {
  /**
   * Filter front statistics
   */
  filter (functionReturn: Function) {
    const ajaxUrl = store.props.ajaxurl + '?territoire=' + store.state.filters.territoire

    axios
      .get(ajaxUrl, { timeout: 10000 })
      .then(response => {
        const responseData = response.data
        console.log('then')
        console.log(responseData)
        functionReturn(responseData)
      })
      .catch(error => {
        console.log('error.toJSON')
        console.log(error)
        functionReturn('error')
      })
  }
}
