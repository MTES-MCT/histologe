<template>
  <section class="addresses-history-map-filters">
    <div class="fr-grid-row fr-grid-row--gutters">
      <!-- Territoire -->
      <div
        v-if="(sharedState.user.isAdmin || sharedState.user.isMultiTerritoire) && sharedState.territories.length > 0"
        class="fr-col-12 fr-mb-2w"
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

      <!-- Communes -->
      <div class="fr-col-12 fr-mb-2w">
        <AppAutoComplete
          id="filter-communes"
          v-model="sharedState.input.filters.communes"
          :suggestions="sharedState.communes"
          :initSelectedSuggestions="sharedState.input.filters.communes"
          :placeholder="'Commune ou code postal'"
          title="Commune ou code postal"
          :iconClass="'fr-icon-map-pin-2-line'"
          :multiple="true"
          :reset="resetKey"
        >
          <template #label>Commune</template>
        </AppAutoComplete>
      </div>

      <!-- Filtres actifs -->
      <!-- Je laisse pour l'instant pour voir les communes sélectionnées. A voir comment on fait dans la version finale -->
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
import { store } from '../composables/useAddressesHistoryStore'
import { useAddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'
import { getActiveFilters, type ActiveFilter } from '../services/activeFiltersBuilder'
import type { AddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'
import HistoSelect from '../../common/HistoSelect.vue'
// import HistoMultiSelect from '../../common/HistoMultiSelect.vue'
// import AppSearch from '../../common/AppSearch.vue'
import AppAutoComplete from '../../common/AppAutoComplete.vue'

// State
const sharedState = store.state
const resetKey = ref(false)

// Composable
const filtersComposable = useAddressesHistoryFilters()

// Filtres actifs
const activeFilters = computed<ActiveFilter[]>(() => {
  return getActiveFilters(sharedState.input.filters as AddressesHistoryFilters)
})

/**
 * Quand le territoire change
 * - Pour la carte, filtre côté client sans recharger les données
 * - Met à jour le filtre dans le store (synchronisé avec la vue liste)
 */
const onTerritoryChange = async (value: string): Promise<void> => {
  sharedState.input.filters.territoire = value

  // Le filtrage côté client se fait automatiquement via la computed property
  // dans AddressesHistoryMap, pas besoin d'appeler l'API
}

/**
 * Supprime un filtre spécifique
  Je laisse pour l'instant pour voir les communes sélectionnées. A voir comment on fait dans la version finale
 */
const onRemoveFilter = async (key: keyof AddressesHistoryFilters): Promise<void> => {
  const filters = sharedState.input.filters

  // Si c'est le territoire, on réinitialise
  if (key === 'territoire') {
    filters.territoire = undefined

    // Sélectionner le premier territoire si plusieurs territoires existent
    if (sharedState.territories.length > 1) {
      filters.territoire = sharedState.territories[0].Id
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

  // Le filtrage se fait automatiquement via la computed property
}

// Au montage, sauvegarde le territoire initial
onMounted(() => {
  filtersComposable.saveCurrentTerritory()
})
</script>

<style>
section.addresses-history-map-filters {
  position: absolute;
  margin: 20px;
  z-index: 100;
  background-color: white;
  max-width: 400px;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
