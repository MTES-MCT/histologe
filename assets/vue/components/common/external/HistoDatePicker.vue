<template>
  <div class="histo-date-picker">
    <Datepicker
      v-model="modelValue"
      @update:modelValue="handleDate"
      locale="fr"
      range
      multi-calendars
      :enableTimePicker=false
      format="dd/MM/yyyy"
      selectText="Appliquer"
      cancelText="Annuler"
      />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import Datepicker from '@vuepic/vue-datepicker'

export default defineComponent({
  name: 'HistoDatePicker',
  components: { Datepicker },
	props: {
		id: { type: String, default: null },
    modelValue: { type: Array, default: [] }
  },
  data() {
    return {
      date: { target: { values: [] }}
    }
  },
  emits: ['update:modelValue'],
  methods: {
    handleDate: function(modelData: any) {
      console.log('handleDate')
      console.log(modelData)
      console.log(this.date)
      if (this.date !== undefined && this.date !== null) {
        this.date.target.values = modelData
      }
      this.$emit('update:modelValue', modelData)
    }
  }
})
</script>

<style>
</style>
