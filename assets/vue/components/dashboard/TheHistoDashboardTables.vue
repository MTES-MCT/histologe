<template>
  <div class="histo-dashboard-tables fr-grid-row fr-grid-row--gutters fr-mb-3w">
    <div v-if="sharedState.user.isAdmin" class="fr-col-12 fr-col-lg-6 fr-mb-3w">
      <HistoDataTable
        :headers=signalementPerTerritoryHeaders
        :items=sharedState.signalementsPerTerritoire
        >
        <template #title>Signalements sur les territoires</template>
      </HistoDataTable>
    </div>

    <div v-if="sharedState.user.isAdmin || sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-lg-6 fr-mb-3w">
      <HistoDataTable
        :headers=affectationsOfPartnersHeaders
        :items=sharedState.affectationsPartenaires
        >
        <template #title>Affectations des partenaires</template>
      </HistoDataTable>
    </div>

    <div v-if="sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-lg-6 fr-mb-3w">
      <HistoDataTable
        :headers=signalementsAcceptedNoSuiviHeaders
        :items=sharedState.signalementsAcceptedNoSuivi
        >
        <template #title>Signalements acceptés mais sans suivi</template>
      </HistoDataTable>
    </div>

    <div v-if="sharedState.user.isAdmin" class="fr-col-12">
      <HistoDataTable
        :headers=connectionsEsaboraHeaders
        :items=sharedState.esaboraEvents
        >
        <template #title>Connexions ESABORA</template>
      </HistoDataTable>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'

import HistoDataTable from '../common/external/HistoDataTable.vue'

export default defineComponent({
  name: 'TheHistoDashboardTables',
  components: {
    HistoDataTable
  },
  data () {
    return {
      sharedState: store.state,
      signalementPerTerritoryHeaders: [
        'Territoire',
        'Nouveaux',
        'Non affectés'
      ],
      signalementsAcceptedNoSuiviHeaders: [
        'Partenaire',
        'Sans suivi'
      ],
      connectionsEsaboraHeaders: [
        'Référence',
        'Dernière synchro',
        'Partenaire',
        'Action',
        'Statut',
        'Message d\'erreur'
      ]
    }
  },
  computed: {
    affectationsOfPartnersHeaders () {
      return [
        'Partenaire',
        'En attente',
        'Refusés'
      ]
    }
  },
  methods: {
  }
})
</script>

<style>
</style>
