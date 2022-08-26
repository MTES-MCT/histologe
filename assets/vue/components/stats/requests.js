import axios from 'axios'
import { store } from './store.js'

export const requests = {
	/**
	 * Filtre les statistiques
	 */
	filter(functionReturn) {
		let data = new FormData()

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