<template>
  <section class="histo-stats-filters">
    <h2 class="fr-h3 fr-mb-0">Statistiques détaillées</h2>
    <p class="fr-mb-5w">
      Modifiez les filtres et / ou la plage de dates pour mettre les données à jour.
      <br>
      Par défaut, les signalements refusés ne sont pas comptabilisés.
    </p>

    <div class="fr-container--fluid">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-communes"
            @update:modelValue="onChange(false)"
            inner-label="Communes"
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect 
            id="filter-statut"
            v-model="sharedState.filters.statut"
            @update:modelValue="onChange(false)"
            inner-label="Statut"
            :option-items=statusList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-etiquettes"
            v-model="sharedState.filters.etiquettes"
            @update:modelValue="onChange(false)"
            inner-label="Etiquettes"
            :option-items=sharedState.filters.etiquettesList
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-type"
            v-model="sharedState.filters.type"
            @update:modelValue="onChange(false)"
            inner-label="Type"
            :option-items=typesList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
          <HistoDatePicker
            v-model="sharedState.filters.dateRange"
            @update:modelValue="onChange(false)"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-5">
          <HistoCheckbox
            id="count-refused"
            v-model="sharedState.filters.countRefused"
            @update:modelValue="onChange(false)"
            >
            <template #label>Cocher la case pour comptabiliser les signalements refusés</template>
          </HistoCheckbox>
        </div>
        <div class="fr-col-12 fr-col-lg-12 fr-col-xl-3">
          <a href="#" @click="onReinitLocalEvent">Tout réinitialiser</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoSelect from '../common/HistoSelect.vue'
import HistoCheckbox from '../common/HistoCheckbox.vue'
import HistoDatePicker from '../common/external/HistoDatePicker.vue'

export default defineComponent({
  name: 'TheHistoStatsFilters',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoSelect,
    HistoCheckbox,
    HistoDatePicker
  },
  data () {
    return {
			sharedState: store.state,
      initFilters: {
        territoire: store.state.filters.territoire,
        communes: store.state.filters.communes,
        statut: store.state.filters.statut,
        etiquettes: store.state.filters.etiquettes,
        type: store.state.filters.type,
        dateRange: store.state.filters.dateRange,
        countRefused: store.state.filters.countRefused
      },
      statusList: [
        { Id: 'all', Text: 'Tous' },
        { Id: 'new', Text: 'Nouveau' },
        { Id: 'active', Text: 'En cours' },
        { Id: 'closed', Text: 'Fermé' }
      ],
      typesList: [
        { Id: 'all', Text: 'Tous' },
        { Id: 'public', Text: 'Public' },
        { Id: 'private', Text: 'Privé' },
        { Id: 'unset', Text: 'Non renseigné' }
      ]
    }
  },
	methods: {
		onReinitLocalEvent () {
      this.sharedState.filters.territoire = this.initFilters.territoire
      this.sharedState.filters.communes = this.initFilters.communes
      this.sharedState.filters.statut = this.initFilters.statut
      this.sharedState.filters.etiquettes = this.initFilters.etiquettes
      this.sharedState.filters.type = this.initFilters.type
      this.sharedState.filters.dateRange = this.initFilters.dateRange // TODO: reinit in component
      this.sharedState.filters.countRefused = this.initFilters.countRefused

      if (this.onChange !== undefined) {
			  this.onChange()
      }
		}
	}
})
</script>
