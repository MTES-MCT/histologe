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

    const values: any = formStore
    const dictionary: any = dictionaryStore
    let value

    for (const key of keys) {
      if (key !== 'formStore' && key !== 'data') {
        if (values.data[key] !== undefined) {
          value = isDictionary ? dictionary[values.data[key]] : values.data[key]
        } else {
          return undefined
        }
      }
    }

    return value
  }
}
