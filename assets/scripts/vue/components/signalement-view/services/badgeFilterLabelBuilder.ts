import { PATTERN_BADGE_EPCI, store } from '../store'
import HistoInterfaceSelectOption from '../../common/HistoInterfaceSelectOption'

export function buildBadge (key: string, value: any): string | undefined | null {
  if (typeof value === 'undefined') {
    return undefined
  }

  if (key === 'territoire') {
    return store.state.territories.find(territory => territory.Id.toString() === value)?.Text
  }

  if (key === 'bailleurSocial') {
    return store.state.bailleursSociaux.find(bailleurSocial => bailleurSocial.Id.toString() === value)?.Text
  }
  if (key === 'etiquettes' || key === 'partenaires' || key === 'zones') {
    const matchedItems = store.state[key]
      .filter(item => item.Id !== '' && Array.from(value).includes(item.Id.toString()))

    if (Array.from(value)[0] === 'AUCUN') {
      const optionItem = new HistoInterfaceSelectOption()
      optionItem.Id = '0'
      optionItem.Text = 'Aucun'
      matchedItems.push(optionItem)
    }
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
    const epciData = localStorage.getItem('epci')
    if (epciData !== null) {
      const listEpci = JSON.parse(epciData)
      if (value.length > 0) {
        console.log(value)
        const listEpciAsString = value
          .map((item: string) => {
            const matches = PATTERN_BADGE_EPCI.exec(item)
            return matches !== null
              ? listEpci.filter((itemEpci: string) => itemEpci.includes(matches[0]))
              : ''
          })
          .join(', ')

          console.log(listEpciAsString)
        return listEpciAsString.length > 0 ? `EPCI : ${listEpciAsString}` : null
      }
    }
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

  if (key === 'sansSuiviPeriode') {
    return 'Sans suivi depuis au moins 30 jours'
  }

  if (key === 'relancesUsager') {
    return 'Pas de suivi après 3 relances'
  }

  if (key === 'createdFrom') {
      return  `Signalement crée depuis le ${value.replace('-', ' ')}`
  }

  if (key === 'usagerAbandonProcedure') {
    return 'Demande fermeture usager'
  }

  if (key === 'nouveauSuivi') {
    return 'Nouveaux suivis partenaires et usagers'
  }

  if (key === 'procedure') {
    const item = store.state.procedureList.find(item => item.Id === value)
    if (item != null) {
      return 'Procédure suspectée : ' + item.Text
    }
  }

  if (key === 'procedureConstatee') {
    const item = store.state.procedureConstateeList.find(item => item.Id === value)
    if (item != null) {
      return 'Procédure constatée : ' + item.Text
    }
  }

  if (key === 'motifCloture') {
    const item = store.state.motifClotureList.find(item => item.Id === value)
    if (item != null) {
      return 'Motif de clôture : ' + item.Text
    }
  }

  if (key === 'relanceUsagerSansReponse') {
    return 'Relances usager restées sans réponse'
  }
  if (key === 'isMessagePostCloture') {
    return 'Messages usagers après fermeture'
  }
  if (key === 'isNouveauMessage') {
    return 'Nouveaux messages usagers'
  }
  if (key === 'isMessageWithoutResponse') {
    return 'Messages usagers sans réponse'
  }
  if (key === 'isDossiersSansActivite') {
    return 'Dossiers sans activité partenaire'
  }
    
  return buildStaticBadge(value)
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

function buildStaticBadge (value: any): string | undefined {
  const staticListsWithNoDuplicateId = [
    store.state.statusSignalementList,
    store.state.statusAffectationList,
    store.state.statusVisiteList,
    store.state.situationList,
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
