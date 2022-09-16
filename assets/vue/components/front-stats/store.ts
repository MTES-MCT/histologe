import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
	state: {
		filters: {
			territoire: 'all',
			territoiresList: new Array<HistoInterfaceSelectOption>()
		},
		stats: {
			countSignalement: 0,
			countTerritory: 0,
			percentValidation: 0,
			percentCloture: 0,
			countSignalementPerMonth: Object,
			countSignalementPerStatut: Object,
			countSignalementPerSituation: Object,
			countSignalementPerMotifCloture: Object
		}
	},
	props: {
		ajaxurl: ''
	}
}
  