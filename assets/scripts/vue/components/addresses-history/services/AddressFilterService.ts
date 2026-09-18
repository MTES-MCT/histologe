import type { AddressesHistoryFilters } from '../composables/useAddressesHistoryFilters'

export interface Address {
  territoryId?: number | string
  ville?: string
  cp?: string
  signalements?: any[]
  arretes?: Array<{
    arreteType: string
    dateMainLevee?: string | null
  }>
  hasLogementSocial?: boolean
  hasLogementPrive?: boolean
  hasLogementNatureNonRenseigne?: boolean
  lat?: number
  lng?: number
  addressForHuman?: string
  communeForHuman?: string
  bailleurNames?: string[]
}

export interface FilterParams {
  mainLeveeUniquement: boolean
}

export class AddressFilterService {
  /**
   * Filtre une liste d'adresses selon les critères définis
   */
  static filterAddresses(
    addresses: Address[],
    filters: AddressesHistoryFilters,
    params: FilterParams
  ): Address[] {
    let filteredAddresses = [...addresses]

    filteredAddresses = this.filterByTerritoire(filteredAddresses, filters.territoire)
    filteredAddresses = this.filterByCommuneOuEpci(filteredAddresses, filters.communeOuEpci)
    filteredAddresses = this.filterByDossiersMultiples(filteredAddresses, filters.dossiersMultiples)
    filteredAddresses = this.filterByMainLevee(filteredAddresses, params.mainLeveeUniquement)
    filteredAddresses = this.filterByArreteTypes(filteredAddresses, filters.arreteTypes)
    filteredAddresses = this.filterByNatureParc(filteredAddresses, filters.natureParc)
    filteredAddresses = this.filterByBailleurOuSyndic(filteredAddresses, filters.bailleurOuSyndic)

    return filteredAddresses
  }

  /**
   * Filtre par territoire
   */
  private static filterByTerritoire(addresses: Address[], territoire?: string): Address[] {
    if (!territoire) {
      return addresses
    }

    return addresses.filter((address) => {
      return address.territoryId?.toString() === territoire
    })
  }

  /**
   * Filtre par commune ou EPCI
   */
  private static filterByCommuneOuEpci(addresses: Address[], communeOuEpci?: string): Address[] {
    if (!communeOuEpci) {
      return addresses
    }

    const searchTerm = communeOuEpci.toLowerCase()

    return addresses.filter((address) => {
      return address.ville?.toLowerCase().includes(searchTerm) ||
             address.cp?.includes(communeOuEpci)
    })
  }

  /**
   * Filtre par dossiers multiples
   */
  private static filterByDossiersMultiples(addresses: Address[], dossiersMultiples?: string): Address[] {
    if (dossiersMultiples !== 'oui') {
      return addresses
    }

    return addresses.filter((address) => {
      const nbSignalements = address.signalements?.length || 0
      return nbSignalements > 1
    })
  }

  /**
   * Filtre pour main levée uniquement
   */
  private static filterByMainLevee(addresses: Address[], mainLeveeUniquement: boolean): Address[] {
    if (!mainLeveeUniquement) {
      return addresses
    }

    return addresses.filter((address) => {
      return address.arretes?.some((arrete) => arrete.dateMainLevee !== null)
    })
  }

  /**
   * Filtre par types d'arrêtés
   */
  private static filterByArreteTypes(addresses: Address[], arreteTypes: string[]): Address[] {
    if (arreteTypes.length === 0) {
      return addresses
    }

    return addresses.filter((address) => {
      return address.arretes?.some((arrete) =>
        arreteTypes.includes(arrete.arreteType)
      )
    })
  }

  /**
   * Filtre par nature du parc
   */
  private static filterByNatureParc(addresses: Address[], natureParc?: string): Address[] {
    if (!natureParc) {
      return addresses
    }

    return addresses.filter((address) => {
      if (natureParc === 'public') {
        return address.hasLogementSocial === true
      }
      if (natureParc === 'privee') {
        return address.hasLogementPrive === true
      }
      if (natureParc === 'non_renseigne') {
        return address.hasLogementNatureNonRenseigne === true
      }
      return false
    })
  }

  private static filterByBailleurOuSyndic(addresses: Address[], bailleurOuSyndic?: string): Address[] {
    if (!bailleurOuSyndic) {
      return addresses
    }
    return addresses.filter((address) => {
      // Assuming address has a property 'bailleurOuSyndic' which is an array of strings
      return address.bailleurNames?.some((bailleurName) => {
        return bailleurName.includes(bailleurOuSyndic)
      })
    })
  }
}
