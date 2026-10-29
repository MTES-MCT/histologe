<template>
  <div class="front-stats-year-toggle">
    <div>
      <button type="button" :class="{ selected: modelValue === 'year' }" @click="select('year')">{{ currentYear }}</button>
      <button v-if="isTotalActive" type="button" :class="{ selected: modelValue === 'all' }" @click="select('all')">Total</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { YearType } from '../types'

const props = withDefaults(defineProps<{
  modelValue: YearType
  isTotalActive?: boolean
}>(), {
  isTotalActive: true
})

// Émissions
const emit = defineEmits<{
  'update:modelValue': [value: YearType]
}>()

const currentYear = new Date().getFullYear()

const select = (yearType: YearType): void => {
  if (yearType !== props.modelValue) {
    emit('update:modelValue', yearType)
  }
}
</script>

<style>
  .front-stats-year-toggle button {
    font-size: 16px;
    color: var(--grey-50-1000);
    border: 1px solid var(--blue-france-sun-113-625);
    background-color: #FFFFFF;
    cursor: pointer;
  }
  .front-stats-year-toggle button.selected {
    color: #FFFFFF;
    background-color: var(--blue-france-sun-113-625);
  }

  .front-stats-year-toggle button:first-child {
    border-radius: 4px 0px 0px 4px;
  }
  .front-stats-year-toggle button:last-child {
    border-radius: 0px 4px 4px 0px;
  }
</style>
