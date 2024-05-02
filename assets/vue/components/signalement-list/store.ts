import { QueryParameter } from './interfaces/queryParameter'

export const store = {
  state: {
    signalements: {
      filters: Object,
      list: new Array<Object>(),
      pagination: Object
    },
    input: {
      order: 'reference-DESC',
      queryParameters: [] as QueryParameter[]
    },
    user: {
      isAdmin: false,
      isResponsableTerritoire: false,
      isAdministrateurPartenaire: false,
      canSeeStatusAffectation: false,
      canDeleteSignalement: false,
      canSeeNonDecenceEnergetique: false
    }
  },
  props: {
    ajaxurlSignalement: '',
    ajaxurlRemoveSignalement: '',
    ajaxurlExportCsv: '',
    ajaxurlSettings: ''
  }
}
