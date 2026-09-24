<template>
  <section
    class="addresses-history-map-filters fr-m-5w"
    :class="{ 'addresses-history-map-filters--retracted': isRetracted }"
  >
    <div class="addresses-history-map-filters__scroll">
        <div id="addresses-history-map-filters-legend" class="filters-collapsible">
          <div class="filters-collapsible__inner">
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
          </div>
        </div>

        <div class="visible-when-retracted">
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
        </div>

        <div id="addresses-history-map-filters-content" class="filters-collapsible">
          <div class="filters-collapsible__inner">
            <hr class="fr-mt-5w">

            <h2 class="fr-h4 fr-text-label--blue-france">Rechercher un lieu</h2>

            <!-- Territoire -->
            <div
              v-if="(sharedState.user.isAdmin || sharedState.user.isMultiTerritoire) && sharedState.territories.length > 0"
              class="fr-mb-2w"
            >
              <HistoSelect
                id="filter-map-territoire"
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
            <div class="fr-mb-2w">
              <AppAutoComplete
                id="filter-map-adresse"
                v-model="sharedState.input.filters.adresse"
                :suggestions="addressesSuggestions"
                :initSelectedSuggestions="sharedState.input.filters.adresse"
                :placeholder="'Taper l\'adresse du logement'"
                title="Adresse"
                :iconClass="'fr-icon-map-pin-2-fill'"
                :minLengthSearch="3"
                :multiple="false"
              >
                <template #label>Adresse</template>
              </AppAutoComplete>
            </div>

            <!-- Communes -->
            <div class="fr-mb-2w">
              <AppAutoComplete
                id="filter-map-commune-or-epci"
                v-model="sharedState.input.filters.communeOuEpci"
                :suggestions="sharedState.communes"
                :initSelectedSuggestions="sharedState.input.filters.communeOuEpci"
                :placeholder="'Commune ou EPCI'"
                title="Commune ou EPCI"
                :iconClass="'fr-icon-map-pin-2-fill'"
                :multiple="false"
              >
                <template #label>Commune ou EPCI</template>
              </AppAutoComplete>
            </div>

            <hr class="fr-mt-5w">

            <h2 class="fr-h4 fr-text-label--blue-france">Affichage des données</h2>

            <HistoToggle
              id="toggle-map-dossiers-multiples"
              :model-value="dossiersMultiplesToggleValue"
              @update:model-value="onDossiersMultiplesToggle"
              containerClass="fr-mb-2w"
            >
              <template #label>
                <span class="fr-picto-dossiers-multiples"></span>
                Signalements multiples à l'adresse
              </template>
            </HistoToggle>

            <h3 class="fr-h5 fr-mt-3w">Arrêtés</h3>

            <HistoCheckbox
              id="checkbox-map-arretes"
              v-model="sharedState.input.params.mainLeveeUniquement"
              containerClass="fr-mb-2w"
            >
              <template #label>
                Main levée uniquement
              </template>
              <template #help>
                <p>Afficher uniquement les arrêtés ayant fait l'objet d'une main levée.</p>
              </template>
            </HistoCheckbox>

            <AppToggleCheckboxes
              v-if="arreteTypesGroupsForMap[0]"
              id="toggle-map-arretes-0"
              labelPicto="purple-hexagon"
              v-model="sharedState.input.filters.arreteTypes"
              :option-groups="[arreteTypesGroupsForMap[0]]"
            >
              <template #label>{{ arreteTypesGroupsForMap[0].title }}</template>
            </AppToggleCheckboxes>

            <AppToggleCheckboxes
              v-if="arreteTypesGroupsForMap[1]"
              id="toggle-map-arretes-1"
              labelPicto="blue-square"
              v-model="sharedState.input.filters.arreteTypes"
              :option-groups="[arreteTypesGroupsForMap[1]]"
            >
              <template #label>{{ arreteTypesGroupsForMap[1].title }}</template>
            </AppToggleCheckboxes>

            <AppToggleCheckboxes
              v-if="arreteTypesGroupsForMap[2]"
              id="toggle-map-arretes-2"
              labelPicto="purple-diamond"
              v-model="sharedState.input.filters.arreteTypes"
              :option-groups="[arreteTypesGroupsForMap[2]]"
            >
              <template #label>{{ arreteTypesGroupsForMap[2].title }}</template>
            </AppToggleCheckboxes>

            <hr class="fr-mt-5w">

            <h2 class="fr-h4 fr-text-label--blue-france">Filtres supplémentaires</h2>

            <!-- select pour l'instant pour synchro avec vue liste -->
            <HistoSelect
              id="filter-map-nature-parc"
              v-model="sharedState.input.filters.natureParc"
              :option-items="natureParcOptions"
              :placeholder="'Tous'"
              title="Rechercher par nature du parc"
            >
              <template #label>Nature du parc</template>
            </HistoSelect>

            <AppAutoComplete
              id="filter-map-bailleur-ou-syndic"
              v-model="sharedState.input.filters.bailleurOuSyndic"
              :suggestions="sharedState.bailleursAndSyndic"
              :initSelectedSuggestions="sharedState.input.filters.bailleurOuSyndic"
              :placeholder="'Nom du bailleur ou syndicat'"
              title="Nom du bailleur ou syndicat"
              :multiple="false"
              :iconClass="'fr-icon-user-search-fill'"
            >
              <template #label>Bailleur ou syndicat gestionnaire</template>
              <template #hint>Tapez le nom et sélectionnez-le dans la liste</template>
            </AppAutoComplete>
          </div>
        </div>
    </div>

    <button
      type="button"
      class="addresses-history-map-filters__toggle-button"
      :aria-expanded="!isRetracted"
      aria-controls="addresses-history-map-filters-legend addresses-history-map-filters-content"
      :title="isRetracted ? 'Afficher tous les filtres' : 'Masquer les filtres'"
      @click="isRetracted = !isRetracted"
    >
      <span
        class="fr-icon-arrow-down-s-line toggle-icon"
        :class="{ 'toggle-icon--up': !isRetracted }"
        aria-hidden="true"
      ></span>
      <span class="fr-sr-only">{{ isRetracted ? 'Afficher tous les filtres' : 'Masquer les filtres' }}</span>
    </button>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { store } from '../composables/useAddressesHistoryStore'
import { useAddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'
import { useFilteredSuggestions } from '../composables/useFilteredSuggestions'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoToggle from '../../common/HistoToggle.vue'
import HistoCheckbox from '../../common/HistoCheckbox.vue'
import AppAutoComplete from '../../common/AppAutoComplete.vue'
import AppToggleCheckboxes from '../../common/AppToggleCheckboxes.vue'
import type { CheckboxGroup } from '../../common/AppListCheckboxes.types'
import type { ArreteTypesGroup } from '../types'

// State
const sharedState = store.state

// Les filtres sont ouverts par défaut
const isRetracted = ref(false)

// Composable
const filtersComposable = useAddressesHistoryFilters()

// Suggestions d'adresses limitées à la commune sélectionnée (pas de filtre zone dans cette vue)
const { addressesSuggestions } = useFilteredSuggestions({ withZone: false })

/**
 * Convertit un ArreteTypesGroup en CheckboxGroup pour la vue carte
 * Utilise TextInMapView au lieu de Text
 */
const convertToMapViewGroup = (group: ArreteTypesGroup): CheckboxGroup => {
  return {
    title: group.titleInMapView || group.title,
    options: group.options.map(option => ({
      Id: option.Id,
      Text: option.TextInMapView || option.Text
    }))
  }
}

// Groupes convertis pour la vue carte
const arreteTypesGroupsForMap = computed<CheckboxGroup[]>(() => {
  return sharedState.arreteTypesGroups.map(convertToMapViewGroup)
})

/**
 * Quand le territoire change
 * - Réinitialise communes et zone
 * - Recharge les settings
 * - Recharge les adresses
 */
const onTerritoryChange = async (value: string): Promise<void> => {
  sharedState.input.filters.communeOuEpci = undefined
  sharedState.input.filters.zone = undefined
  sharedState.input.filters.territoire = value

  await filtersComposable.reloadSettings()
  await filtersComposable.reloadAddresses()
}

// Computed pour gérer le toggle des dossiers multiples
const dossiersMultiplesToggleValue = computed(() => {
  return sharedState.input.filters.dossiersMultiples === 'oui'
})
const natureParcOptions = computed(() => store.state.natureParcList)

/**
 * Gère le changement du toggle dossiers multiples
 * Si activé : "oui", sinon : null
 */
const onDossiersMultiplesToggle = (value: boolean): void => {
  sharedState.input.filters.dossiersMultiples = value ? 'oui' : undefined
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
  max-width: 460px;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 2px 0px 6px rgba(0, 0, 0, 0.3);
}

.addresses-history-map-filters__scroll {
  max-height: calc(70vh - 6rem);
  overflow-y: auto;
  padding-bottom: 0.75rem;
}

section.addresses-history-map-filters .filters-collapsible {
  display: grid;
  grid-template-rows: 1fr;
  opacity: 1;
  transition: grid-template-rows 0.3s ease, opacity 0.3s ease;
}

section.addresses-history-map-filters .filters-collapsible__inner {
  min-height: 0;
  overflow: hidden;
  visibility: visible;
  transition: visibility 0s;
}

section.addresses-history-map-filters--retracted .filters-collapsible {
  grid-template-rows: 0fr;
  opacity: 0;
}

section.addresses-history-map-filters--retracted .filters-collapsible__inner {
  visibility: hidden;
  transition: visibility 0s linear 0.3s;
}

.addresses-history-map-filters__toggle-button {
  position: absolute;
  left: 50%;
  bottom: 0rem;
  transform: translate(-50%, 50%);
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  border: 0px;
  border-radius: 50%;
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  color: var(--text-action-high-blue-france, #000091);
  cursor: pointer;
}

.addresses-history-map-filters__toggle-button:hover {
  background-color: var(--background-default-grey-hover, #f6f6f6);
}

.addresses-history-map-filters__toggle-button .fr-icon-arrow-down-s-line::before {
  margin: 0;
}

.addresses-history-map-filters__toggle-button .toggle-icon {
  transition: transform 0.3s ease;
}

.addresses-history-map-filters__toggle-button .toggle-icon--up {
  transform: rotate(180deg);
}

@media (prefers-reduced-motion: reduce) {
  section.addresses-history-map-filters .filters-collapsible,
  .addresses-history-map-filters__toggle-button .toggle-icon {
    transition: none;
  }
}
</style>
