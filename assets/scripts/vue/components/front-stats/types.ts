import type HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export type StatsChartData = Record<string, unknown>

/**
 * Période affichée par un graphique : l'année en cours ou le total
 */
export type YearType = 'year' | 'all'

export type ChartKey =
  | 'perMonth'
  | 'perStatut'
  | 'perMotifCloture'
  | 'perDesordresCategories'
  | 'perLogementDesordres'
  | 'perBatimentDesordres'

export type ChartStats = Record<YearType, StatsChartData>

export interface TerritoryCount {
  name: string
  zip: string
  count: number
}

export interface FrontStatsFilters {
  territoire: string
  yearTypes: Record<ChartKey, YearType>
}

export interface FrontStatsData {
  countSignalementResolus: number
  countSignalement: number
  countTerritory: number
  percentValidation: number
  percentCloture: number
  percentRefused: number
  countImported: number
  countSignalementPerTerritory: Record<string, TerritoryCount>
  charts: Record<ChartKey, ChartStats>
}

export interface StoreState {
  filters: FrontStatsFilters
  territoiresList: HistoInterfaceSelectOption[]
  stats: FrontStatsData
  loadingInit: boolean
  loadingStats: boolean
  hasErrorLoading: boolean
}

export interface StoreProps {
  ajaxurl: string
  platformName: string
}

export interface FrontStatsResponse {
  count_signalement_resolus: number
  count_signalement: number
  count_territory: number
  percent_validation: number
  percent_cloture: number
  percent_refused: number
  count_imported: number
  list_territoires?: Record<string, string>
  signalement_per_territoire?: Record<string, TerritoryCount>
  signalement_per_month?: StatsChartData
  signalement_per_month_this_year?: StatsChartData
  signalement_per_statut?: StatsChartData
  signalement_per_statut_this_year?: StatsChartData
  signalement_per_motif_cloture?: StatsChartData
  signalement_per_motif_cloture_this_year?: StatsChartData
  signalement_per_desordres_categories?: StatsChartData
  signalement_per_desordres_categories_this_year?: StatsChartData
  signalement_per_logement_desordres?: StatsChartData
  signalement_per_logement_desordres_this_year?: StatsChartData
  signalement_per_batiment_desordres?: StatsChartData
  signalement_per_batiment_desordres_this_year?: StatsChartData
}
