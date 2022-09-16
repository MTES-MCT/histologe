import axios from 'axios'
import { store } from './store'

export const requests = {
	/**
	 * Get front statistics
	 */
	get(functionReturn: Function) {
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
				console.log(error)
				functionReturn('error')
			})
	}
}