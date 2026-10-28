<template>
  <div :class="[ 'histo-front-stats-territory-item fr-col-12 fr-mb-5w', 'fr-col-md-' + sizeClass ]">
    <div class="fr-p-2w">
      <div class="title"><slot name="title"></slot></div>
      <FrontStatsYearToggle
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        :is-total-active="isTotalActive"
        />
      <div class="clear"></div>
      <!-- Les graphiques ne réagissent pas au changement de données : la clé force leur recréation -->
      <div class="graph" :key="modelValue"><slot name="graph"></slot></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { YearType } from '../types'
import FrontStatsYearToggle from './FrontStatsYearToggle.vue'

withDefaults(defineProps<{
  modelValue: YearType
  sizeClass: string
  isTotalActive?: boolean
}>(), {
  isTotalActive: true
})

// Émissions
const emit = defineEmits<{
  'update:modelValue': [value: YearType]
}>()
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
