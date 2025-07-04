<template>
  <div
    class="histo-app-dashboard"
    :data-ajaxurl-settings="sharedProps.ajaxurlSettings"
    :data-ajaxurl-kpi="sharedProps.ajaxurlKpi"
    :data-ajaxurl-partners="sharedProps.ajaxurlPartners"
    :data-ajaxurl-signalements-nosuivi="sharedProps.ajaxurlSignalementsNosuivi"
    :data-ajaxurl-signalements-per-territoire="sharedProps.ajaxurlSignalementsPerTerritoire"
    :data-ajaxurl-connections-esabora="sharedProps.ajaxurlConnectionsEsabora"
    >

    <div v-if="isLoadingInit" class="loading fr-m-10w fr-text--center">
      Initialisation du tableau de bord...

      <div v-if="isErrorInit" class="fr-my-5w">
        Erreur lors de l'initialisation du tableau de bord.<br><br>
        Veuillez recharger la page ou nous prévenir via le formulaire de contact.
      </div>
    </div>

    <div v-else>
      <TheHistoDashboardHeader />

      <div :class="['fr-p-3w', 'fr-container-sml']">
        <div class="fr-grid-row fr-grid-row--gutters fr-mb-1w">
          <div class="fr-col fr-col-md-9">
            <div class="fr-display-inline-flex fr-align-items-center">
                <div v-html="sharedState.user.avatarOrPlaceHolder"></div>
                <div class="fr-ml-3v">
                    <span class="fr-display-block"><h1 class="fr-h2">Bonjour {{ sharedState.user.prenom }}</h1></span>
                    <span class="fr-display-block">Bienvenue sur votre tableau de bord !</span>
                </div>
            </div>
          </div>
          <div v-if="sharedState.user.isAdmin || sharedState.user.isMultiTerritoire" class="fr-col fr-col-md-3">
            <HistoSelect
              id="filter-territoires"
              v-model="sharedState.filters.territory"
              @update:modelValue="handleChangeTerritoire"
              :option-items=sharedState.territories
              >
              <template #label>Territoire</template>
            </HistoSelect>
          </div>
        </div>

        <TheHistoDashboardCards />

        <div v-if="sharedState.user.isAdmin || sharedState.user.isResponsableTerritoire">
          <div v-if="countTablesLoaded < countTablesToLoad">
            Informations complémentaires en cours de chargement
          </div>

          <TheHistoDashboardTables v-else />
        </div>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import HistoSelect from '../common/HistoSelect.vue'
