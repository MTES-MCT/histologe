import formStore from '../store'
import { resolveDictionaryValue } from './dictionaryResolver'

export const variablesReplacer = {
  replace (textToReplace: string | undefined): string {
    console.log('variablesReplacer.replace', textToReplace)
    if (textToReplace === undefined || textToReplace === null) {
      return ''
    }
    const descriptionWithValues = textToReplace.replace(/\{\{([\w.:]+)\}\}/g, (match, expression) => {
      console.log('variablesReplacer.replace: match', match, 'expression', expression)
      const value = this.evaluateExpression(expression)
      return value ?? match
    })

    return descriptionWithValues
  },
  evaluateExpression (expression: string): string | undefined {
    console.log('variablesReplacer.evaluateExpression', expression)
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
      value = resolveDictionaryValue(value, context)
    }

    return value
  }
}
