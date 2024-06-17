export interface Filters {
  [key: string]: any
}

export const SEARCH_FILTERS = [
  { type: 'text', name: 'territoire', showOptions: false, defaultValue: null },
  { type: 'text', name: 'searchTerms', showOptions: false, defaultValue: null },
  { type: 'text', name: 'status', showOptions: false, defaultValue: null },
  { type: 'text', name: 'page', showOptions: false, defaultValue: null },
  { type: 'text', name: 'sortBy', showOptions: false, defaultValue: null },
  { type: 'text', name: 'direction', showOptions: false, defaultValue: null },
  { type: 'text', name: 'procedure', showOptions: true, defaultValue: null },
  { type: 'text', name: 'visiteStatus', showOptions: true, defaultValue: null },
  { type: 'text', name: 'statusAffectation', showOptions: true, defaultValue: null },
  { type: 'text', name: 'typeDernierSuivi', showOptions: true, defaultValue: null },
  { type: 'text', name: 'criticiteScoreMin', showOptions: true, defaultValue: null },
  { type: 'text', name: 'criticiteScoreMax', showOptions: true, defaultValue: null },
  { type: 'text', name: 'typeDeclarant', showOptions: true, defaultValue: null },
  { type: 'text', name: 'natureParc', showOptions: true, defaultValue: null },
  { type: 'text', name: 'allocataire', showOptions: true, defaultValue: null },
  { type: 'text', name: 'enfantsM6', showOptions: true, defaultValue: null },
  { type: 'text', name: 'situation', showOptions: true, defaultValue: null },
  { type: 'text', name: 'relancesUsager', showOptions: false, defaultValue: 'Pas de suivi après 3 relances' },
  { type: 'text', name: 'sansSuiviPeriode', showOptions: false, defaultValue: 'Sans suivi depuis au moins 30 jours' },
  { type: 'text', name: 'nouveauSuivi', showOptions: false, defaultValue: 'Nouveaux suivis partenaires et usagers' },
  { type: 'text', name: 'showMyAffectationOnly', showOptions: true, defaultValue: null },
  { type: 'collection', name: 'communes', showOptions: false, defaultValue: null },
  { type: 'collection', name: 'epcis', showOptions: false, defaultValue: null },
  { type: 'collection', name: 'etiquettes', showOptions: true, defaultValue: null },
  { type: 'collection', name: 'partenaires', showOptions: true, defaultValue: null },
  { type: 'date', name: 'dateDepotDebut', showOptions: true, defaultValue: null },
  { type: 'date', name: 'dateDernierSuiviDebut', showOptions: true, defaultValue: null }
]
