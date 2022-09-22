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
        searching :  false,
        language: {
          "info": "Résultats _START_ - _END_ sur _TOTAL_",
          "infoEmpty": "Aucun résultat",
          "zeroRecords": "Aucune donnée disponible",
          "emptyTable": "Aucune donnée disponible",
          "paginate": {
            "first":      "|<",
            "last":       ">|",
            "next":       ">",
            "previous":   "<"
          }
        }
      }
    }
  }
})
</script>

<style>
  @import 'datatables.net-dt';

  .histo-data-table table thead {
    color: var(--blue-france-sun-113-625);
  }

  .histo-data-table table.dataTable.display tbody td {
    box-shadow: none;
    background-color: #FFF;
    text-align: left;
  }
  .histo-data-table table.dataTable.display > tbody > tr.odd > td {
    background-color: #CACAFBA6;
  }
  .histo-data-table table.dataTable.display > tbody > tr.even > td.sorting_1, .histo-data-table table.dataTable.display > tbody > tr.odd > td.sorting_1 {
    box-shadow: none; 
  }

  .histo-data-table .dataTables_wrapper .dataTables_paginate .paginate_button {
    border: 0px;
    background: none;
    padding: 0.2em 0.7em;
  }
  .histo-data-table .dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 0px;
    background: var(--blue-france-850-200);
  }
</style>
