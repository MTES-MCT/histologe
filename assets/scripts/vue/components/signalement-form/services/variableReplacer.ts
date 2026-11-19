import formStore from '../store'
import dictionaryStore from '../dictionary-store'
import { resolveDictionaryValue } from './dictionaryResolver'

const HTML_ESCAPE_MAP: Record<string, string> = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;'
}

function escapeHtml (value: string): string {
  return value.replace(/[&<>"']/g, (char) => HTML_ESCAPE_MAP[char])
}

export const variablesReplacer = {
  /**
   * Remplace les variables pour un affichage en texte (interpolation Vue, attributs).
   * Le résultat ne doit PAS être injecté via v-html : utiliser replaceHtml().
   */
  replace (textToReplace: string | number | undefined): string {
    return this.replaceVariables(textToReplace, false)
  },
  /**
   * Remplace les variables pour un affichage via v-html : le texte source (JSON interne)
   * est conservé tel quel, mais les valeurs issues de la saisie usager sont échappées.
   */
  replaceHtml (textToReplace: string | number | undefined): string {
    return this.replaceVariables(textToReplace, true)
  },
  replaceVariables (textToReplace: string | number | undefined, escape: boolean): string {
    if (textToReplace === undefined || textToReplace === null) {
      return ''
    }
    return String(textToReplace).replace(/\{\{([\w.:]+)\}\}/g, (match, expression) => {
      const value = this.evaluateExpression(expression, escape)
      return value ?? match
    })
  },
  evaluateExpression (expression: string, escape: boolean = false): string | undefined {
    const isDictionary = expression.includes('::')
    const path = isDictionary ? expression.split('::')[1] : expression
    const prefix = isDictionary ? expression.split('::')[0] : undefined
    const context = prefix?.includes(':') ? prefix.split(':')[1] : undefined
    const keys = path.split('.')

    if (isDictionary && !expression.includes('formStore')) {
      return resolveDictionaryValue(keys[0], context)
    }

    let value: any = formStore

    for (const key of keys) {
      if (key !== 'formStore') {
        if (value[key] !== undefined) {
          value = value[key]
        } else {
          return undefined
        }
      }
    }

    if (isDictionary) {
      // Les traductions du dictionnaire sont internes (HTML autorisé),
      // mais une valeur absente du dictionnaire est renvoyée telle quelle : elle vient de l'usager.
      if (escape && (typeof value !== 'string' || !Object.prototype.hasOwnProperty.call(dictionaryStore, value))) {
        return escapeHtml(String(value))
      }
      return resolveDictionaryValue(value, context)
    }

    return escape ? escapeHtml(String(value)) : value
  }
}
