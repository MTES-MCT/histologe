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
//   css?: string
//   action?: string
// }

interface FormStore {
  data: FormData
  props: FormData
  updateData: (key: string, value: any) => void
  shouldShowField: (conditional: string) => boolean
}

const formStore: FormStore = reactive({
  data: {
    introduction_test: '',
    adresse_logement_etage: '',
    adresse_logement_escalier: ''
  },
  props: {
    ajaxurl: ''
  },
  updateData (key: string, value: any) {
    formStore.data[key] = value
  },
  shouldShowField (conditional: string) {
    return computed(() => eval(conditional)).value
  }
})

export default formStore
