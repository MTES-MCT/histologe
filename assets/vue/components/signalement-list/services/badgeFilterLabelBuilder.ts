import { store } from '../store'

export function buildBadge (key: string, value: any): string | undefined {
  if (typeof value === 'undefined') {
    return undefined
  }

  if (key === 'territoires') {
    return store.state.territories.find(territory => territory.Id.toString() === value)?.Text
  }

  if (key === 'etiquettes' || key === 'partenaires') {
    const matchedItems = store.state[key]
      .filter(item => item.Id !== '' && Array.from(value).includes(item.Id.toString()))

    if (matchedItems.length > 0) {
      return `${matchedItems[0].Text}${matchedItems.length > 1
                ? ` +${matchedItems.length - 1}`
                : ''}`
    }
    return undefined
  }

  if (key === 'communes' || key === 'epcis') {
    return value.join(', ')
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
    return buildRandgeDateBadge(key, value)
  }

  return builStaticBadge(key, value)
}

function buildRandgeDateBadge (key: string, value: any): string | undefined {
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

function builStaticBadge (key: string, value: any): string | undefined {
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
