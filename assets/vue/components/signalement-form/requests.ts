import axios from 'axios'
import formStore from './store'

export const requests = {
  // TODO : voir avec Emilien pour ne plus gérer via des callbacks mais faire de l'async pour rendre le code plus lisible
  doRequestGet (ajaxUrl: string, functionReturn: Function) {
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
  doRequestPost (ajaxUrl: string, data: any, functionReturn: Function) {
    axios
      .post(ajaxUrl, data, { timeout: 15000 })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.log(error)
        functionReturn('error')
      })
  },
  doRequestPut (ajaxUrl: string, data: any, functionReturn: Function) {
    axios
      .put(ajaxUrl, data, { timeout: 15000 })
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
    requests.doRequestGet(url, functionReturn)
  },

  initQuestionsProfil (functionReturn: Function) {
    const url = (formStore.props.ajaxurlQuestions as string) + (formStore.data.profil as string)
    requests.doRequestGet(url, functionReturn)
  },

  saveSignalementDraft (functionReturn: Function) {
    if (formStore.data.uuidSignalementDraft !== '') {
      // TODO : il y a sûrement plus élégant à faire pour construire l'url (cf controlleur et twig)
      const url = formStore.props.ajaxurlPutSignalementDraft.replace('uuid', formStore.data.uuidSignalementDraft)
      requests.doRequestPut(url, formStore.data, functionReturn)
    } else if (formStore.data.adresse_logement_adresse !== undefined &&
          (formStore.data.vos_coordonnees_occupant_email !== undefined ||
            formStore.data.vos_coordonnees_tiers_email !== undefined)
    ) { // TODO : vérifier la condition (notamment en fonction des profils)
      const url = formStore.props.ajaxurlPostSignalementDraft
      requests.doRequestPost(url, formStore.data, functionReturn)
    } else {
      // TODO : que renvoyer ?
      functionReturn('no need to save')
    }
  },

  validateAddress (valueAdress: string, functionReturn: Function) {
    const url = (formStore.props.urlApiAdress as string) + valueAdress
    requests.doRequestGet(url, functionReturn)
  }
}
