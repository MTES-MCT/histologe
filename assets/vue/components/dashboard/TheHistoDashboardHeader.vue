<template>
  <div class="histo-dashboard-header fr-p-3w fr-mb-3w">
    <div class="header-list">
      <div class="label-rollover">
        Passez votre curseur sur cette zone pour visualiser des statistiques complémentaires.
      </div>
      <div class="header-group-item">
        <div class="header-item">
          <strong>{{ sharedState.newSignalements.count }} nouveaux</strong><br>
          soit {{ sharedState.newSignalements.percent }} %
        </div>
        <div class="header-item">
          <strong>{{ sharedState.signalements.count }} en cours</strong><br>
          soit {{ sharedState.signalements.percent }} %
        </div>
        <div class="header-item">
          <strong>{{ sharedState.closedSignalements.count }} fermés</strong><br>
          soit {{ sharedState.closedSignalements.percent }} %
        </div>
        <div class="header-item">
          <strong>{{ sharedState.refusedSignalements.count }} refusés</strong><br>
          soit {{ sharedState.refusedSignalements.percent }} %
        </div>
      </div>

      <div class="border-left">
      </div>

      <div class="header-group-item">
        <div class="header-item">
          <strong>{{ sharedState.suivis.countMoyen }} suivis moy.</strong><br>
          par signalement
        </div>
        <div class="header-item">
          <strong>{{ sharedState.suivis.countByPartner }} suivis</strong><br>
          partenaires
        </div>
        <div class="header-item">
          <strong>{{ sharedState.suivis.countByUsager }} suivis</strong><br>
          usagers
        </div>
      </div>

      <div v-if="canSeeAccountStats" class="border-left">
      </div>

      <div v-if="canSeeAccountStats" class="header-group-item">
        <div class="header-item">
          <strong>{{ sharedState.users.countActive }} actifs</strong><br>
          soit {{ sharedState.users.percentActive }} %
        </div>
        <div class="header-item">
          <strong>{{ sharedState.users.countNotActive }} inactifs</strong><br>
          soit {{ sharedState.users.percentNotActive }} %
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'

export default defineComponent({
  name: 'TheHistoDashboardHeader',
  components: {
  },
  data () {
    return {
      sharedState: store.state
    }
  },
  computed: {
    canSeeAccountStats () {
      return store.state.user.isAdmin || store.state.user.isResponsableTerritoire || store.state.user.isAdministrateurPartenaire
    }
  },
  methods: {
  }
})
</script>

<style>
  .histo-dashboard-header {
    background-color: #CACAFB66;
  }
  .histo-dashboard-header div.header-list {
    display: table;
    margin-left: -10px;
    border-spacing: 10px 0px;
  }
  .histo-dashboard-header div.header-list div.label-rollover {
    display: none;
  }
  .histo-dashboard-header div.header-list div.header-item {
    display: table-cell;
    background-color: #FFF;
    padding: 6px 12px;
  }
  .histo-dashboard-header div.header-list div.border-left {
    display: table-cell;
    border-left: 2px solid #000091;
    margin-right: 5px;
  }

  @media (max-width: 1200px) {
    .histo-dashboard-header div.header-list {
      display: block;
    }
    .histo-dashboard-header div.header-list:hover {
      display: block;
      height: auto;
    }
    .histo-dashboard-header div.header-list div.header-group-item {
      display: none;
    }
    .histo-dashboard-header div.header-list div.label-rollover {
      display: block;
      font-style: italic;
    }
    .histo-dashboard-header div.header-list:hover div.label-rollover {
      display: none;
    }
    .histo-dashboard-header div.header-list:hover div.header-group-item {
      display: block;
    }
    .histo-dashboard-header div.header-list div.header-item {
      display: inline-block;
      margin-right: 5px;
      margin-bottom: 5px;
    }
    .histo-dashboard-header div.header-list div.border-left {
      display: none;
    }
  }
</style>
