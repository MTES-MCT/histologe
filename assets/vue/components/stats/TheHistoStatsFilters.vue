<template>
  <section class="histo-stats-filters">
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
            @update:modelValue="onChange"
            inner-label="Communes"
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect 
            id="filter-statut"
            v-model="sharedState.filters.statut"
            @update:modelValue="onChange"
            inner-label="Statut"
            :option-items=statusList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-etiquettes"
            @update:modelValue="onChange"
            inner-label="Etiquettes"
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-type"
            v-model="sharedState.filters.type"
            @update:modelValue="onChange"
            inner-label="Type"
            :option-items=typesList
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
          Sélecteur de dates
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-5">
          <HistoCheckbox
            id="count-refused"
            v-model="sharedState.filters.countRefused"
            @update:modelValue="onChange"
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
import { store } from './store.js'
import HistoSelect from '../common/HistoSelect.vue'
import HistoCheckbox from '../common/HistoCheckbox.vue'

export default defineComponent({
  name: 'TheHistoStatsFilters',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoSelect,
    HistoCheckbox
  },
  data () {
    return {
			sharedState: store.state,
      initFilters: {
        communes: store.state.filters.communes,
        statut: store.state.filters.statut,
        etiquette: store.state.filters.etiquette,
        type: store.state.filters.type,
        startDate: store.state.filters.startDate,
        endDate: store.state.filters.endDate,
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
      this.sharedState.filters.communes = this.initFilters.communes
      this.sharedState.filters.statut = this.initFilters.statut
      this.sharedState.filters.etiquette = this.initFilters.etiquette
      this.sharedState.filters.type = this.initFilters.type
      this.sharedState.filters.startDate = this.initFilters.startDate
      this.sharedState.filters.endDate = this.initFilters.endDate
      this.sharedState.filters.countRefused = this.initFilters.countRefused

      if (this.onChange !== undefined) {
			  this.onChange()
      }
		}
	}
})
</script>
