import formStore from '../store'
import dictionaryStore from '../dictionary-store'

export function resolveDictionaryValue (slug: string, context?: string): string {
  if (dictionaryStore[slug] === undefined) {
    return slug
  }

  let translation = ''

  if (context && dictionaryStore[slug][context] !== undefined) {
    const variant = dictionaryStore[slug][context]
    if (formStore.data.signalement_concerne_profil === 'logement_occupez' && variant.occupant !== undefined) {
      translation = variant.occupant
    } else if (formStore.data.signalement_concerne_profil === 'autre_logement' && variant.tiers !== undefined) {
      translation = variant.tiers
    } else if (variant.default !== undefined) {
      translation = variant.default
    }
  }

  if (translation === '') {
    translation = dictionaryStore[slug].default
  }

  return translation
}
