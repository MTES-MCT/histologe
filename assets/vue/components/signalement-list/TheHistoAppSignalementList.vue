<template>
  <div id="histo-app-signalement-list">
    <TheHistoSignalementListFilter/>
    <section v-if="loadingList" class="loading fr-m-10w">
      <h2 class="fr-text--light">Chargement de la liste...</h2>
    </section>
    <section v-else class="fr-col-12 fr-background-alt--blue-france fr-mt-0">
        <div class="fr-px-3w">
          <TheHistoSignalementListHeader
              :total="sharedState.signalements.pagination.total_items"
              :on-change="handleOrderChange"/>
          <TheHistoSignalementListCards
              :list="sharedState.signalements.list"
              @deleteSignalementItem="deleteItem" />
          <TheHistoSignalementListPagination
              :pagination="sharedState.signalements.pagination"
              @changePage="handlePageChange"/>
        </div>
    </section>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { store } from './store'
import { requests } from './requests'
import { SignalementItem } from './interfaces/signalementItem'
import TheHistoSignalementListFilter from '../signalement-list/TheHistoSignalementListFilter.vue'
import TheHistoSignalementListHeader from '../signalement-list/TheHistoSignalementListHeader.vue'
import TheHistoSignalementListCards from '../signalement-list/TheHistoSignalementListCards.vue'
import TheHistoSignalementListPagination from '../signalement-list/TheHistoSignalementListPagination.vue'
const initElements:any = document.querySelector('#app-signalement-list')

export default defineComponent({
  name: 'TheHistoAppSignalementList',
  components: {
    TheHistoSignalementListFilter,
    TheHistoSignalementListHeader,
    TheHistoSignalementListCards,
    TheHistoSignalementListPagination
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      loadingList: true
    }
  },
  created () {
    if (initElements !== null) {
      this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl
      this.sharedProps.ajaxurlRemoveSignalement = initElements.dataset.ajaxurlRemoveSignalement
      this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
      this.sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv
      requests.getSettings(this.handleSettings)
      requests.getSignalements(this.handleSignalements)
    } else {
      alert('Error while loading signalements')
    }
  },
  methods: {
    handleSettings (requestResponse: any) {
      this.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
      this.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Responsable Territoire'
      this.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Administrateur'
      this.sharedState.user.canSeeNonDecenceEnergetique = requestResponse.canSeeNDE === '1'
      const isAdminOrAdminTerritoire = this.sharedState.user.isAdmin || this.sharedState.user.isResponsableTerritoire
      this.sharedState.user.canSeeStatusAffectation = isAdminOrAdminTerritoire
      this.sharedState.user.canDeleteSignalement = isAdminOrAdminTerritoire
    },
    handleSignalements (requestResponse: any) {
      this.sharedState.signalements.filters = requestResponse.filters
      this.sharedState.signalements.list = requestResponse.list
      this.sharedState.signalements.pagination = requestResponse.pagination

      window.scrollTo(0, 0)
      this.loadingList = false
    },
    handlePageChange (pageNumber: number) {
      this.loadingList = true
      window.scrollTo(0, 0)
      this.addQueryParameter('page', pageNumber.toString())
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    handleOrderChange () {
      this.loadingList = true
      window.scrollTo(0, 0)
      const [field, sort] = this.sharedState.input.order.split('-')
      this.removeQueryParameter('page')
      this.addQueryParameter('sortBy', field)
      this.addQueryParameter('orderBy', sort)
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    handleDelete (requestResponse: any) {
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    async deleteItem (item: SignalementItem) {
      this.loadingList = true
      window.scrollTo(0, 0)
      await requests.deleteSignalement(
        item.uuid,
        item.csrfToken,
        this.handleDelete
      )
    },
    getSettings (functionReturn: Function) {
      const url = store.props.ajaxurlSettings
      requests.doRequest(url, functionReturn)
    },
    addQueryParameter (name: string, value: string) {
      const param = this.sharedState.input.queryParameters.find(parameter => parameter.name === name)
      if (param) {
        param.value = value
      } else {
        this.sharedState.input.queryParameters.push({ name, value })
      }
    },
    removeQueryParameter (name: string) {
      const index = this.sharedState.input.queryParameters.findIndex(parameter => parameter.name === name)
      if (index !== -1) {
        this.sharedState.input.queryParameters.splice(index, 1)
      }
    },
    buildUrl () {
      const queryParams = this
        .sharedState
        .input
        .queryParameters
        .map(parameter => `${parameter.name}=${parameter.value}`)
      this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl + '?' + queryParams.join('&')
    }
  }
})

</script>
