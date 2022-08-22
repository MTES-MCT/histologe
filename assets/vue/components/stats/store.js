export const store = {
	state: {
		filters: {
			communes: [],
			statut: 'all',
			etiquette: [],
			type: 'all',
			startDate: '',
			endDate: '',
			countRefused: false
		},
		stats: {
			countSignalement: 1,
			averageCriticite: 5,
			averageDaysValidation: 4,
			averageDaysClosure: 3,
			countSignalementPerMonth: [],
			countSignalementPerPartenaire: [],
			countSignalementPerSituation: [],
			countSignalementPerCriticite: [],
			countSignalementPerStatut: [],
			countSignalementPerCriticitePercent: [],
			countSignalementPerVisite: []
		}
	},
	props: {
		ajaxurl: ''
	}
}