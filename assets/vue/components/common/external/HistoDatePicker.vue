<template>
  <div class="histo-date-picker">
    <Datepicker
      v-model="dates"
      @update:modelValue="handleDate"
      locale="fr"
      range
      multi-calendars
      :enableTimePicker=false
      format="dd/MM/yyyy"
      selectText="Appliquer"
      cancelText="Annuler"
      :ref="id"
      />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Datepicker from '@vuepic/vue-datepicker'

export default defineComponent({
  name: 'HistoDatePicker',
  components: { Datepicker },
  expose: ['updateDate'],
  props: {
    id: { type: String, default: null },
    modelValue: { type: Array, default: [] }
  },
  data() {
    return {
      dates: this.modelValue
    }
  },
  emits: ['update:modelValue'],
  methods: {
    updateDate: function(newDates: Array<Date>) {
      this.dates = newDates
    },
    handleDate: function(modelData: any) {
      if (this.dates !== undefined && this.dates !== null) {
        this.dates = modelData
      }
      this.$emit('update:modelValue', modelData)
    }
  }
})
</script>

<style>
</style>
