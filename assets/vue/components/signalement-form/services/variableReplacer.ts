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
    const dictionary: any = dictionaryStore

    const isDictionary = expression.includes('::')
    const keys = isDictionary ? expression.split('::')[1].split('.') : expression.split('.')

    if (isDictionary && !expression.includes('formStore')) {
      return dictionary[keys[0]].default
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

    if (isDictionary && dictionary[value] !== null) {
      value = dictionary[value].default
    }

    return value
  }
}
