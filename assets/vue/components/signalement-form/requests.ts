import axios from 'axios'
import formStore from './store'

export const requests = {
  // TODO : voir avec Emilien pour ne plus gérer via des callbacks mais faire de l'async pour rendre le code plus lisible
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
    const url = (formStore.props.ajaxurlQuestions as string) + 'tous'
    requests.doRequest(url, functionReturn)
  },

  initQuestionsProfil (functionReturn: Function) {
    const url = (formStore.props.ajaxurlQuestions as string) + (formStore.data.profil as string)
    requests.doRequest(url, functionReturn)
  },

  validateAddress (valueAdress: string, functionReturn: Function) {
    const url = (formStore.props.urlApiAdress as string) + valueAdress
    requests.doRequest(url, functionReturn)
  }
}
