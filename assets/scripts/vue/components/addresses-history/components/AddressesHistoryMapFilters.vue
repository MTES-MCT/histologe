<template>
  <section class="addresses-history-map-filters fr-m-5w">
    <div class="fr-m-2w">
      <div class="fr-notice fr-notice--info fr-mb-2w">
          <div class="fr-container">
              <div class="fr-notice__body">
                  <p>
                      <span class="fr-notice__title">Légende</span>
                      <br>
                      <span class="fr-notice__desc">Chaque forme correspond à un type de donnée. Quand plusieurs types de données sont présents à une adresse, on affiche un triange (<span class="fr-icon-triangle-fill fr-icon--sm" aria-hidden="true"></span>).</span>
                  </p>
                  <button title="Masquer le message" onclick="const notice = this.parentNode.parentNode.parentNode; notice.parentNode.removeChild(notice)" type="button" class="fr-btn--close fr-btn">Masquer le message</button>
              </div>
          </div>
      </div>

      <h2 class="fr-h4 fr-text-label--blue-france">Paramètres de la carte</h2>

      <HistoToggle
        id="toggle-map-niveaux-gris"
        v-model="sharedState.input.params.niveauxGris"
        containerClass="fr-mb-2w"
      >
        <template #label>Afficher la carte en niveaux de gris</template>
      </HistoToggle>

      <HistoToggle
        id="toggle-map-limites-administratives"
        v-model="sharedState.input.params.limitesAdministratives"
        containerClass="fr-mb-2w"
      >
        <template #label>Afficher les limites administratives</template>
      </HistoToggle>

      <HistoToggle
        id="toggle-map-zones-territoire"
        v-model="sharedState.input.params.zonesTerritoire"
        containerClass="fr-mb-2w"
      >
        <template #label>Afficher les zones du territoire</template>
      </HistoToggle>

      <h2 class="fr-h4 fr-mt-2w fr-text-label--blue-france">Rechercher un lieu</h2>

      <!-- Territoire -->
      <div
        v-if="(sharedState.user.isAdmin || sharedState.user.isMultiTerritoire) && sharedState.territories.length > 0"
        class="fr-mb-2w"
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
      <div class="fr-mb-2w">
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
      <div v-if="activeFilters.length > 0" class="fr-mt-2w">
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
import HistoToggle from '../../common/HistoToggle.vue'
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
 * - Réinitialise communes et zone
 * - Recharge les settings
 * - Recharge les adresses
 */
const onTerritoryChange = async (value: string): Promise<void> => {
  sharedState.input.filters.communes = []
  sharedState.input.filters.zone = undefined
  sharedState.input.filters.territoire = value

  await filtersComposable.reloadSettings()
  await filtersComposable.reloadAddresses()
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
  z-index: 100;
  background-color: white;
  max-width: 450px;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 2px 0px 6px rgba(0, 0, 0, 0.3);
}
</style>
