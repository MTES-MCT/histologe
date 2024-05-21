
<template>
  <div v-if="classNameDeleteConfirmation.length > 0" class="fr-alert fr-alert--sm" :class="classNameDeleteConfirmation">
    <p>{{ messageDeleteConfirmation }}</p>
  </div>
  <div id="histo-app-signalement-list">
    <SignalementListFilters
        @change="handleFilters"
        @changeTerritory="handleTerritoryChange"
        @clickReset="handleClickReset"
    />
    <section v-if="loadingList" class="loading fr-m-10w">
      <h2 class="fr-text--light" v-if="!hasErrorLoading">Chargement de la liste...</h2>
      <h2 class="fr-text--light" v-if="hasErrorLoading">Erreur lors du chargement de la liste.</h2>
      <p v-if="hasErrorLoading">Veuillez recharger la page ou nous prévenir <a :href="sharedProps.ajaxurlContact">via le formulaire de contact</a>.</p>
    </section>
    <section v-else class="fr-col-12 fr-background-alt--blue-france fr-mt-0">
        <div class="fr-px-3w">
          <SignalementListHeader
              :total="sharedState.signalements.pagination.total_items"
              @change="handleOrderChange"/>
          <SignalementListCards
              :list="sharedState.signalements.list"
              @deleteSignalementItem="deleteItem" />
          <SignalementListPagination
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
import SignalementListFilters from './components/SignalementListFilters.vue'
import SignalementListHeader from './components/SignalementListHeader.vue'
import SignalementListCards from './components/SignalementListCards.vue'
import SignalementListPagination from './components/SignalementListPagination.vue'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
const initElements:any = document.querySelector('#app-signalement-list')

