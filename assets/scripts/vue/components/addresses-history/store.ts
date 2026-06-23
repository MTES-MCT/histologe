import { QueryParameter } from '../common/interfaces/queryParameter'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    /*
    signalements: {
      filters: Object,
      list: new Array<Object>(),
      pagination: {
        current_page: 1,
        total_pages: 1,
        total_items: 0,
        per_page: 10,
      },
      zoneAreas: new Array<string>(),
    },
    */
    input: {
      order: 'reference-DESC',
      queryParameters: [] as QueryParameter[],
      filters: {
        territoire: undefined,
        adresse: undefined,
        communes: new Array<string>(),
        bailleur_syndic: undefined,
        zones: new Array<string>(),
        natureParc: undefined,
        dossiersMultiples: undefined,
        typesArretes: new Array<string>(),
      }
    },
    user: {
      isAdmin: false,
      isResponsableTerritoire: false,
      isAdministrateurPartenaire: false,
      isAgent: false,
      isMultiTerritoire: false,
      partnerIds: new Array<string>()
    },
    territories: new Array<HistoInterfaceSelectOption>(),
    communes: new Array<string>(),
    bailleursAndSyndic: new Array<HistoInterfaceSelectOption>(),
    zones: new Array<HistoInterfaceSelectOption>(),
    currentTerritoryId: '',
    currentCommunes: '',
    viewMode: 'card',
    loadingList: true,
    hasErrorLoading: false,
    natureParcList: [
      { Id: 'privee', Text: 'Parc privé' },
      { Id: 'public', Text: 'Parc public' },
      { Id: 'non_renseigne', Text: 'Parc Non renseigné' }
    ],
    dossiersMultiplesList: [
      { Id: '', Text: 'Tout' },
      { Id: 'oui', Text: 'Oui' },
      { Id: 'non', Text: 'Non' },
    ],
    typesArretes: [
      { Id: 'bla', Text: 'Bla bla bla' },
    ],
    filtersApplyKey: 0
  },
  props: {
    ajaxurlSignalement: '',
    baseAjaxUrlSignalement: '',
    ajaxurlExportCsv: '',
    ajaxurlSettings: '',
    platformName: '',
    token: ''
  }
}
