<template>
  <div class="histo-front-stats-global">
    <div class="fr-container--fluid fr-px-5w fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-lg-3">
          <TheHistoFrontStatsDetailsItem :data="strCountSignalement">
            <template #title>signalements enregistrés</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strCountTerritory">
            <template #title>territoires déployés</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strPercentValidated">
            <template #title>taux de prise en charge</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strPercentClosed">
            <template #title>taux de clôture des signalements</template>
          </TheHistoFrontStatsDetailsItem>
        </div>
        <div class="fr-col-12 fr-col-lg-9">
          Carte<br><br><br>
          <div v-for="territoryStat in sharedState.stats.countSignalementPerTerritory">
            {{ territoryStat.name }} ({{ territoryStat.zip}}) : {{ territoryStat.count }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import TheHistoFrontStatsDetailsItem from './TheHistoFrontStatsDetailsItem.vue'

export default defineComponent({
  name: 'TheHistoFrontStatsGlobal',
  components: {
    TheHistoFrontStatsDetailsItem
  },
  props: {
    data: String
  },
  data () {
    return {
			sharedState: store.state,
    }
  },
  computed: {
    strCountSignalement () {
      return this.sharedState.stats.countSignalement.toString()
    },
    strCountTerritory () {
      return this.sharedState.stats.countTerritory.toString()
    },
    strPercentValidated () {
      return this.sharedState.stats.percentValidation.toString() + ' %'
    },
    strPercentClosed () {
      return this.sharedState.stats.percentCloture.toString() + ' %'
    }
  }
})
</script>

<style>
</style>
