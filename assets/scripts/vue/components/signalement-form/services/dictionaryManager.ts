import formStore from '../store'
import dictionaryStore from '../dictionary-store'
import { variablesReplacer } from './variableReplacer'

export const dictionaryManager = {
  translate (slug: string, context: string): string {
    if (dictionaryStore[slug] === undefined) {
      return slug
    }

    let translation = ''

    if (dictionaryStore[slug][context] !== undefined) {
      if (formStore.data.signalement_concerne_profil === 'logement_occupez' && dictionaryStore[slug][context].occupant !== undefined) {
        translation = dictionaryStore[slug][context].occupant
      } else if (formStore.data.signalement_concerne_profil === 'autre_logement' && dictionaryStore[slug][context].tiers !== undefined) {
        translation = dictionaryStore[slug][context].tiers
      } else if (dictionaryStore[slug][context].default !== undefined) {
        translation = dictionaryStore[slug][context].default
      }
    }

    if (translation === '') {
      translation = dictionaryStore[slug].default
    }

    return variablesReplacer.replace(translation)
  }
}
