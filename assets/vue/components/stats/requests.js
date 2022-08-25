// import axios from 'axios'
import { store } from './store.js'

export const requests = {
	/**
	 * Filtre les statistiques
	 */
	filter(functionReturn) {
		let data = new FormData()

		setTimeout(functionReturn, 2000)
		/*
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
				this.logRequestError('getCurrentUserInfo >> error >> ' + error.toString() + ' >>>> ' + JSON.stringify(error))
				functionReturn('error')
			})
			*/
	}
}