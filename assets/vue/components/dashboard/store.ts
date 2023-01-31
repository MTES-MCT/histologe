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
      link: '#new-signalements'
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
      link: '#all-signalements'
    },
    newAffectations: {
      count: 0,
      link: '#new-affectations'
    },
    userAffectations: {
      link: '#user-affectations'
    },
    newSuivis: {
      count: 0,
      link: '#new-suivis'
    },
    noSuivis: {
      count: 0,
      link: '#no-suivis'
    },
    suivis: {
      countMoyen: 0,
      countByPartner: 0,
      countByUsager: 0
    },
    cloturesGlobales: {
      count: 0,
      link: '#clotures-globales'
    },
    cloturesPartenaires: {
      count: 0,
      link: '#clotures-partenaires'
    },
    users: {
      countActive: 0,
      percentActive: 0,
      countNotActive: 0,
      percentNotActive: 0
    },
    esaboraEvents: new Array<any>(),
    signalementsPerTerritoire: new Array<any>(),
    affectationsPartenaires: new Array<any>()
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
