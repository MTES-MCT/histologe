<template>
  <section class="histo-stats-filters">
    <p class="fr-mb-5w">
      Modifiez les filtres et / ou la plage de dates pour mettre les données à jour.
      <br>
      Par défaut, les signalements refusés ne sont pas comptabilisés.
    </p>

    <div>
      <HistoSelect id="filter-status" innerLabel="Statut" :optionItems="statusList">
      </HistoSelect>
    </div>
  </section>
</template>

<script lang="ts">
import HistoSelect from '../common/HistoSelect.vue'

export default {
  name: 'HistoStatsFilters',
  components: {
    HistoSelect
  },
  data () {
    return {
      statusList: [
        { Id: 'all', Text: 'Tous' },
        { Id: 'new', Text: 'Nouveau' },
        { Id: 'active', Text: 'En cours' },
        { Id: 'closed', Text: 'Fermé' }
      ]
    }
  }
}
</script>
