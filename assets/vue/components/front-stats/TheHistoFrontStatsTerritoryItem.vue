<template>
  <div :class="[ 'histo-front-stats-territory-item fr-col-12 fr-mb-5w', 'fr-col-md-' + sizeClass ]">
    <div>
      <div class="title"><slot name="title"></slot></div>
      <div class="year-switcher"></div>
      <div class="graph"><slot name="graph"></slot></div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TheHistoFrontStatsTerritoryItem',
  props: {
    data: String,
    sizeClass: String
  }
})
</script>

<style>
  .histo-front-stats-territory-item > div {
    padding: 8px;
    background-color: #FFF;
  }
  .histo-front-stats-territory-item > div div.title {
    text-transform: uppercase;
    color: var(--blue-france-sun-113-625);
    font-weight: bold;
    font-size: 18px;
  }
</style>
