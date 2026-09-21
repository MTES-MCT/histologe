import { computed } from 'vue'
import { store } from './useAddressesHistoryStore'

const EPCI_PREFIX = 'EPCI : '

/**
 * Suggestions d'adresses et de communes limitées par les filtres sélectionnés (filtrage local, sans appel API).
 * - Commune : les adresses suggérées sont celles de la commune (ignoré pour un EPCI, non connu côté adresse)
 * - Zone (si `withZone`) : adresses et communes suggérées limitées à la zone
 */
export function useFilteredSuggestions(options: { withZone: boolean }) {
  const state = store.state

  const selectedZoneId = computed<number | null>(() =>
    options.withZone && state.input.filters.zone ? Number(state.input.filters.zone) : null
  )

  const selectedCommune = computed<string | null>(() => {
    const commune = state.input.filters.communeOuEpci
    return commune && !commune.startsWith(EPCI_PREFIX) ? commune : null
  })

  const addressesInZone = computed(() => {
    const zoneId = selectedZoneId.value
    if (zoneId === null) return state.addressesWithZones
    return state.addressesWithZones.filter((a) => a.zoneIds.includes(zoneId))
  })

  const addressesSuggestions = computed<string[]>(() => {
    if (selectedZoneId.value === null && selectedCommune.value === null) {
      return state.addressesSuggestions
    }
    const commune = selectedCommune.value
    return addressesInZone.value
      .filter((a) => commune === null || a.commune === commune)
      .map((a) => a.address)
  })

  const communesSuggestions = computed<string[]>(() => {
    if (selectedZoneId.value === null) return state.communes
    const communes = addressesInZone.value.map((a) => a.commune).filter((c): c is string => !!c)
    return Array.from(new Set(communes)).sort((a, b) => a.localeCompare(b))
  })

  return { addressesSuggestions, communesSuggestions }
}
