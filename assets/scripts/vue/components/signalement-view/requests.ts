import { store } from './store'
import { commonRequests } from '../common/utils/commonRequests'

export const requests = {
  getSignalements (functionReturn: Function, options = {}) {
    const ajaxUrl = store.props.ajaxurlSignalement
    commonRequests.doRequest(ajaxUrl, functionReturn, options)
  },
  deleteSignalement (uuid: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlRemoveSignalement = decodeURIComponent(store.props.ajaxurlRemoveSignalement)
    ajaxurlRemoveSignalement = ajaxurlRemoveSignalement.replace('<uuid>', uuid)
    commonRequests.doPostRequest(ajaxurlRemoveSignalement, { _token: csrfToken }, functionReturn)
  },
  getSettings (functionReturn: Function) {
    let url = store.props.ajaxurlSettings
    if (store.state.currentTerritoryId.length > 0) {
      url += `?territoryId=${store.state.currentTerritoryId}`
    }

    commonRequests.doRequest(url, functionReturn)
  },
  saveSearch (payload: { name: string; params: any }, csrfToken: string, functionReturn: Function) {
    const ajaxUrlSaveSearch = decodeURIComponent(store.props.ajaxurlSaveSearch)
    commonRequests.doPostRequest(
      ajaxUrlSaveSearch,
      { _token: csrfToken, name: payload.name, params: payload.params },
      functionReturn
    )
  },
  deleteSearch (id: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlDeleteSearch = decodeURIComponent(store.props.ajaxurlDeleteSearch)
    ajaxurlDeleteSearch = ajaxurlDeleteSearch.replace('<id>', id)
    commonRequests.doPostRequest(ajaxurlDeleteSearch, { _token: csrfToken }, functionReturn, [id])
  },
  editSearch (id: string, newName: string, csrfToken: string, functionReturn: Function) {
    let ajaxurlEditSearch = decodeURIComponent(store.props.ajaxurlEditSearch)
    ajaxurlEditSearch = ajaxurlEditSearch.replace('<id>', id)
    commonRequests.doPostRequest(ajaxurlEditSearch, { _token: csrfToken, name: newName }, functionReturn, [id, newName])
  }
}
