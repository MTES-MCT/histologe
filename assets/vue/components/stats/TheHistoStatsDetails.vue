<template>
  <section class="histo-stats-details">
    <div class="fr-container--fluid fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-6 fr-col-lg-3 fr-col-xl-2">
          <div class="stat-general">
            <p>Nb. signalements</p>
            <p>{{ sharedState.stats.countSignalement }}</p>
          </div>
        </div>
        <div class="fr-col-6 fr-col-lg-3 fr-col-xl-2">
          <div class="stat-general">
            <p>Criticité moyenne</p>
            <p>{{ sharedState.stats.averageCriticite }} %</p>
          </div>
        </div>
        <div class="fr-col-6 fr-col-lg-3 fr-col-xl-2">
          <div class="stat-general">
            <p>Délai validation moy.</p>
            <p>{{ sharedState.stats.averageDaysValidation }} jours</p>
          </div>
        </div>
        <div class="fr-col-6 fr-col-lg-3 fr-col-xl-2">
          <div class="stat-general">
            <p>Délai clôture moy.</p>
            <p>{{ sharedState.stats.averageDaysClosure }} jours</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store.js'

export default defineComponent({
  name: 'TheHistoStatsDetails',
  props: {
    onChange: { type: Function }
  },
  components: {
  },
  data () {
    return {
			sharedState: store.state
    }
  }
})
</script>

<style>
  .stat-general {
    text-align: center;
    font-weight: bold;
    background: #FFF;
    padding: 1rem 0.5rem;
  }
  .stat-general p:first-child {
    font-size: 0.8rem;
    color: var(--grey-200-850);
  }
  .stat-general p:last-child {
    font-size: 2rem;
    color: var(--blue-france-sun-113-625);
  }
</style>
