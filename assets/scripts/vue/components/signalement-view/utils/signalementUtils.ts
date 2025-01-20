import { requests } from '../requests'
import { PATTERN_BADGE_EPCI, store } from '../store'
import { Filters, SEARCH_FILTERS } from '../interfaces/filters'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'

export function handleQueryParameter (context: any) {
  const url = new URL(window.location.toString())
  const params = new URLSearchParams(url.search)
  const filters = context.sharedState.input.filters as Filters
  for (const filter of SEARCH_FILTERS) {
    const type = filter.type
    const key = filter.name
    const value = params.get(key)
    const epciData = localStorage.getItem('epci')
    let valueList = params.getAll(`${key}[]`)
    if (value && value.length > 0) {
      if (['sortBy', 'direction', 'page'].includes(key)) {
        addQueryParameter(context, key, value)
        continue
      }
      if (type === 'text') {
        filters[key] = filter?.defaultValue || value
        addQueryParameter(context, key, value)
      } else if (type === 'date') {
        const keyDebut = key
        const keyFin = key.replace('Debut', 'Fin')
        const newKey = key.replace('Debut', '')
        const dateDebut = params.get(keyDebut)
        const dateFin = params.get(keyFin)
        if (dateDebut && dateFin) {
          addQueryParameter(context, keyDebut, dateDebut)
          const dateDebutFormatted = new Date(dateDebut)
          addQueryParameter(context, keyFin, dateFin)
          const dateFinFormatted = new Date(dateFin)
          filters[newKey] = [dateDebutFormatted, dateFinFormatted]
        }
      }
    } else if (valueList && valueList.length > 0) {
      if (type === 'collection') {
        valueList = params.getAll(`${key}[]`)
        if (valueList && valueList.length > 0) {
          valueList.forEach(valueItem => {
            addQueryParameter(context, `${key}[]`, valueItem.trim())
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
      context.sharedState.showOptions = filter.showOptions
    }
  }
}

export function handleSettings (context: any, requestResponse: any) {
  context.sharedState.user.isAdmin = requestResponse.roleLabel === 'Super Admin'
  context.sharedState.user.isResponsableTerritoire = requestResponse.roleLabel === 'Resp. Territoire'
  context.sharedState.user.isAdministrateurPartenaire = requestResponse.roleLabel === 'Admin. partenaire'
  context.sharedState.user.isAgent = ['Admin. partenaire', 'Agent'].includes(requestResponse.roleLabel)
  context.sharedState.user.isMultiTerritoire = requestResponse.isMultiTerritoire === true
  const isAdminOrAdminTerritoire = context.sharedState.user.isAdmin || context.sharedState.user.isResponsableTerritoire
  context.sharedState.user.canSeeStatusAffectation = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeBailleurSocial = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeFilterPartner = isAdminOrAdminTerritoire
  context.sharedState.user.canSeeScore = isAdminOrAdminTerritoire
  context.sharedState.user.partnerIds = requestResponse.partnerIds
  context.sharedState.hasSignalementImported = requestResponse.hasSignalementImported
  context.sharedState.input.order = 'reference-DESC'
  context.sharedState.input.filters.isImported = 'oui'

  context.sharedState.territories = []
  for (const id in requestResponse.territories) {
    const optionItem = new HistoInterfaceSelectOption()
    optionItem.Id = requestResponse.territories[id].id
    optionItem.Text = `${requestResponse.territories[id].zip} - ${requestResponse.territories[id].name}`
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
    context.sharedState.epcis.push(`${requestResponse.epcis[id].nom} (${requestResponse.epcis[id].code} )`)
  }
  localStorage.setItem('epci', JSON.stringify(context.sharedState.epcis))
}

export function handleTerritoryChange (context: any, value: any) {
  delete (context.sharedState.input.filters).communes
  delete (context.sharedState.input.filters).epcis
  context.sharedState.currentTerritoryId = value.toString()
  requests.getSettings(context.handleSettings)
}

export function handleSignalementsShared (context: any, requestResponse: any) {
  if (typeof requestResponse === 'string' && requestResponse === 'error') {
    context.hasErrorLoading = true
  } else {
    context.hasErrorLoading = false
    context.sharedState.signalements.filters = requestResponse.filters
    context.sharedState.signalements.list = requestResponse.list
    context.sharedState.signalements.pagination = requestResponse.pagination
    context.loadingList = false
    if (!context.sharedProps.ajaxurlSignalement.includes('cartographie')) {
      window.scrollTo(0, 0)
    }
  }
}

export function handleFilters (context: any, ajaxurl: string) {
  clearScreen(context)

  if (context.abortRequest) {
    context.abortRequest?.abort()
  }

  context.abortRequest = new AbortController()

  const url = new URL(window.location.toString())
  url.search = ''
  context.sharedState.input.queryParameters = []

  for (const [key, value] of Object.entries(context.sharedState.input.filters)) {
    if (value) {
      if (key === 'dateDepot' || key === 'dateDernierSuivi') {
        const [dateDebut, dateFin] = handleDateParameter(context, key, value)
        url.searchParams.set(`${key}Debut`, dateDebut)
        url.searchParams.set(`${key}Fin`, dateFin)
        url.searchParams.delete(key)
      } else if (Array.isArray(value) && (key === 'partenaires' || key === 'communes' || key === 'etiquettes' || key === 'zones')) {
        value.forEach((valueItem: any) => {
          addQueryParameter(context, `${key}[]`, valueItem)
          url.searchParams.append(`${key}[]`, valueItem)
        })
      } else if (Array.isArray(value) && key === 'epcis') {
        if (!localStorage.getItem('epci')) {
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

export function handleDateParameter (context: any, key: string, value: any) {
  const dateDebut = new Date(value[0]).toISOString().split('T')[0]
  const dateFin = new Date(value[1]).toISOString().split('T')[0]
  addQueryParameter(context, `${key}Debut`, dateDebut)
  addQueryParameter(context, `${key}Fin`, dateFin)
  removeQueryParameter(context, key)

  return [dateDebut, dateFin]
}

export function addQueryParameter (context: any, name: string, value: string) {
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

export function removeQueryParameter (context: any, name: string) {
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
    .map((parameter: any) => `${parameter.name}=${parameter.value}`).join('&')
  context.sharedProps.ajaxurlSignalement = ajaxurl + '?' + queryParams
  if (!ajaxurl.includes('cartographie')) {
    // on n'enregistre en localStorage les filtres que si on est sur la liste de signalements
    localStorage.setItem('back_link_signalement_view', queryParams)
  }
}

export function clearScreen (context: any): any {
  context.messageDeleteConfirmation = ''
  context.classNameDeleteConfirmation = ''
  context.loadingList = true
}

export function removeLocalStorage (context: any) {
  if (!context.sharedProps.ajaxurlSignalement.includes('cartographie')) {
    // on n'enregistre en localStorage les filtres que si on est sur la liste de signalements
    localStorage.removeItem('back_link_signalement_view')
  }
}
