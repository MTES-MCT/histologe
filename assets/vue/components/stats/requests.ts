import axios from 'axios'
import { store } from './store'

export const requests = {
	/**
	 * Filtre les statistiques
	 */
	filter(functionReturn: Function) {
		let data = new FormData()
		data.append('communes', JSON.stringify(store.state.filters.communes))
		data.append('statut', store.state.filters.statut)
		data.append('etiquettes', JSON.stringify(store.state.filters.etiquettes))
		data.append('type', store.state.filters.type)
		if (store.state.filters.dateRange.length > 0) {
			data.append('dateStart', store.state.filters.dateRange[0].toString())
			if (store.state.filters.dateRange.length > 1) {
				data.append('dateEnd', store.state.filters.dateRange[1].toString())
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
				console.log(error.toJSON())
				console.log(error.config)
				functionReturn('error')
			})
	}
}