import axios from 'axios'
import formStore from './store'
import * as Sentry from '@sentry/browser'
import { AXIOS_TIMEOUT } from '../../../controllers/environment'

export const requests = {
  doRequestGet (ajaxUrl: string, functionReturn: Function) {
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
  },
  doRequestPost (ajaxUrl: string, data: any, functionReturn: Function, config: any) {
    if (config !== undefined) {
      config.timeout = AXIOS_TIMEOUT
    } else {
      config = { timeout: AXIOS_TIMEOUT }
    }
    axios
      .post(ajaxUrl, data, config)
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.error(error)
        Sentry.captureException(new Error(error))
        functionReturn(error)
      })
  },
  doRequestPostUpload (ajaxUrl: string, data: any, functionReturn: Function, config: any) {
    const axiosInstance = axios.create({
      timeout: 0 // TODO: va nécéssiter d'apporter quelques retouches sur l'UX
    })
    axiosInstance
        .post(ajaxUrl, data, config)
        .then(response => {
          const responseData = response.data
          functionReturn(responseData)
        })
        .catch(error => {
          console.error(error)
          Sentry.captureException(new Error('Something wrong happened with the upload.'))
          functionReturn(error)
        })
  },
  doRequestPut (ajaxUrl: string, data: any, functionReturn: Function) {
    axios
      .put(ajaxUrl, data, { timeout: AXIOS_TIMEOUT })
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

  initWithExistingData (functionReturn: Function) {
    const url = formStore.props.ajaxurlGetSignalementDraft
    requests.doRequestGet(url, functionReturn)
  },

  initDictionary (functionReturn: Function) {
    const url = (formStore.props.ajaxurlDictionary as string)
    requests.doRequestGet(url, functionReturn)
  },

  initQuestions (functionReturn: Function) {
    const url = (formStore.props.ajaxurlQuestions as string) + 'tous'
    requests.doRequestGet(url, functionReturn)
  },

  initQuestionsProfil (functionReturn: Function) {
    const url = (formStore.props.ajaxurlQuestions as string) + (formStore.data.profil as string)
    requests.doRequestGet(url, functionReturn)
  },

  initDesordresProfil (functionReturn: Function) {
    const url = (formStore.props.ajaxurlDesordres as string) + (formStore.data.profil as string)
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
      requests.doRequestPost(url, formStore.data, functionReturn, undefined)
    } else {
      // TODO : que renvoyer ?
      functionReturn(undefined)
    }
  },

  validateAddress (valueAdress: string, functionReturn: Function) {
    const url = (formStore.props.urlApiAdress as string) + valueAdress
    requests.doRequestGet(url, functionReturn)
  },

  checkTerritory(postcode: string, citycode: string, functionReturn: Function) {
    const url = (formStore.props.ajaxurlCheckTerritory as string) + '?cp=' + postcode + '&insee=' + citycode
    requests.doRequestGet(url, functionReturn)
  },

  uploadFile (formData: FormData, functionReturn: Function, functionProgress: Function) {
    const url = formStore.props.ajaxurlHandleUpload
    const config = {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: function (progressEvent: any) {
        const progress = Math.round((progressEvent.loaded / progressEvent.total) * 100)
        functionProgress(progress)
      }
    }
    requests.doRequestPostUpload(url, formData, functionReturn, config)
  }
}
