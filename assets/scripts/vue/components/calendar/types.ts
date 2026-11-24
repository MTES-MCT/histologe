export interface CalendarFilterOption {
  id: string | number
  label: string
}

export interface CalendarFilters {
  territoire: string
  operateurs: string[]
  uniquementMesDossiers: boolean
}

// Période affichée par le calendrier (Y-m-d), fin exclue
export interface CalendarPeriod {
  dateDebut: string
  dateFin: string
}

// Données propres à chaque événement, envoyées par le serveur (extendedProps)
export interface CalendarEventProps {
  type: 'club' | 'visite' | 'visite_controle'
  link: string
  occupant?: string
  operator?: string | null
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
