import formStore from '../store'

export const variablesReplacer = {
  replace (texteToReplace: string): string {
    const descriptionWithValues = texteToReplace.replace(/\{\{([\w.]+)\}\}/g, (match, expression) => {
      const value = this.evaluateExpression(expression)
      return value ?? match
    })

    return descriptionWithValues
  },
  evaluateExpression (expression: string): string | undefined {
    const keys = expression.split('.')
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

    return value
  }
}
