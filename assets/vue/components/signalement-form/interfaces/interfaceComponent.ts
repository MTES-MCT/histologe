import { ZoneComponents } from './interfaceZoneComponents'

export interface Component {
  type: string
  label: string
  slug: string
  repeat?: {
    count: string
  }
  components?: ZoneComponents
  customCss?: string
  isCloned?: boolean
  labelInfo?: string
  labelUpload?: string
  description?: string
  icons?: string
  action?: string
  link?: string
  linktarget?: string
  values?: any
  defaultValue?: string
  validate?: string
  disabled?: string
  multiple?: string
  conditional?: any
}
