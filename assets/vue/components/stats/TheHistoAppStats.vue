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
      <TheHistoStatsGlobal :on-change="handleFilterChange" />
      <TheHistoStatsFilters :on-change="handleFilterChange" />
      <div v-if="loadingFilters" class="loading fr-m-10w">
        Mise à jour des statistiques...
        <br><br><br><br><br><br><br><br><br><br><br><br>
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
import TheHistoStatsGlobal from './TheHistoStatsGlobal.vue'
import TheHistoStatsFilters from './TheHistoStatsFilters.vue'
import TheHistoStatsDetails from './TheHistoStatsDetails.vue'
const initElements:any = document.querySelector('#app-stats')

export default defineComponent({
  name: 'TheHistoAppStats',
  components: {
    HistoBOTabHeader,
    TheHistoStatsGlobal,
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
     * Initializes the start and end date used in the filters
     */
    initDates () {
      // Default value: last 6 months
      // Starts on the first day of 6 months before
      const date = new Date()
      const prevMonth = date.getMonth() - 6
      const firstDay = 1
      const startDate = new Date(date.getFullYear(), prevMonth, firstDay)
      // Ends on the last day of the previous month
      const endDate = new Date()
      endDate.setDate(0)
      endDate.setHours(23)
      endDate.setMinutes(59)
      endDate.setSeconds(59)

      this.sharedState.filters.dateRange = [startDate, endDate]
    },

    /**
     * One of the filters has changed, a query needs to be executed
     */
    handleFilterChange (reinit:boolean) {
      requests.filter(this.handleRefresh)
      this.loadingInit = reinit
      this.loadingFilters = true
    },

    /**
     * The query has finished its execution, we refresh the UI
     * @param requestResponse
     */
    async handleRefresh (requestResponse: any) {
      this.refreshFilters(requestResponse)
      this.refreshStats(requestResponse)

      const wasInit = this.loadingInit
      this.loadingInit = false
      this.loadingFilters = false

      await this.$nextTick()

      let el = document.getElementById('histo-stats-filters')
      if (wasInit) {
        el = document.getElementById('app-stats')
      }
      if (el) {
        window.scrollTo({
          top: el.offsetTop,
          left: 0,
          behavior: 'smooth'
        })
      }
    },

    refreshFilters (requestResponse: any) {
      this.sharedState.filters.etiquettesList = []
      for (const id in requestResponse.list_etiquettes) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = id
        optionItem.Text = requestResponse.list_etiquettes[id]
        this.sharedState.filters.etiquettesList.push(optionItem)
      }

      this.sharedState.filters.communesList = []
      for (const id in requestResponse.list_communes) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = id
        optionItem.Text = requestResponse.list_communes[id]
        this.sharedState.filters.communesList.push(optionItem)
      }

      this.sharedState.filters.canFilterTerritoires = requestResponse.can_filter_territoires === '1'
      if (this.sharedState.filters.canFilterTerritoires) {
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
      }

      this.sharedState.filters.canSeePerPartenaire = requestResponse.can_see_per_partenaire === '1'
    },

    refreshStats (requestResponse: any) {
      this.sharedState.stats.countSignalement = requestResponse.count_signalement
      this.sharedState.stats.averageCriticite = requestResponse.average_criticite
      this.sharedState.stats.averageDaysValidation = requestResponse.average_days_validation
      this.sharedState.stats.averageDaysClosure = requestResponse.average_days_closure

      this.sharedState.stats.countSignalementFiltered = requestResponse.count_signalement_filtered
      this.sharedState.stats.averageCriticiteFiltered = requestResponse.average_criticite_filtered

      this.sharedState.stats.countSignalementPerMonth = requestResponse.countSignalementPerMonth

      this.makePartenaireArray(requestResponse.countSignalementPerPartenaire)
      this.sharedState.stats.countSignalementPerSituation = requestResponse.countSignalementPerSituation
      this.sharedState.stats.countSignalementPerCriticite = requestResponse.countSignalementPerCriticite

      this.sharedState.stats.countSignalementPerStatut = requestResponse.countSignalementPerStatut
      this.sharedState.stats.countSignalementPerCriticitePercent = requestResponse.countSignalementPerCriticitePercent
      this.sharedState.stats.countSignalementPerVisite = requestResponse.countSignalementPerVisite
    },

    makePartenaireArray (countSignalementPerPartenaire: Object) {
      this.sharedState.stats.countSignalementPerPartenaire = []
      for (const [nomPartenaire, dataPartenaire] of Object.entries(countSignalementPerPartenaire)) {
        const item = [
          nomPartenaire,
          dataPartenaire.total,
          dataPartenaire.wait + ' (' + dataPartenaire.wait_percent + ' %)',
          dataPartenaire.accepted + ' (' + dataPartenaire.accepted_percent + ' %)',
          dataPartenaire.closed + ' (' + dataPartenaire.closed_percent + ' %)'
        ]
        this.sharedState.stats.countSignalementPerPartenaire.push(item)
      }
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
