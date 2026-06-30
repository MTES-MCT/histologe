<template>
    <section>
        <div class="fr-grid-row fr-grid-row--gutters">
            <div 
                v-if="(sharedState.user.isAdmin || sharedState.user.isMultiTerritoire) && sharedState.territories.length > 0"
                class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w"
                >
                <HistoSelect
                    id="filter-territoire"
                    v-model="sharedState.input.filters.territoire"
                    @update:modelValue="updateTerritory"
                    title="Rechercher par territoire"
                    :option-items=sharedState.territories
                    :placeholder="'Tous'"
                    >
                        <template #label>Territoire</template>
                </HistoSelect>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <AppSearch
                    id="filter-search-terms"
                    v-model="sharedState.input.filters.adresse"
                    :placeholder="'Taper l\'adresse'"
                    title="Taper l'adresse"
                    :minLengthSearch=3
                    @update:modelValue="onChange(false)"
                    >
                        <template #label>Adresse</template>
                </AppSearch>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <AppAutoComplete
                    id="filter-communes"
                    v-model="sharedState.input.filters.communes"
                    :suggestions="sharedState.communes"
                    :initSelectedSuggestions="sharedState.input.filters.communes"
                    :placeholder="'Commune ou code postal'"
                    title="Commune ou code postal"
                    :multiple="true"
                    @update:modelValue="onChange(false)"
                    :reset="reset"
                    :iconClass="'fr-icon-map-pin-2-line'"
                    >
                        <template #label>Commune</template>
                </AppAutoComplete>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <AppAutoComplete
                    id="filter-bailleur-ou-syndic"
                    v-model="sharedState.input.filters.bailleurOuSyndic"
                    :suggestions="sharedState.bailleursAndSyndic"
                    :initSelectedSuggestions="sharedState.input.filters.bailleurOuSyndic"
                    :placeholder="'Nom du bailleur ou syndic'"
                    title="Nom du bailleur ou syndic"
                    :multiple="true"
                    @update:modelValue="onChange(false)"
                    :reset="reset"
                    :iconClass="'fr-icon-user-search-fill'"
                    >
                        <template #label>Bailleur ou syndic gestionnaire</template>
                </AppAutoComplete>
            </div>

            <div
                v-if="sharedState.zones.length > 0"
                class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w"
                >
                <HistoMultiSelect
                    id="filter-zones"
                    v-model="sharedState.input.filters.zones"
                    @update:modelValue="onChange(false)"
                    :option-items=sharedState.zones
                    title="Rechercher par zones"
                    :active=true
                    >
                    <template #label>Zones</template>
                </HistoMultiSelect>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <HistoSelect
                    id="filter-nature-parc"
                    v-model="sharedState.input.filters.natureParc"
                    @update:modelValue="onChange(false)"
                    :option-items=natureParcOptions
                    :placeholder="'Tous'"
                    title="Rechercher par nature du parc"
                    >
                    <template #label>Nature du parc</template>
                </HistoSelect>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <HistoSelect
                    id="filter-dossiers-multiples"
                    v-model="sharedState.input.filters.dossiersMultiples"
                    @update:modelValue="onChange(false)"
                    :option-items=dossiersMultiplesOptions
                    :placeholder="'Tout'"
                    title="Rechercher si dossiers multiples à l'adresse"
                    >
                    <template #label>Dossiers multiples à l'adresse</template>
                </HistoSelect>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-mb-1v fr-mb-md-2w">
                <HistoMultiSelect
                    id="filter-types-arretes"
                    v-model="sharedState.input.filters.typesArretes"
                    @update:modelValue="onChange(false)"
                    :option-items=sharedState.typesArretes
                    title="Rechercher par types d'arrêtés"
                    :active=true
                    >
                    <template #label>Types d'arrêtés</template>
                </HistoMultiSelect>
            </div>

            <div class="fr-col-12 fr-col-md-3 fr-pt-0w fr-pt-md-5w fr-grid-row--middle">
                <button
                    @click="resetFilters"
                    class="fr-link fr-link--icon-left fr-icon-close-circle-line fr-text--sm"
                    >Réinitialiser les résultats</button>
            </div>
        </div>
    </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from '../store'
import { handleSettings, handleTerritoryChange } from '../utils/appUtils'
import HistoSelect from '../../common/HistoSelect.vue'
import HistoMultiSelect from '../../common/HistoMultiSelect.vue'
import AppSearch from '../../common/AppSearch.vue'
import AppAutoComplete from '../../common/AppAutoComplete.vue'

export default defineComponent({
  name: 'AddressesHistoryListFilters',
  components: {
    HistoSelect,
    HistoMultiSelect,
    AppSearch,
    AppAutoComplete
  },
  emits: ['territoryChange', 'filtersChange'],
  data () {
    return {
      reset: false,
      sharedState: store.state,
      sharedProps: store.props,
      natureParcOptions: store.state.natureParcList,
      dossiersMultiplesOptions: store.state.dossiersMultiplesList
    }
  },
  computed: {
  },
  methods: {
    onChange(refresh: boolean) {
      console.log('onChange')
      this.$emit('filtersChange', refresh)
    },
    updateTerritory (value: any) {
      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
      handleTerritoryChange(this, value)
    },
    // Callback function for handleTerritoryChange
    handleSettings (requestResponse: any) {
      handleSettings(this, requestResponse)
    },
    resetFilters () {
      console.log('resetFilters')
      this.sharedState.input.filters = {
        territoire: undefined,
        adresse: undefined,
        communes: [],
        bailleurOuSyndic: undefined,
        zones: [],
        natureParc: undefined,
        dossiersMultiples: undefined,
        typesArretes: [],
      }
      this.sharedState.currentTerritoryId = ''

      this.reset = !this.reset

      if (typeof this.onChange === 'function') {
        this.onChange(false)
      }
    }
  }
})
</script>
<style>
</style>
