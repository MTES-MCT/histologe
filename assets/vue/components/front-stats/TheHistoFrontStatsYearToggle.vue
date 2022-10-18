<template>
  <div class="histo-front-stats-year-toggle">
    <div>
      <button :class="classSelectedYear" @click="handleToggle('year')">{{ this.year }}</button>
      <button :class="classSelectedAll" @click="handleToggle('all')">Total</button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'TheHistoFrontStatsYearToggle',
  props: {
    modelValue: { type: String, default: '' },
    onChange: { type: Function }
  },
  data () {
    const year = new Date().getFullYear()
    return {
      year,
      selection: this.modelValue
    }
  },
  emits: ['update:modelValue'],
  methods: {
    handleToggle (selection:string) {
      if (this.selection !== selection) {
        this.selection = selection
        this.$emit('update:modelValue', selection)
        if (this.onChange !== undefined) {
          this.onChange(selection)
        }
      }
    }
  },
  computed: {
    classSelectedYear () {
      return this.selection === 'year' ? 'selected' : ''
    },
    classSelectedAll () {
      return this.selection === 'all' ? 'selected' : ''
    }
  }
})
</script>

<style>
  .histo-front-stats-year-toggle button {
    font-size: 16px;
    color: var(--grey-50-1000);
    border: 1px solid var(--blue-france-sun-113-625);
    background-color: #FFFFFF;
    cursor: pointer;
  }
  .histo-front-stats-year-toggle button.selected {
    color: #FFFFFF;
    background-color: var(--blue-france-sun-113-625);
  }

  .histo-front-stats-year-toggle button:first-child {
    border-radius: 4px 0px 0px 4px;
  }
  .histo-front-stats-year-toggle button:last-child {
    border-radius: 0px 4px 4px 0px;
  }
</style>
