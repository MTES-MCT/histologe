import axios from 'axios'
import { store } from './store'

export const requests = {
  doRequest (ajaxUrl: string, functionReturn: Function) {
    axios
      .get(ajaxUrl, { timeout: 15000 })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.log(error)
        functionReturn('error')
      })
  },

  initSettings (functionReturn: Function) {
    const url = store.props.ajaxurlSettings
    requests.doRequest(url, functionReturn)
  },

  initKPI (functionReturn: Function) {
    let url = store.props.ajaxurlKpi
    if (store.state.filters.territory !== '' && store.state.filters.territory !== 'all') {
      url += '?territory=' + store.state.filters.territory
    }
    requests.doRequest(url, functionReturn)
  },

  initAffectationPartner (functionReturn: Function) {
    let url = store.props.ajaxurlPartners
    if (store.state.filters.territory !== '' && store.state.filters.territory !== 'all') {
      url += '?territory=' + store.state.filters.territory
    }
    requests.doRequest(url, functionReturn)
  },

  initEsaboraEvents (functionReturn: Function) {
    let url = store.props.ajaxurlConnectionsEsabora
    if (store.state.filters.territory !== '' && store.state.filters.territory !== 'all') {
      url += '?territory=' + store.state.filters.territory
    }
    requests.doRequest(url, functionReturn)
  },

  initSignalementsNoSuivi (functionReturn: Function) {
    let url = store.props.ajaxurlSignalementsNosuivi
    if (store.state.filters.territory !== '' && store.state.filters.territory !== 'all') {
      url += '?territory=' + store.state.filters.territory
    }
    requests.doRequest(url, functionReturn)
  },

  initSignalementsPerTerritoire (functionReturn: Function) {
    let url = store.props.ajaxurlSignalementsPerTerritoire
    if (store.state.filters.territory !== '' && store.state.filters.territory !== 'all') {
      url += '?territory=' + store.state.filters.territory
    }
    requests.doRequest(url, functionReturn)
  }
}
