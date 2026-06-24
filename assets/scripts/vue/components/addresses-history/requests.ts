import { store } from './store'
import { commonRequests } from '../common/utils/commonRequests'

export const requests = {
  getAddresses (functionReturn: Function, options = {}) {
    const ajaxUrl = store.props.ajaxurlAddresses
    commonRequests.doRequest(ajaxUrl, functionReturn, options)
  },
  getSettings (functionReturn: Function) {
    let url = store.props.ajaxurlSettings
    if (store.state.currentTerritoryId.length > 0) {
      url += `?territoryId=${store.state.currentTerritoryId}`
    }

    commonRequests.doRequest(url, functionReturn)
  },
}
