import type { QueryParameter } from '../common/interfaces/queryParameter'
import type HistoInterfaceSelectOption from '../common/HistoInterfaceSelectOption'

export enum ViewMode {
  Map = 'map',
  List = 'list'
}

export enum NatureParc {
  Privee = 'privee',
  Public = 'public',
  NonRenseigne = 'non_renseigne'
}

export enum DossiersMultiples {
  All = '',
  Avec = 'oui',
  Sans = 'non'
}

export enum ArreteType {
  MiseEnSecurite = 'MISE_EN_SECURITE',
  ArreteL51111Impropre = 'ARRETE_L_511_11_IMPROPRE'
}

export interface Pagination {
  current_page: number
  total_pages: number
  total_items: number
  per_page: number
}

export interface AddressesState {
  filters: Record<string, unknown>
  list: Array<Record<string, unknown>>
  allAddresses: Array<Record<string, unknown>>
  pagination: Pagination
  zoneAreas: string[]
}

export interface Filters {
  territoire?: string
  adresse?: string
  communes: string[]
  bailleurOuSyndic?: string
  zone?: string
  natureParc?: string
  dossiersMultiples?: string
  arreteTypes: string[]
}

export interface InputState {
  order: string
  queryParameters: QueryParameter[]
  filters: Filters
}

export interface UserState {
  isAdmin: boolean
  isResponsableTerritoire: boolean
  isAdministrateurPartenaire: boolean
  isAgent: boolean
  isMultiTerritoire: boolean
  partnerIds: string[]
}

export interface StoreState {
  addresses: AddressesState
  input: InputState
  user: UserState
  territories: HistoInterfaceSelectOption[]
  communes: string[]
  bailleursAndSyndic: string[]
  zones: HistoInterfaceSelectOption[]
  currentTerritoryId: string
  currentCommunes: string
  viewMode: ViewMode
  loadingList: boolean
  hasErrorLoading: boolean
  natureParcList: Array<{ Id: string; Text: string }>
  dossiersMultiplesList: Array<{ Id: string; Text: string }>
  arreteTypes: Array<{ Id: string; Text: string }>
  filtersApplyKey: number
}

export interface StoreProps {
  ajaxurlAddresses: string
  baseAjaxUrlAddresses: string
  ajaxurlExportCsv: string
  ajaxurlSettings: string
  platformName: string
  token: string
}

export interface RequestOptions {
  method?: string
  body?: unknown
  headers?: Record<string, string>
}

export interface AddressesResponse {
  addresses: {
    list: Array<Record<string, unknown>>
    allAddresses: Array<Record<string, unknown>>
    pagination: Pagination
    zoneAreas: string[]
  }
}

export interface SettingsResponse {
  roleLabel?: string
  isMultiTerritoire?: boolean
  territories?: Record<string, { id: string; zip: string; name: string }>
  communes?: Record<string, string>
  zones?: Record<string, { id: number; name: string }>
  bailleursSociaux?: Record<string, { id: number; name: string }>
  typesArretes?: Record<string, Array<{ Id: string; Text: string }>>
}
