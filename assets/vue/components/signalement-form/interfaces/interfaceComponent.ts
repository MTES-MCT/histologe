import { ZoneComponents } from './interfaceZoneComponents'

export interface Component {
  type: string
  label: string
  slug: string
  repeat?: {
    count: string
  }
  components: ZoneComponents
  customCss: string
  isCloned: boolean
  // TODO ajouter toutes les propriétés possibles ?
}
