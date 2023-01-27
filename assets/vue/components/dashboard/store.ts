
export const store = {
  state: {
    user: {
      prenom: '',
      isAdmin: false,
      isResponsableTerritoire: true,
      isAdministrateurPartenaire: false
    },
    territories: [],
    filters: {
      territory: ''
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
      count: 1,
      link: '#new-affectations'
    },
    userAffectations: {
      link: '#user-affectations'
    },
    newSuivis: {
      count: 2,
      link: '#new-suivis'
    },
    noSuivis: {
      count: 3,
      link: '#no-suivis'
    },
    suivis: {
      countMoyen: 1,
      countByPartner: 2,
      countByUsager: 3
    },
    cloturesGlobales: {
      count: 5,
      link: '#clotures-globales'
    },
    cloturesPartenaires: {
      count: 6,
      link: '#clotures-partenaires'
    },
    users: {
      countActive: 1,
      percentActive: 33,
      countNotActive: 2,
      percentNotActive: 66
    }
  },
  props: {
    ajaxurlFilter: '',
    ajaxurlPartners: '',
    ajaxurlSignalementsNosuivi: '',
    ajaxurlSignamentsPerTerritoire: '',
    ajaxurlConnectionsEsabora: ''
  }
}
