<template>
  <section class="histo-stats-global">
    <h2 class="fr-h3 fr-mb-5v">Statistiques globales</h2>

    <div class="fr-container--fluid filter-territories fr-mb-5v" v-if="sharedState.filters.canFilterTerritoires">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-hidden fr-displayed-md fr-col-md-9">
        </div>
        <div class="fr-col-12 fr-col-md-3">
          <HistoSelect
            id="filter-territoires"
            v-model="sharedState.filters.territoire"
            @update:modelValue="onChange(true)"
            inner-label="Territoire"
            :option-items=sharedState.filters.territoiresList
            />
        </div>
      </div>
    </div>

    <p class="fr-mb-5w">
      Les statistiques globales comprennent l'intégralité de vos signalements, depuis la mise en place de la plateforme.
    </p>

    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <TheHistoStatsDetailsItem :data=strCountSignalement color="purple">
          <template #title>Nb. signalements</template>
        </TheHistoStatsDetailsItem>

        <TheHistoStatsDetailsItem :data=strAverageCriticite color="purple">
          <template #title>Criticité moyenne</template>
        </TheHistoStatsDetailsItem>

        <TheHistoStatsDetailsItem :data=strAverageDaysValidation color="purple">
          <template #title>Délai validation moy.</template>
        </TheHistoStatsDetailsItem>

        <TheHistoStatsDetailsItem :data=strAverageDaysClosure color="purple">
          <template #title>Délai clôture moy.</template>
        </TheHistoStatsDetailsItem>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import HistoSelect from '../common/HistoSelect.vue'
import TheHistoStatsDetailsItem from './TheHistoStatsDetailsItem.vue'

export default defineComponent({
  name: 'TheHistoStatsFilters',
  props: {
    onChange: { type: Function }
  },
  components: {
    HistoSelect,
    TheHistoStatsDetailsItem
  },
  data () {
    return {
			sharedState: store.state
    }
  },
  computed: {
    strCountSignalement () {
      let countSignalement:string = this.sharedState.stats.countSignalement != undefined ? this.sharedState.stats.countSignalement.toString() : '0'
      return countSignalement
    },
    strAverageCriticite () {
      let averageCriticite:string = this.sharedState.stats.averageCriticite != undefined ? this.sharedState.stats.averageCriticite.toString() : '-'
      return averageCriticite + ' %'
    },
    strAverageDaysValidation () {
      let averageDaysValidation:string = this.sharedState.stats.averageDaysValidation != undefined ? this.sharedState.stats.averageDaysValidation.toString() : '-'
      return averageDaysValidation + ' jours'
    },
    strAverageDaysClosure () {
      let averageDaysClosure:string = this.sharedState.stats.averageDaysClosure != undefined ? this.sharedState.stats.averageDaysClosure.toString() : '-'
      return averageDaysClosure + ' jours'
    }
  }
})
</script>

<style>
  @media (min-width: 768px) {
    .filter-territories {
      margin-top: -65px;
    }
  }

  .loading {
    font-size: 2rem;
  }
</style>
