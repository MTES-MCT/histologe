<template>
  <section>
    <div class="fr-grid-row fr-grid-row--gutters">
      <!-- Territoire -->
      <div
        v-if="(sharedState.user.isAdmin || sharedState.user.isMultiTerritoire) && sharedState.territories.length > 0"
        class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w"
      >
        <HistoSelect
          id="filter-territoire"
          v-model="sharedState.input.filters.territoire"
          @update:modelValue="onTerritoryChange"
          title="Rechercher par territoire"
          :option-items="sharedState.territories"
          :placeholder="'Tous'"
        >
          <template #label>Territoire</template>
        </HistoSelect>
      </div>

      <!-- Adresse -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <AppSearch
          id="filter-search-terms"
          v-model="sharedState.input.filters.adresse"
          :placeholder="'Taper l\'adresse'"
          title="Taper l'adresse"
          :minLengthSearch="3"
          @update:modelValue="notifyChange"
        >
          <template #label>Adresse</template>
        </AppSearch>
      </div>

      <!-- Communes -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <AppAutoComplete
          id="filter-communes"
          v-model="sharedState.input.filters.communes"
          :suggestions="sharedState.communes"
          :initSelectedSuggestions="sharedState.input.filters.communes"
          :placeholder="'Commune ou code postal'"
          title="Commune ou code postal"
          :multiple="true"
          @update:modelValue="notifyChange"
          :reset="resetKey"
          :iconClass="'fr-icon-map-pin-2-line'"
        >
          <template #label>Commune</template>
        </AppAutoComplete>
      </div>

      <!-- Bailleur ou syndic -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <AppAutoComplete
          id="filter-bailleur-ou-syndic"
          v-model="sharedState.input.filters.bailleurOuSyndic"
          :suggestions="sharedState.bailleursAndSyndic"
          :initSelectedSuggestions="sharedState.input.filters.bailleurOuSyndic"
          :placeholder="'Nom du bailleur ou syndic'"
          title="Nom du bailleur ou syndic"
          :multiple="true"
          @update:modelValue="notifyChange"
          :reset="resetKey"
          :iconClass="'fr-icon-user-search-fill'"
        >
          <template #label>Bailleur ou syndic gestionnaire</template>
        </AppAutoComplete>
      </div>

      <!-- Zones -->
      <div
        v-if="sharedState.zones.length > 0"
        class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w"
      >
        <HistoMultiSelect
          id="filter-zones"
          v-model="sharedState.input.filters.zones"
          @update:modelValue="notifyChange"
          :option-items="sharedState.zones"
          title="Rechercher par zones"
          :active="true"
        >
          <template #label>Zones</template>
        </HistoMultiSelect>
      </div>

      <!-- Nature du parc -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <HistoSelect
          id="filter-nature-parc"
          v-model="sharedState.input.filters.natureParc"
          @update:modelValue="notifyChange"
          :option-items="natureParcOptions"
          :placeholder="'Tous'"
          title="Rechercher par nature du parc"
        >
          <template #label>Nature du parc</template>
        </HistoSelect>
      </div>

      <!-- Dossiers multiples -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <HistoSelect
          id="filter-dossiers-multiples"
          v-model="sharedState.input.filters.dossiersMultiples"
          @update:modelValue="notifyChange"
          :option-items="dossiersMultiplesOptions"
          :placeholder="'Tout'"
          title="Rechercher si dossiers multiples à l'adresse"
        >
          <template #label>Dossiers multiples à l'adresse</template>
        </HistoSelect>
      </div>

      <!-- Types d'arrêtés -->
      <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
        <HistoMultiSelect
          id="filter-types-arretes"
          v-model="sharedState.input.filters.typesArretes"
          @update:modelValue="notifyChange"
          :option-items="sharedState.typesArretes"
          title="Rechercher par types d'arrêtés"
          :active="true"
        >
          <template #label>Types d'arrêtés</template>
        </HistoMultiSelect>
      </div>

      <!-- Bouton Reset -->
      <div class="fr-col-12 fr-col-md-3 fr-pt-0w fr-pt-md-5w fr-grid-row--middle">
        <button
          @click="onFiltersReset"
          class="fr-link fr-link--icon-left fr-icon-close-circle-line fr-text--sm"
        >
          Réinitialiser les résultats
        </button>
      </div>

      <!-- Filtres actifs -->
      <div v-if="activeFilters.length > 0" class="fr-col-12 fr-mt-2w">
        <ul class="fr-tags-group">
          <li v-for="filter in activeFilters" :key="filter.key">
            <button
              class="fr-tag fr-tag--sm fr-tag--dismiss"
              :aria-label="`Retirer le filtre ${filter.label}`"
              @click="onRemoveFilter(filter.key)"
            >
              {{ filter.label }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { store } from '../store'
import { useAddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'
import { getActiveFilters, type ActiveFilter } from '../services/activeFiltersBuilder'
import type { AddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoMultiSelect from '../../common/HistoMultiSelect.vue'
import AppSearch from '../../common/AppSearch.vue'
import AppAutoComplete from '../../common/AppAutoComplete.vue'

// Émissions
const emit = defineEmits<{
  change: []
}>()

// State
const sharedState = store.state
const resetKey = ref(false)

// Composable
const filtersComposable = useAddressesHistoryFilters()

// Options statiques
const natureParcOptions = computed(() => store.state.natureParcList)
const dossiersMultiplesOptions = computed(() => store.state.dossiersMultiplesList)

// Filtres actifs
const activeFilters = computed<ActiveFilter[]>(() => {
  return getActiveFilters(sharedState.input.filters as AddressesHistoryFilters)
})

/**
 * Notifie le parent que les filtres ont changé
 */
const notifyChange = (): void => {
  emit('change')
}

/**
 * Quand le territoire change
 * - Réinitialise communes et zones
 * - Recharge les settings
 * - Notifie le changement
 */
const onTerritoryChange = async (value: string): Promise<void> => {
  sharedState.input.filters.communes = []
  sharedState.input.filters.zones = []
  sharedState.currentTerritoryId = value

  await filtersComposable.reloadSettings()

  notifyChange()
}

/**
 * Réinitialise tous les filtres
 */
const onFiltersReset = async (): Promise<void> => {
  const territoryHasChanged = filtersComposable.hasTerritoryChanged()

  filtersComposable.resetFilters()
  resetKey.value = !resetKey.value

  if (territoryHasChanged) {
    await filtersComposable.reloadSettings()
  }

  notifyChange()
}

/**
 * Supprime un filtre spécifique
 */
const onRemoveFilter = async (key: keyof AddressesHistoryFilters): Promise<void> => {
  const filters = sharedState.input.filters

  // Si c'est le territoire, on doit recharger les settings
  if (key === 'territoire') {
    const territoryHasChanged = filters.territoire !== undefined
    filters.territoire = undefined
    sharedState.currentTerritoryId = ''

    if (territoryHasChanged) {
      await filtersComposable.reloadSettings()
    }
  }
  // Si c'est un tableau, on le vide
  else if (Array.isArray(filters[key])) {
    (filters[key] as any[]) = []
  }
  // Sinon on met undefined
  else {
    filters[key] = undefined as any
  }

  // Toggle reset pour forcer la mise à jour des composants enfants
  resetKey.value = !resetKey.value

  notifyChange()
}

// Au montage, sauvegarde le territoire initial
onMounted(() => {
  filtersComposable.saveCurrentTerritory()
})
</script>

<style>
</style>