export default defineComponent({
  name: 'TheSignalementAppList',
  components: {
    SignalementListHeader,
    SignalementListFilters,
    SignalementListCards,
    SignalementListPagination
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      loadingList: true,
      hasErrorLoading: false,
      messageDeleteConfirmation: '',
      classNameDeleteConfirmation: ''
    }
  },
  created () {
    this.init()
  },
  methods: {
    init () {
      if (initElements !== null) {
        this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl
        this.sharedProps.ajaxurlRemoveSignalement = initElements.dataset.ajaxurlRemoveSignalement
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.ajaxurlExportCsv = initElements.dataset.ajaxurlExportCsv
        this.sharedProps.ajaxurlContact = initElements.dataset.ajaxurlContact

        const url = new URL(window.location.toString())
        const params = new URLSearchParams(url.search)
        const page = params.get('page')
        const sortBy = params.get('sortBy')
        const direction = params.get('direction')
        const status = params.get('status')

        if (status) {
          this.addQueryParameter('status', status)
          this.buildUrl()
        }

        if (page) {
          this.addQueryParameter('page', page)
          this.buildUrl()
        }

        if (sortBy) {
          this.addQueryParameter('sortBy', sortBy)
          if (direction) {
            this.addQueryParameter('orderBy', direction)
          } else {
            this.addQueryParameter('orderBy', 'DESC')
          }
          this.buildUrl()
        }

        requests.getSettings(this.handleSettings)
        requests.getSignalements(this.handleSignalements)
      } else {
        this.hasErrorLoading = true
      }
    },
    handleSettings (requestResponse: any) {
      this.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
      this.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Responsable Territoire'
      this.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Administrateur'
      this.sharedState.user.isAgent = requestResponse.roleLabel === 'Administrateur' || requestResponse.roleLabel === 'Utilisateur'
      this.sharedState.user.canSeeNonDecenceEnergetique = requestResponse.canSeeNDE === '1'
      const isAdminOrAdminTerritoire = this.sharedState.user.isAdmin || this.sharedState.user.isResponsableTerritoire
      this.sharedState.user.canSeeStatusAffectation = isAdminOrAdminTerritoire
      this.sharedState.user.canDeleteSignalement = isAdminOrAdminTerritoire
      this.sharedState.user.canSeeScore = isAdminOrAdminTerritoire

      this.sharedState.territories = []
      for (const id in requestResponse.territories) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = requestResponse.territories[id].id
        optionItem.Text = `${requestResponse.territories[id].zip} - ${requestResponse.territories[id].name}`
        this.sharedState.territories.push(optionItem)
      }

      this.sharedState.partenaires = []
      const optionNoneItem = new HistoInterfaceSelectOption()
      optionNoneItem.Id = 'AUCUN'
      optionNoneItem.Text = 'Aucun'
      this.sharedState.partenaires.push(optionNoneItem)
      for (const id in requestResponse.partners) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = requestResponse.partners[id].id.toString()
        optionItem.Text = requestResponse.partners[id].nom
        this.sharedState.partenaires.push(optionItem)
      }

      this.sharedState.etiquettes = []
      optionNoneItem.Id = ''
      optionNoneItem.Text = ''
      this.sharedState.etiquettes.push(optionNoneItem)
      for (const id in requestResponse.tags) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = requestResponse.tags[id].id.toString()
        optionItem.Text = requestResponse.tags[id].label
        this.sharedState.etiquettes.push(optionItem)
      }

      this.sharedState.communes = []
      for (const id in requestResponse.communes) {
        this.sharedState.communes.push(requestResponse.communes[id])
      }

      this.sharedState.epcis = []
      for (const id in requestResponse.epcis) {
        this.sharedState.epcis.push(`${requestResponse.epcis[id].code} - ${requestResponse.epcis[id].nom}`)
      }
    },
    handleTerritoryChange (value: any) {
      this.sharedState.currentTerritoryId = value.toString()
      requests.getSettings(this.handleSettings)
    },
    handleClickReset () {
      this.init()
    },
    handleSignalements (requestResponse: any) {
      this.sharedState.signalements.filters = requestResponse.filters
      this.sharedState.signalements.list = requestResponse.list
      this.sharedState.signalements.pagination = requestResponse.pagination
      this.loadingList = false
      window.scrollTo(0, 0)
    },
    handlePageChange (pageNumber: number) {
      this.clearScreen()
      const url = new URL(window.location.toString())
      url.searchParams.set('page', pageNumber.toString())
      window.history.replaceState({}, '', url)
      this.addQueryParameter('page', pageNumber.toString())
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    handleOrderChange () {
      this.clearScreen()
      const [field, direction] = this.sharedState.input.order.split('-')
      this.removeQueryParameter('page')

      const url = new URL(window.location.toString())
      url.searchParams.delete('page')
      url.searchParams.set('sortBy', field)
      url.searchParams.set('direction', direction)
      window.history.pushState({}, '', url.toString())

      this.addQueryParameter('sortBy', field)
      this.addQueryParameter('orderBy', direction)
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    handleFilters () {
      this.clearScreen()
      const url = new URL(window.location.toString())
      url.search = ''
      this.sharedState.input.queryParameters = []

      for (const [key, value] of Object.entries(this.sharedState.input.filters)) {
        if (value && value !== null && value !== '') {
          if (key === 'dateDepot' || key === 'dateDernierSuivi') {
            const [dateDebut, dateFin] = this.handleDateParameter(key, value)
            url.searchParams.set(`${key}Debut`, dateDebut)
            url.searchParams.set(`${key}Fin`, dateFin)
            url.searchParams.delete(key)
          } else if (
            key === 'territoires' ||
              key === 'partenaires' ||
              key === 'communes' ||
              key === 'etiquettes' ||
              key === 'epcis'
          ) {
            if (typeof value === 'object') {
              if (key === 'epcis') {
                value.forEach((valueItem: any) => {
                  const code = valueItem.split('-').shift()
                  this.addQueryParameter(`${key}[]`, code)
                  url.searchParams.append(`${key}[]`, code)
                })
              } else {
                value.forEach((valueItem: any) => {
                  this.addQueryParameter(`${key}[]`, valueItem)
                  url.searchParams.append(`${key}[]`, valueItem)
                })
              }
            } else {
              this.addQueryParameter(`${key}[]`, value)
              url.searchParams.set(`${key}[]`, value)
            }
          } else {
            this.addQueryParameter(key, value)
            url.searchParams.set(key, value)
          }
        } else {
          this.removeQueryParameter(key)
          url.searchParams.delete(key)
        }
      }
      window.history.pushState({}, '', decodeURIComponent(url.toString()))
      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    handleDateParameter (key: string, value: any) {
      const dateDebut = new Date(value[0]).toISOString().split('T')[0]
      const dateFin = new Date(value[1]).toISOString().split('T')[0]
      this.addQueryParameter(`${key}Debut`, dateDebut)
      this.addQueryParameter(`${key}Fin`, dateFin)
      this.removeQueryParameter(key)

      return [dateDebut, dateFin]
    },
    handleDelete (requestResponse: any) {
      this.messageDeleteConfirmation =
          requestResponse.status === 200
            ? requestResponse.message
            : 'Une erreur s\'est produite lors de la suppression. Veuillez réessayer plus tard.'
      this.classNameDeleteConfirmation =
          requestResponse.status === 200
            ? 'fr-alert--success'
            : 'fr-alert--error'

      this.buildUrl()
      requests.getSignalements(this.handleSignalements)
    },
    async deleteItem (item: SignalementItem) {
      this.clearScreen()
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
      const param = this
        .sharedState
        .input
        .queryParameters
        .find(parameter => parameter.name === name && parameter.value === value)
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
    },
    clearScreen () {
      this.messageDeleteConfirmation = ''
      this.classNameDeleteConfirmation = ''
      this.loadingList = true
    }
  }
})

</script>
