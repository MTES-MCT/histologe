import { reactive, computed } from 'vue'

interface FormData {
  [key: string]: any
}

// interface FormField {
//   type: string
//   label: string
//   slug: string
//   conditional?: {
//     show: string
//   }
//   customCss?: string
//   action?: string
//   validate?: {
//     required: boolean
//   }
// }

interface FormStore {
  data: FormData
  props: FormData
  validationErrors: FormData
  inputComponents: string[]
  updateData: (key: string, value: any) => void
  shouldShowField: (conditional: string) => boolean
}

const formStore: FormStore = reactive({
  data: {
    introduction_test: '',
    introduction_subscreen_test: '',
    adresse_logement_etage: '',
    adresse_logement_escalier: '',
    signalement_concerne: ''
  },
  props: {
    ajaxurl: '',
    ajaxurlQuestions: ''
  },
  inputComponents: [
    'SignalementFormTextfield',
    'SignalementFormOnlyChoice'
  ],
  validationErrors: {}, // Les erreurs de validation
  updateData (key: string, value: any) {
    formStore.data[key] = value
  },
  shouldShowField (conditional: string) {
    return computed(() => eval(conditional)).value
  }
})

export default formStore
