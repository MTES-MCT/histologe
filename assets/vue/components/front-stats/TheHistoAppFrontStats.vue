<template>
  <div
    id="app-front-stats"
    class="histo-app-stats fr-p-5v"
	  :data-ajaxurl="sharedProps.ajaxurl"
    >
    <h1>Histologe en quelques chiffres</h1>
    <div v-if="loadingInit" class="loading fr-m-10w">
      Initialisation des statistiques...
    </div>
    <div v-else>
      Coucou !
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
const initElements:any = document.querySelector('#app-front-stats')

export default defineComponent({
  name: 'TheHistoAppFrontStats',
  components: {
  },
  data () {
    return {
			sharedState: store.state,
			sharedProps: store.props,
      loadingInit: true,
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
