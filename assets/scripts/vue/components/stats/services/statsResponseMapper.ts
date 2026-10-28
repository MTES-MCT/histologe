import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import type {
  PartenaireRow,
  PartenaireStats,
  StatsData,
  StatsFiltersOptions,
  StatsResponse
} from '../types'

/**
 * Transforme un dictionnaire { id: libellé } en options de liste déroulante
 */
export function toSelectOptions(list: Record<string, string> = {}): HistoInterfaceSelectOption[] {
  return Object.entries(list).map(([id, text]) => {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = id
    optionItem.Text = text
    return optionItem
  })
}

/**
 * Construit les lignes du tableau de répartition par partenaire
 */
export function toPartenaireRows(countPerPartenaire: Record<string, PartenaireStats> = {}): PartenaireRow[] {
  return Object.entries(countPerPartenaire).map(([nomPartenaire, data]) => [
    nomPartenaire,
    data.total,
    `${data.wait} (${data.wait_percent} %)`,
    `${data.accepted} (${data.accepted_percent} %)`,
    `${data.closed} (${data.closed_percent} %)`
  ])
}

/**
 * Extrait les options des filtres de la réponse
 */
export function mapFiltersOptions(response: StatsResponse): StatsFiltersOptions {
  const canFilterTerritoires = response.can_filter_territoires === '1'

  const territoiresList: HistoInterfaceSelectOption[] = []
  if (canFilterTerritoires) {
    territoiresList.push({ Id: 'all', Text: 'Tous' }, ...toSelectOptions(response.list_territoires))
  }

  return {
    communesList: toSelectOptions(response.list_communes),
    epcisList: toSelectOptions(response.list_epcis),
    etiquettesList: toSelectOptions(response.list_etiquettes),
    territoiresList,
    canFilterTerritoires,
    canFilterArchived: response.can_filter_archived === '1',
    canSeePerPartenaire: response.can_see_per_partenaire === '1'
  }
}

/**
 * Extrait les statistiques de la réponse
 */
export function mapStats(response: StatsResponse): StatsData {
  return {
    countSignalement: response.count_signalement,
    averageCriticite: response.average_criticite,
    averageDaysValidation: response.average_days_validation,
    averageDaysClosure: response.average_days_closure,
    countSignalementsRefuses: response.count_signalement_refuses,
    countSignalementsArchives: response.count_signalement_archives,
    countSignalementFiltered: response.count_signalement_filtered,
    averageCriticiteFiltered: response.average_criticite_filtered,
    countSignalementPerMonth: response.countSignalementPerMonth ?? {},
    countSignalementPerPartenaire: toPartenaireRows(response.countSignalementPerPartenaire),
    countSignalementPerSituation: response.countSignalementPerSituation ?? {},
    countSignalementPerCriticite: response.countSignalementPerCriticite ?? {},
    countSignalementPerStatut: response.countSignalementPerStatut ?? {},
    countSignalementPerCriticitePercent: response.countSignalementPerCriticitePercent ?? {},
    countSignalementPerVisite: response.countSignalementPerVisite ?? {},
    countSignalementPerMotifCloture: response.countSignalementPerMotifCloture ?? {}
  }
}
