<template>
  <div class="histo-data-table">
    <span class="histo-data-table-title"><slot name="title"></slot></span><br>
    <span class="histo-data-table-description"><slot name="description"></slot></span>
    <DataTable :data=items class="display" :options=options>
      <thead>
        <tr>
          <th v-for="headerText in headers">{{ headerText }}</th>
        </tr>
      </thead>
    </DataTable>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import DataTable from 'datatables.net-vue3'

export default defineComponent({
  name: 'HistoDataTable',
  components: { DataTable },
  props: {
    headers: {
      type: Array,
      default: []
    },
    items: {
      type: Object,
      default: {}
    }
  },
  data() {
    return {
      options: {
        lengthChange: false,
        searching :  false
      }
    }
  }
})
</script>

<style>
</style>
