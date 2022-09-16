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
      <TheHistoFrontStatsTerritory />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import TheHistoFrontStatsGlobal from './TheHistoFrontStatsGlobal.vue'
import TheHistoFrontStatsTerritory from './TheHistoFrontStatsTerritory.vue'
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
      loadingInit: false,
    }
  },
	created () {
    if (initElements !== null) {
		  this.sharedProps.ajaxurl = initElements.dataset.ajaxurl

    } else {
      alert('Error while loading front statistics')
    }
  },
  methods: {
    /**
     * The query has finished its execution, we refresh the UI
     * @param requestResponse 
     */
    async handleRefresh (requestResponse: any) {
      this.refreshStats(requestResponse)

      const wasInit = this.loadingInit
      this.loadingInit = false
    },

    refreshStats (requestResponse: any) {
      
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
