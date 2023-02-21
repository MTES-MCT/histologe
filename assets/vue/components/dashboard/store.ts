import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
  state: {
    user: {
      prenom: '',
      isAdmin: false,
      isResponsableTerritoire: false,
      isAdministrateurPartenaire: false
    },
    territories: new Array<HistoInterfaceSelectOption>(),
    filters: {
      territory: 'all'
    },
    newSignalements: {
      count: 0,
      percent: 0,
      link: undefined
    },
    signalements: {
      count: 0,
      percent: 0
    },
    closedSignalements: {
      count: 0,
      percent: 0
    },
    refusedSignalements: {
      count: 0,
      percent: 0
    },
    allSignalements: {
      link: undefined
    },
    newAffectations: {
      count: 0,
      link: undefined
    },
    userAffectations: {
      link: undefined
    },
    newSuivis: {
      count: 0,
      link: undefined
    },
    noSuivis: {
      count: 0,
      link: undefined
    },
    suivis: {
      countMoyen: 0,
      countByPartner: 0,
      countByUsager: 0
    },
    cloturesGlobales: {
      count: 0,
      link: undefined
    },
    cloturesPartenaires: {
      count: 0,
      link: undefined
    },
    users: {
      countActive: 0,
      percentActive: 0,
      countNotActive: 0,
      percentNotActive: 0
    },
    esaboraEvents: new Array<any>(),
    signalementsPerTerritoire: new Array<any>(),
    affectationsPartenaires: new Array<any>(),
    signalementsAcceptedNoSuivi: new Array<any>()
  },
  props: {
    ajaxurlSettings: '',
    ajaxurlKpi: '',
    ajaxurlPartners: '',
    ajaxurlSignalementsNosuivi: '',
    ajaxurlSignalementsPerTerritoire: '',
    ajaxurlConnectionsEsabora: ''
  }
}
