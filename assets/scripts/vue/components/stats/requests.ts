import axios from 'axios'
import { store } from './store'
import * as Sentry from '@sentry/browser'

export const requests = {
  filter (functionReturn: Function) {
    const data = new FormData()
    if (store.state.filters.canFilterTerritoires) {
      data.append('territoire', store.state.filters.territoire)
    }
    data.append('communes', JSON.stringify(store.state.filters.communes))
    data.append('epcis', JSON.stringify(store.state.filters.epcis))
    data.append('statut', store.state.filters.statut)
    data.append('etiquettes', JSON.stringify(store.state.filters.etiquettes))
    data.append('type', store.state.filters.type)
    if (store.state.filters.dateRange !== null && store.state.filters.dateRange.length > 0) {
      const phpDateStart = new Date(store.state.filters.dateRange[0])
      phpDateStart.setMinutes(phpDateStart.getMinutes() - phpDateStart.getTimezoneOffset())
      data.append('dateStart', phpDateStart.toISOString())

      if (store.state.filters.dateRange.length > 1) {
        const phpDateEnd = store.state.filters.dateRange[1] !== null ? new Date(store.state.filters.dateRange[1]) : new Date()
        phpDateEnd.setMinutes(phpDateEnd.getMinutes() - phpDateEnd.getTimezoneOffset())
        data.append('dateEnd', phpDateEnd.toISOString())
      }
    }
    data.append('countRefused', store.state.filters.countRefused ? '1' : '0')
    data.append('countArchived', store.state.filters.countArchived ? '1' : '0')

    axios
      .post(store.props.ajaxurl, data, { timeout: 15000 })
      .then(response => {
        const responseData = response.data
        functionReturn(responseData)
      })
      .catch(error => {
        console.error(error)
        Sentry.captureException(new Error(error))
        functionReturn('error')
      })
  }
}
