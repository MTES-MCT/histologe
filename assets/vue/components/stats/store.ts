import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export const store = {
	state: {
		filters: {
			communes: [],
			statut: 'all',
			etiquettesList: new Array<HistoInterfaceSelectOption>(),
			etiquettes: '',
			type: 'all',
			dateRange: new Array<Date>(),
			countRefused: false,
			territoire: 'all',
			canFilterTerritoires: false,
			territoiresList: new Array<HistoInterfaceSelectOption>()
		},
		stats: {
			countSignalement: 0,
			averageCriticite: 0,
			averageDaysValidation: 0,
			averageDaysClosure: 0,
			countSignalementPerMonth: Object,
			countSignalementPerPartenaire: new Array<any>(),
			countSignalementPerSituation: Object,
			countSignalementPerCriticite: Object,
			countSignalementPerStatut: Object,
			countSignalementPerCriticitePercent: Object,
			countSignalementPerVisite: Object
		}
	},
	props: {
		ajaxurl: ''
	}
}
  