<template>
  <div class="histo-data-table">
    <span class="histo-data-table-title"><slot name="title"></slot></span>
    <EasyDataTable
      :headers="headers"
      :items="items"
      />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'HistoDataTable',
  data() {
    return {
      headers: [
        { text: "Name", value: "name" },
        { text: "Height (cm)", value: "height", sortable: true },
        { text: "Weight (kg)", value: "weight", sortable: true },
        { text: "Age", value: "age", sortable: true }
      ],
      items: [
        { "name": "Curry", "height": 178, "weight": 77, "age": 20 },
        { "name": "James", "height": 180, "weight": 75, "age": 21 },
        { "name": "Jordan", "height": 181, "weight": 73, "age": 22 }
      ]
    }
  }
})
</script>

<style>
</style>
