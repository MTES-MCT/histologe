<template>
  <div class="histo-stats-details-item fr-col-6 fr-col-lg-3 fr-col-xl-2">
    <div :class="[ 'color-' + color ]">
      <p><slot name="title"></slot></p>
      <p>{{ data }}</p>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TheHistoStatsDetailsItem',
  props: {
    data: String,
    color: String
  }
})
</script>

<style>
  .histo-stats-details-item div {
    text-align: center;
    font-weight: bold;
    background: #FFF;
    padding: 1rem 0.5rem;
    border: 1px solid var(--border-default-grey);
  }
  .histo-stats-details-item div p:first-child {
    font-size: 0.8rem;
    color: var(--grey-200-850);
  }
  .histo-stats-details-item div p:last-child {
    font-size: 2rem;
    color: var(--blue-france-sun-113-625);
  }
  .histo-stats-details-item div.color-purple p:last-child {
    color: var(--purple-glycine-main-494);
  }
</style>
