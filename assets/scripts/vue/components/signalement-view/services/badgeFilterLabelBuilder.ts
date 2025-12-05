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
    if (epciData !== null && epciData !== undefined) {
      const listEpci = JSON.parse(epciData)
      if (value.length > 0) {
        const listEpciAsString = value
          .map((item: string) => {
            const matches = PATTERN_BADGE_EPCI.exec(item)
            return matches !== null
              ? listEpci.filter((itemEpci: string) => itemEpci.includes(matches[0]))
              : ''
          })
          .join(', ')

        return listEpciAsString.length > 0 ? `EPCI : ${listEpciAsString}` : null
      }
    }
  }

  if (key === 'enfantsM6') {
    const item = store.state.enfantMoinsSixList.find(item => item.Id === value)
    if (item != null && item !== undefined) {
      return item.Text
    }
  }

  if (key === 'allocataire') {
    const item = store.state.allocataireList.find(item => item.Id === value)
    if (item != null && item !== undefined) {
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

  if (key === 'createdFrom') {
      return  `Signalement crée depuis le ${value.replace('-', ' ')}`
  }

  if (key === 'usagerAbandonProcedure') {
    return 'Demande fermeture usager'
  }

  if (key === 'procedure') {
    const item = store.state.procedureList.find(item => item.Id === value)
    if (item != null && item !== undefined) {
      return 'Procédure suspectée : ' + item.Text
    }
  }

  if (key === 'procedureConstatee') {
    const item = store.state.procedureConstateeList.find(item => item.Id === value)
    if (item != null && item !== undefined) {
      return 'Procédure constatée : ' + item.Text
    }
  }

  if (key === 'motifCloture') {
    const item = store.state.motifClotureList.find(item => item.Id === value)
    if (item != null && item !== undefined) {
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
  if (key === 'isEmailAVerifier') {
    return 'Adresses e-mail usager à vérifier'
  }
  if (key === 'isDossiersSansAgent') {
    return 'Dossiers sans agent'
  }
  if (key === 'isActiviteRecente') {
    return 'Dossiers avec activité récente'
  }
    
  return buildStaticBadge(value)
}

function buildRangeDateBadge (key: string, value: any): string | undefined {
  if (!value || value.length < 2) return undefined;
  const toDate = (v: any) => (v instanceof Date ? v : new Date(v));

  const startDateObj = toDate(value[0]);
  const endDateObj = toDate(value[1]);

  if (Number.isNaN(startDateObj.getTime()) || Number.isNaN(endDateObj.getTime())) return undefined;

  const startDate = startDateObj.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  const endDate = endDateObj.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });

  let label = '';
  if (key === 'dateDernierSuivi') {
    label = 'Date de dernier suivi :';
  } else if (key === 'dateDepot') {
    label = 'Date de dépôt :';
  }

  return `${label} ${startDate} - ${endDate}`;
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
    if (item != null && item !== undefined) {
      return item.Text
    }
  }

  return value
}
