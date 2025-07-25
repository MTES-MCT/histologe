import { requests } from '../requests'
import { PATTERN_BADGE_EPCI, store } from '../store'
import { Filters, SEARCH_FILTERS } from '../interfaces/filters'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'
import { variableTester } from '../../../utils/variableTester'
import { QueryParameter } from '../interfaces/queryParameter'

export function handleQueryParameter (context: any): any {
  const url = new URL(window.location.toString())
  const params = new URLSearchParams(url.search)
  const filters = context.sharedState.input.filters as Filters
  for (const filter of SEARCH_FILTERS) {
    const type = filter.type
    const key = filter.name
    const value = params.get(key)
    const epciData = localStorage.getItem('epci')
    let valueList = params.getAll(`${key}[]`)
    if (variableTester.isNotEmpty(value) && value.length > 0) {
      if (['sortBy', 'direction', 'page'].includes(key)) {
        addQueryParameter(context, key, value)
        continue
      }
      if (type === 'text') {
        filters[key] = filter?.defaultValue ?? (variableTester.isNotEmpty(value) ? value : null)
        addQueryParameter(context, key, value)
      } else if (type === 'date') {
        const keyDebut = key
        const keyFin = key.replace('Debut', 'Fin')
        const newKey = key.replace('Debut', '')
        const dateDebut = params.get(keyDebut)
        const dateFin = params.get(keyFin)
        if (variableTester.isNotEmpty(dateDebut) && variableTester.isNotEmpty(dateFin)) {
          addQueryParameter(context, keyDebut, dateDebut)
          const dateDebutFormatted = new Date(dateDebut)
          addQueryParameter(context, keyFin, dateFin)
          const dateFinFormatted = new Date(dateFin)
          filters[newKey] = [dateDebutFormatted, dateFinFormatted]
        }
      }
    } else if (variableTester.isNotEmpty(valueList) && valueList.length > 0) {
      if (type === 'collection') {
        valueList = params.getAll(`${key}[]`)
        if (variableTester.isNotEmpty(valueList) && valueList.length > 0) {
          valueList.forEach(valueItem => {
            addQueryParameter(context, `${key}[]`, valueItem.trim())
            if (key === 'epcis' && variableTester.isNotEmpty(epciData)) {
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
    if (variableTester.isNotEmpty(value) && value.length > 0) {
      context.sharedState.showOptions = filter.showOptions
    }
  }
}

export function handleSettings (context: any, requestResponse: any): any {
  context.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
  context.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Resp. Territoire'
  context.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Admin. partenaire'
  context.sharedState.user.isAgent = ['Admin. partenaire', 'Agent'].includes(requestResponse.roleLabel)
  context.sharedState.user.isMultiTerritoire = requestResponse.isMultiTerritoire === true
  const isAdminOrAdminTerritoire = context.sharedState.user.isAdmin === true || context.sharedState.user.isResponsableTerritoire === true
  context.sharedState.user.canSeeStatusAffectation = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeBailleurSocial = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeFilterPartner = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeScore = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeMySignalementsButton = requestResponse.isFeatureNewDashboard // TODO: FEATURE_NEW_DASHBOARD feature flipping, remove when not needed
  context.sharedState.user.partnerIds = requestResponse.partnerIds
  context.sharedState.hasSignalementImported = requestResponse.hasSignalementImported
  context.sharedState.input.order = 'reference-DESC'
  context.sharedState.input.filters.isImported = 'oui'

  context.sharedState.territories = []
  for (const id in requestResponse.territories) {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = requestResponse.territories[id].id
    if (variableTester.isNotEmpty(requestResponse.territories[id])) {
      const territory = requestResponse.territories[id]
      optionItem.Text = (territory.zip as string) + ' - ' + (territory.name as string)
    } else {
      optionItem.Text = 'Territoire inconnu'
    }
    context.sharedState.territories.push(optionItem)
  }

  context.sharedState.partenaires = []
  const optionNoneItem = new HistoInterfaceSelectOption()
  optionNoneItem.Id = 'AUCUN'
  optionNoneItem.Text = 'Aucun'
  context.sharedState.partenaires.push(optionNoneItem)
  const partnersArray = Object.values(requestResponse.partners)
  partnersArray.sort((a: any, b: any) => (a.nom > b.nom) ? 1 : ((b.nom > a.nom) ? -1 : 0))
  partnersArray.forEach((partner: any) => {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = partner.id.toString()
    optionItem.Text = partner.nom
    context.sharedState.partenaires.push(optionItem)
  })

  context.sharedState.etiquettes = []
  optionNoneItem.Id = ''
  optionNoneItem.Text = ''
  context.sharedState.etiquettes.push(optionNoneItem)
  const tagsArray = Object.values(requestResponse.tags)
  tagsArray.sort((a: any, b: any) => (a.label > b.label) ? 1 : ((b.label > a.label) ? -1 : 0))
  tagsArray.forEach((tag: any) => {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = tag.id.toString()
    optionItem.Text = tag.label
    context.sharedState.etiquettes.push(optionItem)
  })

  context.sharedState.zones = []
  const zonesArray = Object.values(requestResponse.zones)
  zonesArray.forEach((zone: any) => {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = zone.id.toString()
    optionItem.Text = zone.name
    context.sharedState.zones.push(optionItem)
  })

  context.sharedState.bailleursSociaux = []
  for (const id in requestResponse.bailleursSociaux) {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = requestResponse.bailleursSociaux[id].id.toString()
    optionItem.Text = requestResponse.bailleursSociaux[id].name
    context.sharedState.bailleursSociaux.push(optionItem)
  }

  context.sharedState.communes = []
  for (const id in requestResponse.communes) {
    context.sharedState.communes.push(requestResponse.communes[id])
  }

  context.sharedState.epcis = []
  for (const id in requestResponse.epcis) {
    if (variableTester.isNotEmpty(requestResponse.epcis[id])) {
      const epci = requestResponse.epcis[id]
      context.sharedState.epcis.push((epci.nom as string) + ' ' + (epci.code as string))
    }
  }
  localStorage.setItem('epci', JSON.stringify(context.sharedState.epcis))
}

export function handleTerritoryChange (context: any, value: any): any {
  delete (context.sharedState.input.filters).communes
  delete (context.sharedState.input.filters).epcis
  delete (context.sharedState.input.filters).zones
  context.sharedState.currentTerritoryId = value.toString()
  requests.getSettings(context.handleSettings)
}

export function handleSignalementsShared (context: any, requestResponse: any): any {
  if (typeof requestResponse === 'string' && requestResponse === 'error') {
    context.sharedState.hasErrorLoading = true
  } else {
    context.sharedState.hasErrorLoading = false
    context.sharedState.signalements.filters = requestResponse.filters
    context.sharedState.signalements.list = requestResponse.list
    context.sharedState.signalements.pagination = requestResponse.pagination
    context.sharedState.signalements.zoneAreas = requestResponse.zoneAreas
    context.sharedState.loadingList = false
    if (context.sharedProps.ajaxurlSignalement.includes('cartographie') === false) {
      window.scrollTo(0, 0)
    }
  }
}

export function handleFilters (context: any, ajaxurl: string): any {
  clearScreen(context)

  if (context.abortRequest !== null) {
    context.abortRequest?.abort()
  }

  context.abortRequest = new AbortController()

  const url = new URL(window.location.toString())
  url.search = ''
  context.sharedState.input.queryParameters = []

  for (const [key, value] of Object.entries(context.sharedState.input.filters)) {
    if (variableTester.isNotEmpty(value)) {
      if (key === 'dateDepot' || key === 'dateDernierSuivi') {
        const [dateDebut, dateFin] = handleDateParameter(context, key, value)
        url.searchParams.set(`${key}Debut`, dateDebut)
        url.searchParams.set(`${key}Fin`, dateFin)
        url.searchParams.delete(key)
      } else if (Array.isArray(value) && (['partenaires', 'communes', 'etiquettes', 'zones'].includes(key))) {
        value.forEach((valueItem: any) => {
          addQueryParameter(context, `${key}[]`, valueItem)
          url.searchParams.append(`${key}[]`, valueItem)
        })
      } else if (Array.isArray(value) && key === 'epcis') {
        if (localStorage.getItem('epci') === null) {
          requests.getSettings(context.handleSettings)
        }
        value.forEach((valueItem: any) => {
          const matches = PATTERN_BADGE_EPCI.exec(valueItem)
          if (matches != null) {
            const valueQueryParameter = matches[0].trim()
            addQueryParameter(context, `${key}[]`, valueQueryParameter)
            url.searchParams.append(`${key}[]`, valueQueryParameter)
          }
        })
      } else if (typeof value === 'string') {
        addQueryParameter(context, key, value)
        url.searchParams.set(key, value)
      }
    } else {
      removeQueryParameter(context, key)
      url.searchParams.delete(key)
    }
  }

  const [field, direction] = context.sharedState.input.order.split('-')
  url.searchParams.set('sortBy', field)
  url.searchParams.set('direction', direction)
  addQueryParameter(context, 'sortBy', field)
  addQueryParameter(context, 'orderBy', direction)

  window.history.pushState({}, '', decodeURIComponent(url.toString()))
  buildUrl(context, ajaxurl)
  requests.getSignalements(context.handleSignalements, { signal: context.abortRequest?.signal })
}

export function handleDateParameter (context: any, key: string, value: any): any {
  const dateDebut = new Date(value[0]).toISOString().split('T')[0]
  const dateFin = new Date(value[1]).toISOString().split('T')[0]
  addQueryParameter(context, `${key}Debut`, dateDebut)
  addQueryParameter(context, `${key}Fin`, dateFin)
  removeQueryParameter(context, key)

  return [dateDebut, dateFin]
}

export function addQueryParameter (context: any, name: string, value: string): any {
  const param = store
    .state
    .input
    .queryParameters
    .find((parameter: any) => parameter.name === name && parameter.value === value)
  if (param != null) {
    param.value = value
  } else {
    context.sharedState.input.queryParameters.push({ name, value })
  }
}

export function removeQueryParameter (context: any, name: string): any {
  const index = context.sharedState.input.queryParameters.findIndex((parameter: any) => parameter.name === name)
  if (index !== -1) {
    context.sharedState.input.queryParameters.splice(index, 1)
  }
}

export function buildUrl (context: any, ajaxurl: string): any {
  const queryParams = context
    .sharedState
    .input
    .queryParameters
    .map((parameter: QueryParameter) => `${parameter.name}=${parameter.value}`)
    .join('&')
  context.sharedProps.ajaxurlSignalement = ajaxurl + '?' + (queryParams as string)
  if (!ajaxurl.includes('cartographie')) {
    // on n'enregistre en localStorage les filtres que si on est sur la liste de signalements
    localStorage.setItem('back_link_signalement_view', queryParams)
  }
}

export function clearScreen (context: any): any {
  context.messageDeleteConfirmation = ''
  context.classNameDeleteConfirmation = ''
  context.sharedState.loadingList = true
}

export function removeLocalStorage (context: any): any {
  if (context.sharedProps.ajaxurlSignalement.includes('cartographie') === false) {
    // on n'enregistre en localStorage les filtres que si on est sur la liste de signalements
    localStorage.removeItem('back_link_signalement_view')
  }
}
