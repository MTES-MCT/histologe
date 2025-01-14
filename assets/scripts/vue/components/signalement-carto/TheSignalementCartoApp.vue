
<template>
  <div id="histo-app-signalement-carto">
    <SignalementListFilters
        :shared-props="sharedProps"
        @change="handleFilters"
        @changeTerritory="handleTerritoryChange"
        @clickReset="handleClickReset"
    />
    <HistoCarto
        :api-url="sharedProps.ajaxurlSignalement"
        :token="sharedProps.token"
        formSelector="#signalement-list-filters"/>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { PATTERN_BADGE_EPCI, store } from '../signalement-list/store'
import { requests } from '../signalement-list/requests'
import { Filters, SEARCH_FILTERS } from '../signalement-list/interfaces/filters'
import SignalementListFilters from '../signalement-list/components/SignalementListFilters.vue'
import HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'
import HistoCarto from '../common/external/HistoCarto.vue'
const initElements:any = document.querySelector('#app-signalement-carto')

export default defineComponent({
  name: 'TheSignalementCartoApp',
  components: {
    SignalementListFilters,
    HistoCarto
  },
  data () {
    return {
      sharedProps: store.props,
      sharedState: store.state,
      loadingList: true,
      hasErrorLoading: false,
      messageDeleteConfirmation: '',
      classNameDeleteConfirmation: '',
      abortRequest: null as AbortController | null
    }
  },
  created () {
    this.init()
  },
  methods: {
    init (reset: boolean = false) {
      if (initElements !== null) {
        if (this.abortRequest) {
          this.abortRequest?.abort()
        }
        this.abortRequest = new AbortController()

        this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl
        this.sharedProps.ajaxurlSettings = initElements.dataset.ajaxurlSettings
        this.sharedProps.token = initElements.dataset.token
        if (!reset) {
          this.handleQueryParameter()
        }

        this.buildUrl()
        requests.getSettings(this.handleSettings)
        requests.getSignalements(this.handleSignalements, { signal: this.abortRequest?.signal })
      } else {
        this.hasErrorLoading = true
      }
    },
    handleQueryParameter: function () {
      const url = new URL(window.location.toString())
      const params = new URLSearchParams(url.search)
      const filters = this.sharedState.input.filters as Filters
      for (const filter of SEARCH_FILTERS) {
        const type = filter.type
        const key = filter.name
        const value: null | string = params.get(key)
        const epciData = localStorage.getItem('epci')
        let valueList: null | string[] = params.getAll(`${key}[]`)
        if (value && value.length > 0) {
          if (['sortBy', 'direction', 'page'].includes(key)) {
            this.addQueryParameter(key, value)
            continue
          }
          if (type === 'text') {
            filters[key] = filter?.defaultValue || value
            this.addQueryParameter(key, value)
          } else if (type === 'date') {
            const keyDebut = key
            const keyFin = key.replace('Debut', 'Fin')
            const newKey = key.replace('Debut', '')
            const dateDebut = params.get(keyDebut)
            const dateFin = params.get(keyFin)
            if (dateDebut && dateFin) {
              this.addQueryParameter(keyDebut, dateDebut)
              const dateDebutFormatted: Date = new Date(dateDebut)
              this.addQueryParameter(keyFin, dateFin)
              const dateFinFormatted: Date = new Date(dateFin)
              filters[newKey] = [dateDebutFormatted, dateFinFormatted]
            }
          }
        } else if (valueList && valueList.length > 0) {
          if (type === 'collection') {
            valueList = params.getAll(`${key}[]`)
            if (valueList && valueList.length > 0) {
              valueList.forEach(valueItem => {
                this.addQueryParameter(`${key}[]`, valueItem.trim())
                if (key === 'epcis' && epciData) {
                  const listEpci = JSON.parse(epciData)
                  const itemEpci = listEpci.filter((itemEpci: string) => itemEpci.includes(valueItem))
                  filters[key].push(itemEpci.shift())
                } else {
                  filters[key].push(valueItem)
                }
              })
            }
          }
        }
        if (value && value.length > 0) {
          this.sharedState.showOptions = filter.showOptions
        }
      }
    },
    handleSettings (requestResponse: any) {
      this.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
      this.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Resp. Territoire'
      this.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Admin. partenaire'
      this.sharedState.user.isAgent = ['Admin. partenaire', 'Agent'].includes(requestResponse.roleLabel)
      this.sharedState.user.isMultiTerritoire = requestResponse.isMultiTerritoire === true
      const isAdminOrAdminTerritoire = this.sharedState.user.isAdmin || this.sharedState.user.isResponsableTerritoire
      this.sharedState.user.canSeeStatusAffectation = isAdminOrAdminTerritoire
      this.sharedState.user.canSeeBailleurSocial = isAdminOrAdminTerritoire
      this.sharedState.user.canSeeFilterPartner = isAdminOrAdminTerritoire
      this.sharedState.user.canSeeScore = isAdminOrAdminTerritoire
      this.sharedState.user.partnerIds = requestResponse.partnerIds
      this.sharedState.hasSignalementImported = requestResponse.hasSignalementImported
      this.sharedState.input.order = 'reference-DESC'
      this.sharedState.input.filters.isImported = 'oui'

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
      const partnersArray = Object.values(requestResponse.partners)
      partnersArray.sort((a: any, b:any) => (a.nom > b.nom) ? 1 : ((b.nom > a.nom) ? -1 : 0))
      partnersArray.forEach((partner: any) => {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = partner.id.toString()
        optionItem.Text = partner.nom
        this.sharedState.partenaires.push(optionItem)
      })

      this.sharedState.etiquettes = []
      optionNoneItem.Id = ''
      optionNoneItem.Text = ''
      this.sharedState.etiquettes.push(optionNoneItem)
      const tagsArray = Object.values(requestResponse.tags)
      tagsArray.sort((a: any, b:any) => (a.label > b.label) ? 1 : ((b.label > a.label) ? -1 : 0))
      tagsArray.forEach((tag: any) => {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = tag.id.toString()
        optionItem.Text = tag.label
        this.sharedState.etiquettes.push(optionItem)
      })

      this.sharedState.zones = []
      const zonesArray = Object.values(requestResponse.zones)
      zonesArray.forEach((zone: any) => {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = zone.id.toString()
        optionItem.Text = zone.name
        this.sharedState.zones.push(optionItem)
      })

      this.sharedState.bailleursSociaux = []
      for (const id in requestResponse.bailleursSociaux) {
        const optionItem = new HistoInterfaceSelectOption()
        optionItem.Id = requestResponse.bailleursSociaux[id].id.toString()
        optionItem.Text = requestResponse.bailleursSociaux[id].name
        this.sharedState.bailleursSociaux.push(optionItem)
      }

      this.sharedState.communes = []
      for (const id in requestResponse.communes) {
        this.sharedState.communes.push(requestResponse.communes[id])
      }

      this.sharedState.epcis = []
      for (const id in requestResponse.epcis) {
        this.sharedState.epcis.push(`${requestResponse.epcis[id].nom} (${requestResponse.epcis[id].code} )`)
      }
      localStorage.setItem('epci', JSON.stringify(this.sharedState.epcis))
    },
    handleTerritoryChange (value: any) {
      delete (this.sharedState.input.filters as any).communes
      delete (this.sharedState.input.filters as any).epcis
      this.sharedState.currentTerritoryId = value.toString()
      requests.getSettings(this.handleSettings)
    },
    handleClickReset () {
      this.init(true)
    },
    handleSignalements (requestResponse: any) {
      if (typeof requestResponse === 'string' && requestResponse === 'error') {
        this.hasErrorLoading = true
      } else {
        this.hasErrorLoading = false
        this.sharedState.signalements.filters = requestResponse.filters
        this.sharedState.signalements.list = requestResponse.list
        this.sharedState.signalements.pagination = requestResponse.pagination
        this.loadingList = false
        window.scrollTo(0, 0)
      }
    },
    handleFilters () {
      this.clearScreen()

      if (this.abortRequest) {
        this.abortRequest?.abort()
      }

      this.abortRequest = new AbortController()

      const url = new URL(window.location.toString())
      url.search = ''
      this.sharedState.input.queryParameters = []

      for (const [key, value] of Object.entries(this.sharedState.input.filters)) {
        if (value) {
          if (key === 'dateDepot' || key === 'dateDernierSuivi') {
            const [dateDebut, dateFin] = this.handleDateParameter(key, value)
            url.searchParams.set(`${key}Debut`, dateDebut)
            url.searchParams.set(`${key}Fin`, dateFin)
            url.searchParams.delete(key)
          } else if (typeof value === 'object' && (key === 'partenaires' || key === 'communes' || key === 'etiquettes' || key === 'zones')) {
            value.forEach((valueItem: any) => {
              this.addQueryParameter(`${key}[]`, valueItem)
              url.searchParams.append(`${key}[]`, valueItem)
            })
          } else if (typeof value === 'object' && key === 'epcis') {
            if (!localStorage.getItem('epci')) {
              requests.getSettings(this.handleSettings)
            }
            value.forEach((valueItem: any) => {
              const matches = PATTERN_BADGE_EPCI.exec(valueItem)
              if (matches) {
                const valueQueryParameter = matches[0].trim()
                this.addQueryParameter(`${key}[]`, valueQueryParameter)
                url.searchParams.append(`${key}[]`, valueQueryParameter)
              }
            })
          } else if (typeof value === 'string') {
            this.addQueryParameter(key, value)
            url.searchParams.set(key, value)
          }
        } else {
          this.removeQueryParameter(key)
          url.searchParams.delete(key)
        }
      }

      const [field, direction] = this.sharedState.input.order.split('-')
      url.searchParams.set('sortBy', field)
      url.searchParams.set('direction', direction)
      this.addQueryParameter('sortBy', field)
      this.addQueryParameter('orderBy', direction)

      window.history.pushState({}, '', decodeURIComponent(url.toString()))
      this.buildUrl()
      requests.getSignalements(this.handleSignalements, { signal: this.abortRequest?.signal })
    },
    handleDateParameter (key: string, value: any) {
      const dateDebut = new Date(value[0]).toISOString().split('T')[0]
      const dateFin = new Date(value[1]).toISOString().split('T')[0]
      this.addQueryParameter(`${key}Debut`, dateDebut)
      this.addQueryParameter(`${key}Fin`, dateFin)
      this.removeQueryParameter(key)

      return [dateDebut, dateFin]
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
        .map(parameter => `${parameter.name}=${parameter.value}`).join('&')
      this.sharedProps.ajaxurlSignalement = initElements.dataset.ajaxurl + '?' + queryParams
      localStorage.setItem('back_link_signalement_view', queryParams)
    },
    clearScreen () {
      this.messageDeleteConfirmation = ''
      this.classNameDeleteConfirmation = ''
      this.loadingList = true
    }
  }
})

</script>

<style>
#histo-app-signalement-carto .fr-container--fluid {
  overflow: visible;
}
</style>
