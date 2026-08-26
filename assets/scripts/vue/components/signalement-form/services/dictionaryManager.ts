import { variablesReplacer } from './variableReplacer'
import { resolveDictionaryValue } from './dictionaryResolver'

export const dictionaryManager = {
  translate (slug: string, context: string): string {
    return variablesReplacer.replace(resolveDictionaryValue(slug, context))
  }
}
