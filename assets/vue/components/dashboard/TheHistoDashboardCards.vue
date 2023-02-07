<template>
  <div class="histo-dashboard-cards fr-grid-row fr-grid-row--gutters fr-mb-3w">

    <div v-if="sharedState.user.isAdmin || sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
                <p class="fr-badge fr-badge--no-icon" :class=getTagClass(sharedState.newSignalements.count)>{{ countNouveauxSignalements }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.newSignalements.link)>Nouveaux signalements</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez les nouveaux signalements à accepter et à affecter.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
              <p class="fr-badge fr-badge--no-icon" :class=getTagClass(sharedState.newAffectations.count)>{{ countNouvellesAffectations }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.newAffectations.link)>Nouvelles affectations</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez les nouveaux signalements affectés à votre partenaire.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
              <p class="fr-badge fr-badge--no-icon" :class=getTagClass(sharedState.newSuivis.count)>{{ countNouveauxSuivis }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.newSuivis.link)>Nouveaux suivis</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez les signalements avec de nouveaux suivis partenaires et usagers.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
                <p class="fr-badge fr-badge--no-icon fr-badge--info">{{ countSansSuivi }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.noSuivis.link)>Sans suivi</a>
            </h3>
            <p class="fr-card__desc">
              Accédez aux signalements sans nouveau suivi depuis au moins 30 jours.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!sharedState.user.isAdmin && !sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <div class="fr-card__content fr-mt-5w">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.allSignalements.link)>Tous les signalements</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez tous les signalements gérés par votre partenaire.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="sharedState.user.isAdmin" class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
              <p class="fr-badge fr-badge--no-icon" :class=getTagClass(sharedState.cloturesGlobales.count)>{{ countCloturesGlobales }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.cloturesGlobales.link)>Clôtures globales</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez les signalements récemment clôturés par les responsables territoire.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="sharedState.user.isAdmin || sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <ul class="fr-badges-group">
            <li>
              <p class="fr-badge fr-badge--no-icon" :class=getTagClass(sharedState.cloturesPartenaires.count)>{{ countCloturesPartenaires }}</p>
            </li>
          </ul>
          <div class="fr-card__content">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.cloturesPartenaires.link)>Clôtures partenaires</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez les signalements récemment clôturés par les partenaires.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="sharedState.user.isResponsableTerritoire" class="fr-col-12 fr-col-md-6 fr-col-lg-4">
      <div class="fr-card fr-enlarge-link">
        <div class="fr-card__body">
          <div class="fr-card__content fr-mt-5w">
            <h3 class="fr-card__title">
              <a :href=getSanitizedUrl(sharedState.userAffectations.link)>Mes affectations</a>
            </h3>
            <p class="fr-card__desc">
              Retrouvez tous les signalements qui vous ont été affectés.
            </p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'

export default defineComponent({
  name: 'TheHistoDashboardCards',
  components: {
  },
  data () {
    return {
      sharedState: store.state
    }
  },
  computed: {
    countNouveauxSignalements () {
      if (store.state.newSignalements.count === 0) {
        return 'Aucun'
      } else {
        return store.state.newSignalements.count + ' nouveau' + (store.state.newSignalements.count > 1 ? 'x' : '')
      }
    },
    countNouvellesAffectations () {
      if (store.state.newAffectations.count === 0) {
        return 'Aucun'
      } else {
        return store.state.newAffectations.count + ' nouveau' + (store.state.newAffectations.count > 1 ? 'x' : '')
      }
    },
    countNouveauxSuivis () {
      if (store.state.newSuivis.count === 0) {
        return 'Aucun'
      } else {
        return store.state.newSuivis.count + ' nouveau' + (store.state.newSuivis.count > 1 ? 'x' : '')
      }
    },
    countSansSuivi () {
      if (store.state.noSuivis.count === 0) {
        return 'Aucun'
      } else {
        return store.state.noSuivis.count + ' signalement' + (store.state.noSuivis.count > 1 ? 's' : '')
      }
    },
    countCloturesGlobales () {
      if (store.state.cloturesGlobales.count === 0) {
        return 'Aucun'
      } else {
        return store.state.cloturesGlobales.count + ' nouveau' + (store.state.cloturesGlobales.count > 1 ? 'x' : '')
      }
    },
    countCloturesPartenaires () {
      if (store.state.cloturesPartenaires.count === 0) {
        return 'Aucun'
      } else {
        return store.state.cloturesPartenaires.count + ' nouveau' + (store.state.cloturesPartenaires.count > 1 ? 'x' : '')
      }
    }
  },
  methods: {
    getTagClass (count:number) {
      return (count === 0) ? 'fr-badge--info' : 'fr-badge--warning'
    },
    getSanitizedUrl (url:any) {
      if (url === undefined || url === null) {
        return url
      }
      const sanitizeUrl = require('@braintree/sanitize-url').sanitizeUrl
      return sanitizeUrl(url)
    }
  }
})
</script>

<style>
  .histo-dashboard-cards .fr-card .fr-card__title a {
    box-shadow: none;
  }
  .histo-dashboard-cards .fr-card ul {
    list-style: none;
  }
</style>
