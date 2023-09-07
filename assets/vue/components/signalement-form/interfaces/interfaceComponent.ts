import { ZoneComponents } from './interfaceZoneComponents'

export interface Component {
  type: string
  label: string
  slug: string
  repeat?: {
    count: string
  }
  components: ZoneComponents
  // TODO ajouter toutes les propriétés possibles ?
}
