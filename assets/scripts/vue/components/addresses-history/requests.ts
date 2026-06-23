import { store } from './store'
import { commonRequests } from '../common/utils/commonRequests'

export const requests = {
  /*
  getSignalements (functionReturn: Function, options = {}) {
    const ajaxUrl = store.props.ajaxurlSignalement
    commonRequests.doRequest(ajaxUrl, functionReturn, options)
  },
  deleteSignalement (uuid: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlRemoveSignalement = decodeURIComponent(store.props.ajaxurlRemoveSignalement)
    ajaxurlRemoveSignalement = ajaxurlRemoveSignalement.replace('<uuid>', uuid)
    commonRequests.doPostRequest(ajaxurlRemoveSignalement, { _token: csrfToken }, functionReturn)
  },
  */
  getSettings (functionReturn: Function) {
    let url = store.props.ajaxurlSettings
    if (store.state.currentTerritoryId.length > 0) {
      url += `?territoryId=${store.state.currentTerritoryId}`
    }

    commonRequests.doRequest(url, functionReturn)
  },
}
