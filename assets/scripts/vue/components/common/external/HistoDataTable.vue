<template>
  <div class="histo-data-table">
    <span class="histo-data-table-title"><slot name="title"></slot></span><br>
    <span class="histo-data-table-description"><slot name="description"></slot></span>
    <DataTable :data=items class="display" :options=options>
      <thead>
        <tr>
          <th v-for="headerText in headers" v-bind:key="headerText">{{ headerText }}</th>
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
      type: Array
    },
    items: {
      type: Object
    }
  },
  data () {
    return {
      options: {
        lengthChange: false,
        // pageLength: 5,
        searching: false,
        language: {
          info: 'Résultats _START_ - _END_ sur _TOTAL_',
          infoEmpty: 'Aucun résultat',
          zeroRecords: 'Aucune donnée disponible',
          emptyTable: 'Aucune donnée disponible',
          paginate: {
            first: '|<',
            last: '>|',
            next: '>',
            previous: '<'
          }
        }
      }
    }
  }
})
</script>

<style>
  @import 'datatables.net-dt';

  .histo-data-table table {
    border: 1px solid var(--border-contrast-grey);
  }

  .histo-data-table table thead {
    color: var(--text-default-grey);
    background-color: var(--background-alt-grey);
    text-decoration: underline;
  }

  .histo-data-table table.dataTable.display tbody td {
    box-shadow: none;
    background-color: #FFF;
    text-align: left;
  }

  .histo-data-table table.dataTable.display > tbody > tr.even > td.sorting_1, .histo-data-table table.dataTable.display > tbody > tr.odd > td.sorting_1 {
    box-shadow: none;
  }

  .histo-data-table .dataTables_wrapper .dataTables_paginate .paginate_button {
    border: 0px;
    color: #161616;
    background: none;
    padding: 0.2em 0.7em;
  }
  .histo-data-table .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 0px;
    background-color: var(--background-active-blue-france);
    color: var(--text-inverted-blue-france) !important;
  }
  .histo-data-table .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    color: #161616 !important;
    background-color: #E5E5E5;
    --hover-tint: var(--hover);
  }
</style>
