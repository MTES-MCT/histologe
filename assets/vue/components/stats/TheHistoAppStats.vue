<template>
  <div
    id="app-stats"
    class="histo-app-stats fr-p-5v"
	  :data-ajaxurl="sharedProps.ajaxurl"
    >
    <HistoBOTabHeader>
      <template #title>Statistiques</template>
    </HistoBOTabHeader>
    <div v-if="loadingInit" class="loading fr-m-10w">
      Initialisation des filtres...
    </div>
    <div v-else>
      <TheHistoStatsFilters :on-change="handleFilterChange" />
      <div v-if="loadingFilters" class="loading fr-m-10w">
        Mise à jour des statistiques
      </div>
      <TheHistoStatsDetails v-else />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import HistoBOTabHeader from '../common/HistoBOTabHeader.vue'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
import TheHistoStatsFilters from './TheHistoStatsFilters.vue'
import TheHistoStatsDetails from './TheHistoStatsDetails.vue'
const initElements:any = document.querySelector('#app-stats')

export default defineComponent({
  name: 'TheHistoAppStats',
  components: {
    HistoBOTabHeader,
    TheHistoStatsFilters,
    TheHistoStatsDetails
  },
  data () {
    return {
			sharedState: store.state,
			sharedProps: store.props,
      loadingInit: true,
      loadingFilters: false
    }
  },
	created () {
    if (initElements !== null) {
		  this.sharedProps.ajaxurl = initElements.dataset.ajaxurl
      this.initDates()
      requests.filter(this.handleRefresh)

    } else {
      alert('Error while loading statistics')
    }
  },
  methods: {
    /**
     * Initialisation de la date de début et de fin de filtres
     */
    initDates () {
      this.sharedState.filters.dateRange = []
      // Par défaut, on prend le semestre précédent
      // Soit la période JAN-JUIN précédente, soit la période JUIL-DEC
    },
    handleFilterChange () {
      console.log('onFilterChange')
      console.log(this.sharedState)

      requests.filter(this.handleRefresh)
      this.loadingFilters = true
    },
    handleRefresh (requestResponse: any) {
      this.sharedState.filters.etiquettesList = []
      for (let id in requestResponse.list_etiquettes) {
        let optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = id
        optionItem.Text = requestResponse.list_etiquettes[id]
        this.sharedState.filters.etiquettesList.push(optionItem)
      }
      this.loadingInit = false
      this.loadingFilters = false
    }
  }
})
</script>

<style>
  .histo-app-stats {
    background-color: var(--background-alt-grey);
  }

  .histo-app-stats a {
    color: var(--blue-france-sun-113-625);
  }

  .loading {
    font-size: 2rem;
  }
</style>
