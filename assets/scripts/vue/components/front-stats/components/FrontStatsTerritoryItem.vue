<template>
  <div :class="[ 'front-stats-territory-item fr-col-12 fr-mb-5w', 'fr-col-md-' + sizeClass ]">
    <div class="fr-p-2w">
      <h3 :id="titleId" class="title fr-h6 title-blue-france"><slot name="title"></slot></h3>
      <FrontStatsYearToggle
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        :is-total-active="isTotalActive"
        />
      <div class="clear"></div>
      <!-- Les graphiques ne réagissent pas au changement de données : la clé force leur recréation -->
      <div class="graph" :key="modelValue"><slot name="graph" :title-id="titleId"></slot></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useId } from 'vue'
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

// Identifiant du titre, qui sert de libellé au graphique et à son tableau de données
const titleId = useId()
</script>

<style>
  .front-stats-territory-item > div {
    padding: 8px;
    background-color: #FFF;
  }
  .front-stats-territory-item > div > .title {
    text-transform: uppercase;
  }
  .front-stats-territory-item > div div.front-stats-year-toggle {
    float: right;
  }
  .front-stats-territory-item .clear {
    clear: both;
  }
</style>
