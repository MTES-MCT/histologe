import formStore from '../store'
import dictionaryStore from '../dictionary-store'

export const variablesReplacer = {
  replace (texteToReplace: string): string {
    const descriptionWithValues = texteToReplace.replace(/\{\{([\w.:]+)\}\}/g, (match, expression) => {
      const value = this.evaluateExpression(expression)
      return value ?? match
    })

    return descriptionWithValues
  },
  evaluateExpression (expression: string): string | undefined {
    const isDictionary = expression.includes('::')
    const keys = isDictionary ? expression.split('::')[1].split('.') : expression.split('.')

    let value: any = formStore
    const dictionary: any = dictionaryStore

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
      value = dictionary[value]
    }

    return value
  }
}
