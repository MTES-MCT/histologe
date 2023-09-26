import { reactive, computed } from 'vue'
import { ZoneComponents } from './interfaces/interfaceZoneComponents'
import { Component } from './interfaces/interfaceComponent'
import { PictureDescription } from './interfaces/interfacePictureDescription'

interface FormData {
  [key: string]: any
}

// TODO : compléter, centraliser et utiliser les interfaces
// interface FormField {
//   type: string
//   slug: string
//   label?: string
//   description?: string
//   conditional?: {
//     show: string
//   }
//   customCss?: string
//   action?: string
//   validate?: {
//     required: boolean
//   }
//   components: {
//     body: ({
//       type: string
//       label: string
//       slug: string
//       components?: undefined
//       action?: undefined
//       customCss?: undefined
//     })
//     footer?: undefined
//   }
// }

interface FormStore {
  data: FormData
  props: FormData
  screenData: any[]
  currentScreen: {
    slug: string
    screenCategory: string
    label: string
    description: string
    icon: PictureDescription
    desktopIllustration: PictureDescription
    components: ZoneComponents
    customCss: string
  } | null
  lastButtonClicked: string
  validationErrors: FormData
  inputComponents: string[]
  updateData: (key: string, value: any) => void
  shouldShowField: (conditional: string) => boolean
  preprocessScreen: (screenBodyComponents: any) => Component[]
}

const formStore: FormStore = reactive({
  data: {
    uuidSignalementDraft: ''
  },
  props: {
    ajaxurl: '',
    ajaxurlDictionary: '',
    ajaxurlQuestions: '',
    ajaxurlDesordres: '',
    ajaxurlPostSignalementDraft: '',
    ajaxurlPutSignalementDraft: '',
    ajaxurlHandleUpload: '',
    ajaxurlGetSignalementDraft: '',
    platformName: '',
    urlApiAdress: 'https://api-adresse.data.gouv.fr/search/?q='
  },
  screenData: [],
  currentScreen: null,
  lastButtonClicked: '',
  inputComponents: [
    'SignalementFormTextfield',
    'SignalementFormTextarea',
    'SignalementFormOnlyChoice',
    'SignalementFormRoomList',
    'SignalementFormAddress',
    'SignalementFormCheckbox',
    'SignalementFormCounter',
    'SignalementFormDate',
    'SignalementFormPhonefield',
    'SignalementFormUpload',
    'SignalementFormEmailfield'
  ],
  validationErrors: {}, // Les erreurs de validation
  updateData (key: string, value: any) {
    formStore.data[key] = value
  },
  isTerritoryOpenedOnStopPunaises (): boolean {
    console.log(formStore.data.adresse_logement_adresse_detail_insee)
    // TODO : trouver le meilleur moyen de relier histologe et stop-punaises
    return true
  },
  shouldShowField (conditional: string) {
    return computed(() => eval(conditional)).value
  },
  preprocessScreen (screenBodyComponents: any): Component[] {
    const repeatedComponents: Component[] = []
    screenBodyComponents.forEach((component: Component) => {
      if (component.repeat !== null && component.repeat !== undefined) {
        for (let i = 1; i <= eval(component.repeat.count); i++) {
          repeatedComponents.push(this.cloneComponentWithNumber(component, i))
        }
      } else {
        repeatedComponents.push(component)
      }
    })
    return repeatedComponents
  },
  replaceNumberInString (value: string, number: number): string {
    return value.replace('{{number}}', String(number))
  },
  cloneComponentWithNumber (component: any, number: number): any {
    const clonedComponent: any = {}

    for (const prop in component) {
      if (Object.prototype.hasOwnProperty.call(component, prop)) {
        if (prop !== 'repeat') {
          if (typeof component[prop] === 'string') {
            clonedComponent[prop] = this.replaceNumberInString(component[prop], number)
          } else if (Array.isArray(component[prop])) {
            clonedComponent[prop] = component[prop].map((item: any) => this.cloneComponentWithNumber(item, number))
          } else if (typeof component[prop] === 'object') {
            clonedComponent[prop] = this.cloneComponentWithNumber(component[prop], number)
          } else {
            clonedComponent[prop] = component[prop]
          }
        }
      }
    }

    return clonedComponent
  }
})

export default formStore
