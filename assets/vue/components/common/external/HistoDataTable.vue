<template>
  <div class="histo-data-table">
    <span class="histo-data-table-title"><slot name="title"></slot></span><br>
    <span class="histo-data-table-description"><slot name="description"></slot></span>
    <DataTable :data=items class="display" :options=options>
      <thead>
        <tr>
          <th>A</th>
          <th>B</th>
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
  data() {
    return {
      items: [
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4],
        [1, 2],
        [3, 4]
      ],
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
