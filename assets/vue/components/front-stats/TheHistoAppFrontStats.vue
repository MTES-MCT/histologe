<template>
  <div
    id="app-front-stats"
    class="histo-app-front-stats fr-pt-5w"
	  :data-ajaxurl="sharedProps.ajaxurl"
    >
    <h1>Histologe en quelques chiffres</h1>
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
      requests.filter(this.handleRefresh)

    } else {
      alert('Error while loading front statistics')
    }
  },
  methods: {
    updateFilter() {
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
      let optionAllItem = new HistoInterfaceSelectOption()
      optionAllItem.Id = 'all'
      optionAllItem.Text = 'Tous'
      this.sharedState.filters.territoiresList.push(optionAllItem)
      for (let id in requestResponse.list_territoires) {
        let optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = id
        optionItem.Text = requestResponse.list_territoires[id]
        this.sharedState.filters.territoiresList.push(optionItem)
      }
    },

    refreshStats (requestResponse: any) {
      this.sharedState.stats.countSignalement = requestResponse.count_signalement
      this.sharedState.stats.countTerritory = requestResponse.count_territory
      this.sharedState.stats.percentValidation = requestResponse.percent_validation
      this.sharedState.stats.percentCloture = requestResponse.percent_cloture
      this.sharedState.stats.countSignalementPerTerritory = requestResponse.signalement_per_territoire
      this.sharedState.stats.countSignalementPerMonth = requestResponse.signalement_per_month
      this.sharedState.stats.countSignalementPerStatut = requestResponse.signalement_per_statut
      this.sharedState.stats.countSignalementPerSituation = requestResponse.signalement_per_situation
      this.sharedState.stats.countSignalementPerMotifCloture = requestResponse.signalement_per_motif_cloture
      this.sharedState.stats.countSignalementPerMonthThisYear = requestResponse.signalement_per_month_this_year
      this.sharedState.stats.countSignalementPerStatutThisYear = requestResponse.signalement_per_statut_this_year
      this.sharedState.stats.countSignalementPerSituationThisYear = requestResponse.signalement_per_situation_this_year
      this.sharedState.stats.countSignalementPerMotifClotureThisYear = requestResponse.signalement_per_motif_cloture_this_year
    }
  }
})
</script>

<style>
  .histo-app-front-stats {
    background-color: '#FFF';
  }
  .histo-app-front-stats h1 {
    text-align: center;
    color: var(--blue-france-sun-113-625);
  }

  .histo-app-stats a {
    color: var(--blue-france-sun-113-625);
  }

  .loading {
    font-size: 2rem;
  }
</style>
