<template>
  <div :class="[ 'histo-front-stats-territory-item fr-col-12 fr-mb-5w', 'fr-col-md-' + sizeClass ]">
    <div class="fr-p-2w">
      <div class="title"><slot name="title"></slot></div>
      <TheHistoFrontStatsYearToggle v-model="yearType" :on-change="onYearTypeChange" />
      <div class="clear"></div>
      <div class="graph"><slot name="graph"></slot></div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import TheHistoFrontStatsYearToggle from './TheHistoFrontStatsYearToggle.vue';

export default defineComponent({
    name: "TheHistoFrontStatsTerritoryItem",
    components: { TheHistoFrontStatsYearToggle },
    props: {
        sizeClass: { type: String, default: '' },
        modelValue: { type: String, default: '' },
        onChange: { type: Function }
    },
    emits: ['update:modelValue'],
    data() {
      return {
        yearType: this.modelValue 
      }
    },
    methods: {
      onYearTypeChange(selection: string) {
			  this.$emit('update:modelValue', selection)
        if (this.onChange !== undefined) {
          this.onChange(selection)
        }
      }
    }
})
</script>

<style>
  .histo-front-stats-territory-item > div {
    padding: 8px;
    background-color: #FFF;
  }
  .histo-front-stats-territory-item > div div.title {
    float: left;
    text-transform: uppercase;
    color: var(--blue-france-sun-113-625);
    font-weight: bold;
    font-size: 18px;
  }
  .histo-front-stats-territory-item > div div.histo-front-stats-year-toggle {
    float: right;
  }
  .histo-front-stats-territory-item .clear {
    clear: both;
  }
</style>
