<template>
  <div class="histo-app-stats fr-p-5v">
    <HistoBOTabHeader>
      <template #title>Statistiques</template>
    </HistoBOTabHeader>
    <HistoStatsFilters />
  </div>
</template>

<script lang="ts">
import HistoBOTabHeader from '../common/HistoBOTabHeader.vue'
import HistoStatsFilters from './HistoStatsFilters.vue'

export default {
  name: 'HistoAppStats',
  components: {
    HistoBOTabHeader,
    HistoStatsFilters
  }
}
</script>

<style>
  .histo-app-stats {
    background-color: var(--background-alt-grey);
  }
</style>