import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import type { ChartStats, FrontStatsData, FrontStatsResponse, StatsChartData } from '../types'

/**
 * Construit la liste des territoires, précédée de l'option « Tous »
 */
export function toTerritoiresOptions(list: Record<string, string> = {}): HistoInterfaceSelectOption[] {
  return [
    { Id: 'all', Text: 'Tous' },
    ...Object.entries(list).map(([id, text]) => {
      const optionItem = new HistoInterfaceSelectOption()
      optionItem.Id = id
      optionItem.Text = text
      return optionItem
    })
  ]
}

function toChartStats(thisYear: StatsChartData | undefined, all: StatsChartData | undefined): ChartStats {
  return { year: thisYear ?? {}, all: all ?? {} }
}

/**
 * Extrait les statistiques de la réponse
 */
export function mapFrontStats(response: FrontStatsResponse): FrontStatsData {
  return {
    countSignalementResolus: response.count_signalement_resolus,
    countSignalement: response.count_signalement,
    countTerritory: response.count_territory,
    percentValidation: response.percent_validation,
    percentCloture: response.percent_cloture,
    percentRefused: response.percent_refused,
    countImported: response.count_imported,
    countSignalementPerTerritory: response.signalement_per_territoire ?? {},
    charts: {
      perMonth: toChartStats(response.signalement_per_month_this_year, response.signalement_per_month),
      perStatut: toChartStats(response.signalement_per_statut_this_year, response.signalement_per_statut),
      perMotifCloture: toChartStats(response.signalement_per_motif_cloture_this_year, response.signalement_per_motif_cloture),
      perDesordresCategories: toChartStats(response.signalement_per_desordres_categories_this_year, response.signalement_per_desordres_categories),
      perLogementDesordres: toChartStats(response.signalement_per_logement_desordres_this_year, response.signalement_per_logement_desordres),
      perBatimentDesordres: toChartStats(response.signalement_per_batiment_desordres_this_year, response.signalement_per_batiment_desordres)
    }
  }
}
