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
    } else if ((formStore.currentScreen != null) && formStore.currentScreen.slug !== 'adresse_logement' && formStore.currentScreen.slug !== 'signalement_concerne') {
      const url = formStore.props.ajaxurlPostSignalementDraft
      requests.doRequestPost(url, formStore.data, functionReturn, undefined)
    } else {
      functionReturn(undefined)
    }
  },

  checkIfAlreadyExists (functionReturn: Function) {
    const url = formStore.props.ajaxurlCheckSignalementOrDraftAlreadyExists
    if ((formStore.currentScreen != null) && formStore.currentScreen.slug !== 'adresse_logement' && formStore.currentScreen.slug !== 'signalement_concerne') {
      requests.doRequestPost(url, formStore.data, functionReturn, undefined)
    } else {
      functionReturn(undefined)
    }
  },

  sendMailContinueFromDraft (functionReturn: Function) {
    const url = formStore.props.ajaxurlSendMailContinueFromDraft
    requests.doRequestPost(url, formStore.data, functionReturn, undefined)
  },

  sendMailGetLienSuivi (uuid: any, functionReturn: Function) {
    const url = (formStore.props.ajaxurlSendMailGetLienSuivi.replace('uuid', uuid) as string) + '?profil=' + (formStore.data.profil as string)
    requests.doRequestPost(url, '', functionReturn, undefined)
  },

  archiveDraft (functionReturn: Function) {
    const url = formStore.props.ajaxurlArchiveDraft
    requests.doRequestPost(url, formStore.data, functionReturn, undefined)
  },

  validateAddress (valueAdress: string, functionReturn: Function) {
    const url = (formStore.props.urlApiAdress as string) + valueAdress
    requests.doRequestGet(url, functionReturn)
  },
  getAutompleteSuggestions (url: string, functionReturn: Function) {
    requests.doRequestGet(url, functionReturn)
  },
  checkTerritory (postcode: string, citycode: string, functionReturn: Function) {
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
