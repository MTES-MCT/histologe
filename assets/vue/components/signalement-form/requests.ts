import axios from 'axios'
import formStore from './store'

export const requests = {
  doRequest (ajaxUrl: string, functionReturn: Function) {
    axios
      .get(ajaxUrl, { timeout: 15000 })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.log(error)
        functionReturn('error')
      })
  },

  initQuestions (functionReturn: Function) {
    const url = formStore.props.ajaxurlQuestions
    requests.doRequest(url, functionReturn)
  }
}
