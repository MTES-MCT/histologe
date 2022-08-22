<template>
  <div class="histo-app-stats fr-p-5v">
    <HistoBOTabHeader>
      <template #title>Statistiques</template>
    </HistoBOTabHeader>
    <TheHistoStatsFilters :on-change="onFilterChange" />
    <TheHistoStatsDetails />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store.js'
import HistoBOTabHeader from '../common/HistoBOTabHeader.vue'
import TheHistoStatsFilters from './TheHistoStatsFilters.vue'
import TheHistoStatsDetails from './TheHistoStatsDetails.vue'

export default defineComponent({
  name: 'TheHistoAppStats',
  components: {
    HistoBOTabHeader,
    TheHistoStatsFilters,
    TheHistoStatsDetails
  },
  data () {
    return {
			sharedState: store.state
    }
  },
  methods: {
    onFilterChange () {
      console.log('onFilterChange')
      console.log(this.sharedState)
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
</style>
