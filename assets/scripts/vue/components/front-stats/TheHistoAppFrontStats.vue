<template>
  <div
    id="app-front-stats"
    class="histo-app-front-stats fr-pt-5w"
    :data-ajaxurl="sharedProps.ajaxurl"
    >
    <div class="fr-container">
      <h1>{{ sharedProps.platformName }} en quelques chiffres</h1>
    </div>
    <div v-if="loadingInit" class="loading fr-m-10w">
      Initialisation des statistiques...
    </div>

    <div v-else>
      <TheHistoFrontStatsGlobal />

      <div v-if="loadingRefresh" class="loading fr-m-10w">
        Mise à jour des statistiques...
      </div>
      <TheHistoFrontStatsTerritory v-else :on-update-filter="updateFilter" />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import TheHistoFrontStatsGlobal from './TheHistoFrontStatsGlobal.vue'
import TheHistoFrontStatsTerritory from './TheHistoFrontStatsTerritory.vue'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
const initElements:any = document.querySelector('#app-front-stats')

export default defineComponent({
  name: 'TheHistoAppFrontStats',
  components: {
    TheHistoFrontStatsGlobal,
    TheHistoFrontStatsTerritory
  },
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props,
      loadingInit: true,
      loadingRefresh: false
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.sharedProps.platformName = initElements.dataset.platformName
      requests.filter(this.handleRefresh)
    } else {
      alert('Error while loading front statistics')
    }
  },
  methods: {
    updateFilter () {
      this.loadingRefresh = true
      requests.filter(this.handleRefresh)
    },

    /**
     * The query has finished its execution, we refresh the UI
     * @param requestResponse
     */
    async handleRefresh (requestResponse: any) {
      this.refreshFilters(requestResponse)
      this.refreshStats(requestResponse)

      this.loadingInit = false
      this.loadingRefresh = false
    },

    refreshFilters (requestResponse: any) {
      this.sharedState.filters.territoiresList = []
      const optionAllItem = new HistoInterfaceSelectOption()
      optionAllItem.Id = 'all'
      optionAllItem.Text = 'Tous'
      this.sharedState.filters.territoiresList.push(optionAllItem)
      for (const id in requestResponse.list_territoires) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = id
        optionItem.Text = requestResponse.list_territoires[id]
        this.sharedState.filters.territoiresList.push(optionItem)
      }
    },

    refreshStats (requestResponse: any) {
      this.sharedState.stats.countSignalementResolus = requestResponse.count_signalement_resolus
      this.sharedState.stats.countSignalement = requestResponse.count_signalement
      this.sharedState.stats.countTerritory = requestResponse.count_territory
      this.sharedState.stats.percentValidation = requestResponse.percent_validation
      this.sharedState.stats.percentCloture = requestResponse.percent_cloture
      this.sharedState.stats.percentRefused = requestResponse.percent_refused
      this.sharedState.stats.countImported = requestResponse.count_imported
      this.sharedState.stats.countSignalementPerTerritory = requestResponse.signalement_per_territoire
      this.sharedState.stats.countSignalementPerMonth = requestResponse.signalement_per_month
      this.sharedState.stats.countSignalementPerStatut = requestResponse.signalement_per_statut
      this.sharedState.stats.countSignalementPerMotifCloture = requestResponse.signalement_per_motif_cloture
      this.sharedState.stats.countSignalementPerDesordresCategories = requestResponse.signalement_per_desordres_categories
      this.sharedState.stats.countSignalementPerLogementDesordres = requestResponse.signalement_per_logement_desordres
      this.sharedState.stats.countSignalementPerBatimentDesordres = requestResponse.signalement_per_batiment_desordres
      this.sharedState.stats.countSignalementPerMonthThisYear = requestResponse.signalement_per_month_this_year
      this.sharedState.stats.countSignalementPerStatutThisYear = requestResponse.signalement_per_statut_this_year
      this.sharedState.stats.countSignalementPerMotifClotureThisYear = requestResponse.signalement_per_motif_cloture_this_year
      this.sharedState.stats.countSignalementPerDesordresCategoriesThisYear = requestResponse.signalement_per_desordres_categories_this_year
      this.sharedState.stats.countSignalementPerLogementDesordresThisYear = requestResponse.signalement_per_logement_desordres_this_year
      this.sharedState.stats.countSignalementPerBatimentDesordresThisYear = requestResponse.signalement_per_batiment_desordres_this_year
    }
  }
})
</script>

<style>
  .histo-app-front-stats {
    background-color: '#FFF';
  }
  .histo-app-front-stats h1 {
    text-align: left;
    color: var(--blue-france-sun-113-625);
  }
  .iframed .histo-app-front-stats h1 {
    display: none;
  }

  .histo-app-front-stats a {
    color: var(--blue-france-sun-113-625);
  }

  .loading {
    font-size: 2rem;
  }
</style>
