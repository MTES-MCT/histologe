import type HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export type StatsChartData = Record<string, unknown>

/**
 * Ligne du tableau de répartition par partenaire :
 * [nom, total, en attente, en cours, fermés]
 */
export type PartenaireRow = [string, number, string, string, string]

export interface PartenaireStats {
  total: number
  wait: number
  wait_percent: number
  accepted: number
  accepted_percent: number
  closed: number
  closed_percent: number
}

export interface StatsFilters {
  communes: string[]
  epcis: string[]
  statut: string
  etiquettes: string[]
  type: string
  dateRange: Date[]
  countRefused: boolean
  countArchived: boolean
  territoire: string
}

export interface StatsFiltersOptions {
  communesList: HistoInterfaceSelectOption[]
  epcisList: HistoInterfaceSelectOption[]
  etiquettesList: HistoInterfaceSelectOption[]
  territoiresList: HistoInterfaceSelectOption[]
  canFilterTerritoires: boolean
  canFilterArchived: boolean
  canSeePerPartenaire: boolean
}

export interface StatsData {
  countSignalement?: number
  averageCriticite?: number
  averageDaysValidation?: number
  averageDaysClosure?: number
  countSignalementsRefuses?: number
  countSignalementsArchives?: number
  countSignalementFiltered?: number
  averageCriticiteFiltered?: number
  countSignalementPerMonth: StatsChartData
  countSignalementPerPartenaire: PartenaireRow[]
  countSignalementPerSituation: StatsChartData
  countSignalementPerCriticite: StatsChartData
  countSignalementPerStatut: StatsChartData
  countSignalementPerCriticitePercent: StatsChartData
  countSignalementPerVisite: StatsChartData
  countSignalementPerMotifCloture: StatsChartData
}

export interface StoreState {
  filters: StatsFilters
  options: StatsFiltersOptions
  stats: StatsData
  loadingInit: boolean
  loadingStats: boolean
  hasErrorLoading: boolean
}

export interface StoreProps {
  ajaxurl: string
}

export interface StatsResponse {
  list_communes?: Record<string, string>
  list_epcis?: Record<string, string>
  list_etiquettes?: Record<string, string>
  list_territoires?: Record<string, string>
  can_filter_territoires?: string
  can_filter_archived?: string
  can_see_per_partenaire?: string
  count_signalement?: number
  average_criticite?: number
  average_days_validation?: number
  average_days_closure?: number
  count_signalement_refuses?: number
  count_signalement_archives?: number
  count_signalement_filtered?: number
  average_criticite_filtered?: number
  countSignalementPerMonth?: StatsChartData
  countSignalementPerPartenaire?: Record<string, PartenaireStats>
  countSignalementPerSituation?: StatsChartData
  countSignalementPerCriticite?: StatsChartData
  countSignalementPerStatut?: StatsChartData
  countSignalementPerCriticitePercent?: StatsChartData
  countSignalementPerVisite?: StatsChartData
  countSignalementPerMotifCloture?: StatsChartData
}
