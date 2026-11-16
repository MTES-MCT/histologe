// Option d'un filtre, telle que fournie par le serveur (data-territories, data-operators)
export interface CalendarFilterOption {
  id: string | number
  label: string
}

export interface CalendarFilters {
  // Vide : tous les territoires
  territoire: string
  operateurs: string[]
  uniquementMesDossiers: boolean
}

// Période affichée par le calendrier (Y-m-d), fin exclue
export interface CalendarPeriod {
  dateDebut: string
  dateFin: string
}

export interface StoreState {
  territories: CalendarFilterOption[]
  operators: CalendarFilterOption[]
  filters: CalendarFilters
  loadingInit: boolean
  hasErrorLoading: boolean
}

export interface StoreProps {
  ajaxurl: string
}
