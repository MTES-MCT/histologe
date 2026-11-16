// Option d'un filtre, telle que fournie par le serveur (data-territories, data-operators)
export interface CalendarFilterOption {
  id: string | number
  label: string
}

export interface CalendarFilters {
  // Vide : tous les territoires
  territoryId: string
  operatorIds: string[]
  onlyMySignalements: boolean
}
