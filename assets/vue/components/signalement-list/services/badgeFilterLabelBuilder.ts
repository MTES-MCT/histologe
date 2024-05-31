import { store } from '../store'

export function buildBadge (key: string, value: any): string | undefined {
  if (typeof value === 'undefined') {
    return undefined
  }

  if (key === 'territoire') {
    return store.state.territories.find(territory => territory.Id.toString() === value)?.Text
  }

  if (key === 'etiquettes' || key === 'partenaires') {
    const matchedItems = store.state[key]
      .filter(item => item.Id !== '' && Array.from(value).includes(item.Id.toString()))

    if (matchedItems.length > 0) {
      const label = `${key.charAt(0).toUpperCase()}${key.slice(1)} : `
      return `${label} ${matchedItems[0].Text}${matchedItems.length > 1
                ? ` +${matchedItems.length - 1}`
                : ''}`
    }
    return undefined
  }

  if (key === 'communes' && value instanceof Array) {
    const values: string = value.join(', ')
    return `Commune ou code postal :  ${values}`
  }

  if (key === 'epcis' && value instanceof Array) {
    return `EPCI : ${value.map((item: string) => item.split('|')[1]).join(', ')}`
  }

  if (key === 'enfantsM6') {
    const item = store.state.enfantMoinsSixList.find(item => item.Id === value)
    if (item != null) {
      return item.Text
    }
  }

  if (key === 'allocataire') {
    const item = store.state.allocataireList.find(item => item.Id === value)
    if (item != null) {
      return item.Text
    }
  }

  if (typeof value === 'string' && key === 'criticiteScoreMin') {
    return `Criticité Minimum : ${value}`
  }

  if (typeof value === 'string' && key === 'criticiteScoreMax') {
    return `Criticité Maximum : ${value}`
  }

  if (key === 'dateDernierSuivi' || key === 'dateDepot') {
    return buildRangeDateBadge(key, value)
  }

  return buildStaticBadge(key, value)
}

function buildRangeDateBadge (key: string, value: any): string | undefined {
  let label: string = ''
  let startDate: string = ''
  let endDate: string = ''

  startDate = value[0].toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  endDate = value[1].toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })

  if (key === 'dateDernierSuivi') {
    label = 'Date de dernier suivi : '
  }

  if (key === 'dateDepot') {
    label = 'Date de dépôt : '
  }

  return `${label} ${startDate} - ${endDate}`
}

function buildStaticBadge (_key: string, value: any): string | undefined {
  const staticListsWithNoDuplicateId = [
    store.state.statusSignalementList,
    store.state.statusAffectationList,
    store.state.statusVisiteList,
    store.state.situationList,
    store.state.procedureList,
    store.state.typeDernierSuiviList,
    store.state.typeDeclarantList,
    store.state.natureParcList
  ]

  for (const list of staticListsWithNoDuplicateId) {
    const item = list.find(item => item.Id === value)
    if (item != null) {
      return item.Text
    }
  }

  return value
}
