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
            :on-select=onChange
            inner-label="Communes"
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect 
            id="filter-statut"
            :on-select=onChange
            inner-label="Statut"
            :option-items=statusList
            :value=sharedState.filters.statut
            v-bind:valueReturn.sync="sharedState.filters.statut"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-etiquettes"
            :on-select=onChange
            inner-label="Etiquettes"
            :multiselect=true
            />
        </div>
        <div class="fr-col-12 fr-col-lg-3">
          <HistoSelect
            id="filter-type"
            :on-select=onChange
            inner-label="Type"
            :option-items=typesList
            :value=sharedState.filters.type
            v-bind:valueReturn.sync="sharedState.filters.type"
            />
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-4">
          Sélecteur de dates
        </div>
        <div class="fr-col-12 fr-col-lg-6 fr-col-xl-5">
          Cocher la case pour comptabiliser les signalements refusés
        </div>
        <div class="fr-col-12 fr-col-lg-12 fr-col-xl-3">
          <a href="#">Lien pour réinitialiser</a>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store.js'
import HistoSelect from '../common/HistoSelect.vue'

export default defineComponent({
  name: 'TheHistoStatsFilters',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoSelect
  },
  data () {
    return {
			sharedState: store.state,
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
  }
})
</script>
