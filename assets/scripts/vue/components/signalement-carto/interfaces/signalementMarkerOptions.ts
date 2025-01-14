import { MarkerOptions } from 'leaflet'

export interface SignalementMarkerOptions extends MarkerOptions {
  id?: string
  status: string
  address?: string
  zip?: string
  city?: string
  reference?: string
  score?: number
  name?: string
  url?: string
  details?: string
}
