<template>
  <div class="histo-front-stats-global fr-container">
    <div class="fr-container--fluid fr-px-5w fr-my-10v">
      <div class="fr-grid-row fr-grid-row--gutters">
        <div class="fr-col-12 fr-col-lg-3">
          <TheHistoFrontStatsDetailsItem :data="strCountSignalementResolus">
            <template #title>foyers sortis du mal-logement</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strCountSignalement">
            <template #title>signalements enregistrés</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strCountTerritory">
            <template #title>territoires déployés</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strPercentValidated">
            <template #title>taux de dossiers traités ou en cours de traitement</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strPercentClosed">
            <template #title>taux de clôture des signalements</template>
          </TheHistoFrontStatsDetailsItem>
          <TheHistoFrontStatsDetailsItem :data="strPercentRefused">
            <template #title>taux de signalements refusés</template>
          </TheHistoFrontStatsDetailsItem>
        </div>
        <div class="fr-col-12 fr-col-lg-9">
          <HistoFranceMap :data=sharedState.stats.countSignalementPerTerritory />
        </div>
      </div>
    </div>

    <hr>

    <div class="fr-mt-5w fr-mb-7w imported-data">
      <h2>Données historiques</h2>
      <p>
        <span>{{strCountImported}}</span> signalements importés
        <br>
        Ces données correspondent aux signalements recensés sur les territoires avant le déploiement de la plateforme {{ sharedProps.platformName }}.
      </p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import TheHistoFrontStatsDetailsItem from './TheHistoFrontStatsDetailsItem.vue'
import HistoFranceMap from '../common/HistoFranceMap.vue'

export default defineComponent({
  name: 'TheHistoFrontStatsGlobal',
  components: {
    TheHistoFrontStatsDetailsItem,
    HistoFranceMap
  },
  props: {
    data: String
  },
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props
    }
  },
  computed: {
    strCountSignalementResolus () {
      return this.sharedState.stats.countSignalementResolus.toString()
    },
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
    },
    strPercentRefused () {
      return this.sharedState.stats.percentRefused.toString() + ' %'
    },
    strCountImported () {
      return this.sharedState.stats.countImported.toString()
    }
  }
})
</script>

<style>
.imported-data span {
  font-weight: bold;
  color: var(--blue-france-sun-113-625);
}
</style>
