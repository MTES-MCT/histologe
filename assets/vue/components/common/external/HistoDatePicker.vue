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
  .dp__instance_calendar > .dp__flex_display > .dp__instance_calendar::before {
    display: block;
    padding: 4px 0px;
    text-align: center;
    background: #CACAFB;
  }
  .dp__instance_calendar > .dp__flex_display > .dp__instance_calendar:first-child::before {
    content: "Date de début";
  }
  .dp__instance_calendar > .dp__flex_display > .dp__instance_calendar:last-child::before {
    content: "Date de fin";
    border-left: 10px solid #FFF;
  }
  .dp__calendar_item .dp__range_start, .dp__calendar_item .dp__range_end {
    color: #333;
    background: #CACAFB;
  }

  span.dp__action {
    font-weight: normal;
  }
  span.dp__action.dp__cancel {
    text-decoration: underline;
    color: #333;
  }
  span.dp__action.dp__select {
    margin-left: 8px;
    padding: 5px 10px;
    color: #FFF;
    background-color: var(--blue-france-sun-113-625);
  }
</style>
