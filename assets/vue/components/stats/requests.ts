import axios from 'axios'
import { store } from './store'

export const requests = {
	filter(functionReturn: Function) {
		let data = new FormData()
		if (store.state.filters.canFilterTerritoires) {
			data.append('territoire', store.state.filters.territoire)
		}
		data.append('communes', JSON.stringify(store.state.filters.communes))
		data.append('statut', store.state.filters.statut)
		data.append('etiquettes', JSON.stringify(store.state.filters.etiquettes))
		data.append('type', store.state.filters.type)
		if (store.state.filters.dateRange.length > 0) {
			const phpDateStart = new Date(store.state.filters.dateRange[0])
			phpDateStart.setMinutes(phpDateStart.getMinutes() - phpDateStart.getTimezoneOffset())
			data.append('dateStart', phpDateStart.toISOString())

			if (store.state.filters.dateRange.length > 1) {
				const phpDateEnd = new Date(store.state.filters.dateRange[1])
				phpDateEnd.setMinutes(phpDateEnd.getMinutes() - phpDateEnd.getTimezoneOffset())
				data.append('dateEnd', phpDateEnd.toISOString())
			}
		}
		data.append('countRefused', store.state.filters.countRefused ? '1' : '0')

		axios
			.post(store.props.ajaxurl, data, { timeout: 10000 })
			.then(response => {
				let responseData = response.data
				console.log('then')
				console.log(responseData)
				functionReturn(responseData)
			})
			.catch(error => {
				console.log('error.toJSON')
				console.log(error)
				functionReturn('error')
			})
	}
}