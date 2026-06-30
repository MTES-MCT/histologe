import { store } from '../store'
import type { AddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'

export interface ActiveFilter {
  key: keyof AddressesHistoryFilters
  label: string
  displayValue: string
}

/**
 * Construit le label d'affichage pour un filtre actif
 */
export function buildFilterLabel(key: keyof AddressesHistoryFilters, value: any): string {
  // Territoire
  if (key === 'territoire') {
    const territory = store.state.territories.find(t => t.Id.toString() === value)
    return territory ? `Territoire : ${territory.Text}` : ''
  }

  // Adresse
  if (key === 'adresse') {
    return `Adresse : ${value}`
  }

  // Communes (tableau)
  if (key === 'communes' && Array.isArray(value) && value.length > 0) {
    const firstCommune = store.state.communes.find(c => c === value[0])
    const count = value.length
    return count > 1
      ? `Communes : ${firstCommune} +${count - 1}`
      : `Commune : ${firstCommune}`
  }

  // Bailleur ou syndic
  if (key === 'bailleurOuSyndic') {
    const bailleur = store.state.bailleursAndSyndic.find(b => b.Id.toString() === value)
    return bailleur ? `Bailleur/Syndic : ${bailleur.Text}` : ''
  }

  // Zones (tableau)
  if (key === 'zones' && Array.isArray(value) && value.length > 0) {
    const matchedZones = store.state.zones.filter(z => value.includes(z.Id.toString()))
    if (matchedZones.length > 0) {
      return matchedZones.length > 1
        ? `Zones : ${matchedZones[0].Text} +${matchedZones.length - 1}`
        : `Zone : ${matchedZones[0].Text}`
    }
  }

  // Nature du parc
  if (key === 'natureParc') {
    const natureParc = store.state.natureParcList.find(n => n.Id === value)
    return natureParc ? `Nature du parc : ${natureParc.Text}` : ''
  }

  // Dossiers multiples
  if (key === 'dossiersMultiples') {
    const option = store.state.dossiersMultiplesList.find(d => d.Id === value)
    return option ? `Dossiers multiples : ${option.Text}` : ''
  }

  // Types d'arrêtés (tableau)
  if (key === 'typesArretes' && Array.isArray(value) && value.length > 0) {
    const matchedTypes = store.state.typesArretes.filter(t => value.includes(t.Id.toString()))
    if (matchedTypes.length > 0) {
      return matchedTypes.length > 1
        ? `Types d'arrêtés : ${matchedTypes[0].Text} +${matchedTypes.length - 1}`
        : `Type d'arrêté : ${matchedTypes[0].Text}`
    }
  }

  return ''
}

/**
 * Récupère la liste des filtres actifs
 */
export function getActiveFilters(filters: AddressesHistoryFilters): ActiveFilter[] {
  const activeFilters: ActiveFilter[] = []

  for (const [key, value] of Object.entries(filters)) {
    const filterKey = key as keyof AddressesHistoryFilters

    // Ignore les filtres vides
    if (value === undefined || value === null) continue
    if (Array.isArray(value) && value.length === 0) continue
    if (typeof value === 'string' && value.trim() === '') continue

    const label = buildFilterLabel(filterKey, value)
    if (label) {
      activeFilters.push({
        key: filterKey,
        label,
        displayValue: Array.isArray(value) ? value.join(', ') : String(value)
      })
    }
  }

  return activeFilters
}