import TheHistoDashboardHeader from './TheHistoDashboardHeader.vue'
import TheHistoDashboardCards from './TheHistoDashboardCards.vue'
import TheHistoDashboardTables from './TheHistoDashboardTables.vue'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
const initElements:any = document.querySelector('#app-dashboard')
export default defineComponent({
  name: 'TheHistoAppDashboard',
  components: {
    HistoSelect,
    TheHistoDashboardHeader,
    TheHistoDashboardCards,
    TheHistoDashboardTables
  },
  data () {
    return {
      sharedState: store.state,
      sharedProps: store.props,
      isErrorInit: false,
      isLoadingInit: true,
      countTablesToLoad: 0,
      countTablesLoaded: 0,
      isLoadingRefresh: false
    }
  },
  async created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
      this.sharedProps.ajaxurlKpi = initElements.dataset.ajaxurlKpi
      this.sharedProps.ajaxurlPartners = initElements.dataset.ajaxurlPartners
      this.sharedProps.ajaxurlSignalementsNosuivi = initElements.dataset.ajaxurlSignalementsNosuivi
      this.sharedProps.ajaxurlSignalementsPerTerritoire = initElements.dataset.ajaxurlSignalementsPerTerritoire
      this.sharedProps.ajaxurlConnectionsEsabora = initElements.dataset.ajaxurlConnectionsEsabora
      try {
        await this.initSettingsWithPromise()
        await this.initKPIWithPromise()
      } catch (error) {
        console.error('Error during initialization', error)
        this.isErrorInit = true
      }
    } else {
      this.isErrorInit = true
    }
  },
  methods: {
    handleInitSettings (requestResponse: any) {
      this.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
      this.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Resp. Territoire'
      this.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Admin. partenaire'
      this.sharedState.user.isMultiTerritoire = requestResponse.isMultiTerritoire === true
      this.sharedState.user.canSeeNonDecenceEnergetique = requestResponse.canSeeNDE === '1'
      this.sharedState.user.prenom = requestResponse.firstname
      this.sharedState.user.avatarOrPlaceHolder = requestResponse.avatarOrPlaceHolder
      this.sharedState.territories = []
      const optionAllItem = new HistoInterfaceSelectOption()
      optionAllItem.Id = 'all'
      optionAllItem.Text = 'Tous'
      this.sharedState.territories.push(optionAllItem)
      for (const id in requestResponse.territories) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = requestResponse.territories[id].id
        optionItem.Text = requestResponse.territories[id].zip + ' - ' + requestResponse.territories[id].name
        this.sharedState.territories.push(optionItem)
      }
    },
    handleInitKPI (requestResponse: any) {
      if (requestResponse === 'error') {
        this.isErrorInit = true
      } else {
        this.isLoadingInit = false
        this.isLoadingRefresh = false
        this.processResponseInit(requestResponse, () => {
          if (this.sharedState.user.isAdmin || this.sharedState.user.isResponsableTerritoire) {
            this.countTablesToLoad++
            requests.initAffectationPartner(this.handleAffectationPartner)
          }
          if (this.sharedState.user.isResponsableTerritoire) {
            this.countTablesToLoad++
            requests.initSignalementsNoSuivi(this.handleSignalementsNoSuivi)
          }
          if (this.sharedState.user.isAdmin) {
            this.countTablesToLoad++
            requests.initSignalementsPerTerritoire(this.handleSignalementsPerTerritoire)
            this.countTablesToLoad++
            requests.initEsaboraEvents(this.handleEsaboraEvents)
          }
        })
      }
    },
    processResponseInit (requestResponse: any, callback: () => void) {
      this.sharedState.signalements.count = requestResponse.data.countSignalement.active
      this.sharedState.signalements.percent = requestResponse.data.countSignalement.percentage.active
      this.sharedState.closedSignalements.count = requestResponse.data.countSignalement.closed
      this.sharedState.closedSignalements.percent = requestResponse.data.countSignalement.percentage.closed
      this.sharedState.newSignalementsStats.count = requestResponse.data.countSignalement.new
      this.sharedState.newSignalementsStats.percent = requestResponse.data.countSignalement.percentage.new
      this.sharedState.refusedSignalements.count = requestResponse.data.countSignalement.refused
      this.sharedState.refusedSignalements.percent = requestResponse.data.countSignalement.percentage.refused
      this.sharedState.suivis.countMoyen = requestResponse.data.countSuivi.average
      this.sharedState.suivis.countByPartner = requestResponse.data.countSuivi.partner
      this.sharedState.suivis.countByUsager = requestResponse.data.countSuivi.usager
      this.sharedState.users.countActive = requestResponse.data.countUser.active
      this.sharedState.users.percentActive = requestResponse.data.countUser.percentage.active
      this.sharedState.users.countNotActive = requestResponse.data.countUser.inactive
      this.sharedState.users.percentNotActive = requestResponse.data.countUser.percentage.inactive
      const dataWidget = requestResponse.data.widgetCards
      this.sharedState.newSignalements.count = dataWidget.cardNouveauxSignalements && dataWidget.cardNouveauxSignalements.count != null ? dataWidget.cardNouveauxSignalements?.count : 0
      this.sharedState.newSignalements.link = dataWidget.cardNouveauxSignalements?.link
      this.sharedState.newAffectations.link = dataWidget.cardAffectation?.link
      this.sharedState.newSuivis.count = dataWidget.cardNouveauxSuivis && dataWidget.cardNouveauxSuivis.count != null ? dataWidget.cardNouveauxSuivis?.count : 0
      this.sharedState.newSuivis.link = dataWidget.cardNouveauxSuivis?.link
      this.sharedState.noSuivis.count = dataWidget.cardSansSuivi && dataWidget.cardSansSuivi.count != null ? dataWidget.cardSansSuivi?.count : 0
      this.sharedState.noSuivis.link = dataWidget.cardSansSuivi?.link
      this.sharedState.cloturesGlobales.count = dataWidget.cardCloturesGlobales && dataWidget.cardCloturesGlobales.count != null ? dataWidget.cardCloturesGlobales?.count : 0
      this.sharedState.cloturesGlobales.link = dataWidget.cardCloturesGlobales?.link
      this.sharedState.cloturesPartenaires.count = dataWidget.cardCloturesPartenaires && dataWidget.cardCloturesPartenaires.count != null ? dataWidget.cardCloturesPartenaires?.count : 0
      this.sharedState.cloturesPartenaires.link = dataWidget.cardCloturesPartenaires?.link
      this.sharedState.allSignalements.link = dataWidget.cardTousLesSignalements?.link
      this.sharedState.newAffectations.count = dataWidget.cardNouvellesAffectations && dataWidget.cardNouvellesAffectations.count != null ? dataWidget.cardNouvellesAffectations?.count : 0
      this.sharedState.newAffectations.link = dataWidget.cardNouvellesAffectations?.link
      this.sharedState.userAffectations.link = dataWidget.cardMesAffectations?.link
      this.sharedState.nonDecenceSignalements.countNew = dataWidget.cardSignalementsNouveauxNonDecence && dataWidget.cardSignalementsNouveauxNonDecence.count != null ? dataWidget.cardSignalementsNouveauxNonDecence?.count : 0
      this.sharedState.nonDecenceSignalements.linkNew = dataWidget.cardSignalementsNouveauxNonDecence?.link
      this.sharedState.nonDecenceSignalements.countActive = dataWidget.cardSignalementsEnCoursNonDecence && dataWidget.cardSignalementsEnCoursNonDecence.count != null ? dataWidget.cardSignalementsEnCoursNonDecence?.count : 0
      this.sharedState.nonDecenceSignalements.linkActive = dataWidget.cardSignalementsEnCoursNonDecence?.link
      this.sharedState.noSuiviAfter3Relances.count = dataWidget.cardNoSuiviAfter3Relances?.count
      this.sharedState.noSuiviAfter3Relances.link = dataWidget.cardNoSuiviAfter3Relances?.link
      this.sharedState.usagerAbandonProcedure.count = dataWidget.cardUsagerAbandonProcedure?.count
      this.sharedState.usagerAbandonProcedure.link = dataWidget.cardUsagerAbandonProcedure?.link      
      this.sharedState.partenairesNonNotifiables.count = dataWidget.cardPartenairesNonNotifiables?.count
      this.sharedState.partenairesNonNotifiables.link = dataWidget.cardPartenairesNonNotifiables?.link
      this.sharedState.archivingScheduledUsers.count = dataWidget.cardArchivingScheduledUsers?.count
      this.sharedState.archivingScheduledUsers.link = dataWidget.cardArchivingScheduledUsers?.link

      if (callback) {
        callback()
      }
    },
    handleAffectationPartner (requestResponse: any) {
      this.countTablesLoaded++
      this.sharedState.affectationsPartenaires = []
      for (const i in requestResponse.data) {
        const responseItem = requestResponse.data[i]
        const item = [
          responseItem.nom,
          responseItem.waiting,
          responseItem.refused
        ]
        this.sharedState.affectationsPartenaires.push(item)
      }
    },
    handleSignalementsNoSuivi (requestResponse: any) {
      this.countTablesLoaded++
      this.sharedState.signalementsAcceptedNoSuivi = []
      for (const i in requestResponse.data) {
        const responseItem = requestResponse.data[i]
        const item = [
          responseItem.nom,
          responseItem.count_no_suivi
        ]
        this.sharedState.signalementsAcceptedNoSuivi.push(item)
      }
    },
    handleSignalementsPerTerritoire (requestResponse: any) {
      this.countTablesLoaded++
      this.sharedState.signalementsPerTerritoire = []
      for (const i in requestResponse.data) {
        const responseItem = requestResponse.data[i]
        const item = [
          responseItem.label,
          responseItem.new,
          responseItem.no_affected
        ]
        this.sharedState.signalementsPerTerritoire.push(item)
      }
    },
    handleEsaboraEvents (requestResponse: any) {
      this.countTablesLoaded++
      this.sharedState.esaboraEvents = []
      for (const i in requestResponse.data) {
        const responseItem = requestResponse.data[i]
        const item = [
          responseItem.reference,
          responseItem.last_event,
          responseItem.nom,
          responseItem.action,
          responseItem.status,
          responseItem.response
        ]
        this.sharedState.esaboraEvents.push(item)
      }
    },
    handleChangeTerritoire () {
      this.isLoadingRefresh = true
      requests.initKPI(this.handleInitKPI)
    },
    initSettingsWithPromise (): Promise<void> {
      return new Promise((resolve, reject) => {
        requests.initSettings((response: any) => {
          try {
            this.handleInitSettings(response)
            resolve()
          } catch (error) {
            reject(error)
          }
        })
      })
    },
    initKPIWithPromise (): Promise<void> {
      return new Promise((resolve, reject) => {
        requests.initKPI((response: any) => {
          try {
            this.handleInitKPI(response)
            resolve()
          } catch (error) {
            reject(error)
          }
        })
      })
    }
  }
})
</script>

<style>
  #app-dashboard {
    height: 100%;
  }
</style>
