import formStore from '../store'
import { isValidPhoneNumber } from 'libphonenumber-js'

export const componentValidator = {
  validate (component: any) {
    const componentSlug: string = component.slug
    const value = formStore.data[componentSlug]

    let regexPattern
    // s'il y a une valeur, on vérifie si un pattern est requis (ou si c'est un type email)
    if (value !== undefined && value !== '' && component.type === 'SignalementFormEmailfield') {
      regexPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    } else if (value !== undefined && value !== '' && component.validate?.pattern !== undefined) {
      regexPattern = new RegExp(component.validate.pattern)
    }
    if (regexPattern !== undefined) {
      if (!regexPattern.test(value)) {
        formStore.validationErrors[componentSlug] = 'Format invalide'
      }
    }

    if (value !== undefined && value !== '' && component.type === 'SignalementFormPhonefield') {
      const countryCode = formStore.data[componentSlug + '_countrycode'].split(':')[0]
      if (!isValidPhoneNumber(value, countryCode)) {
        formStore.validationErrors[componentSlug] = 'Format invalide'
      }

      const valueTelSecond = formStore.data[componentSlug + '_secondaire']
      if (valueTelSecond !== undefined && valueTelSecond !== '') {
        const countryCodeSecond = formStore.data[componentSlug + '_secondaire_countrycode'].split(':')[0]
        if (!isValidPhoneNumber(valueTelSecond, countryCodeSecond)) {
          formStore.validationErrors[componentSlug + '_secondaire'] = 'Format invalide'
        }
      }
    }

    if (value !== undefined && value !== '' && component.validate?.maxLength !== undefined) {
      if (value.length > component.validate?.maxLength) {
        const maxLength: string = component.validate?.maxLength.toString()
        formStore.validationErrors[componentSlug] = 'La valeur dépasse la longueur autorisée ' + maxLength
      }
    }
  }
}
