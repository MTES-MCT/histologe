import formStore from '../store'
import { isValidPhoneNumber } from 'libphonenumber-js'
import { variableTester } from './variableTester'

export const componentValidator = {
  validate (component: any) {
    const componentSlug: string = component.slug
    const value = formStore.data[componentSlug]

    let regexPattern
    // s'il y a une valeur, on vérifie si un pattern est requis (ou si c'est un type email)
    if (variableTester.isNotEmtpy(value) && component.type === 'SignalementFormEmailfield') {
      regexPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    } else if (variableTester.isNotEmtpy(value) && component.validate?.pattern !== undefined) {
      regexPattern = new RegExp(component.validate.pattern)
    }
    if (regexPattern !== undefined) {
      if (!regexPattern.test(value)) {
        formStore.validationErrors[componentSlug] = 'Format invalide'
      }
    }

    if (variableTester.isNotEmtpy(value) && component.type === 'SignalementFormPhonefield') {
      const countryCode = formStore.data[componentSlug + '_countrycode'].split(':')[0]
      if (!isValidPhoneNumber(value, countryCode)) {
        formStore.validationErrors[componentSlug] = 'Format invalide'
      }

      const valueTelSecond = formStore.data[componentSlug + '_secondaire']
      if (variableTester.isNotEmtpy(valueTelSecond)) {
        const countryCodeSecond = formStore.data[componentSlug + '_secondaire_countrycode'].split(':')[0]
        if (!isValidPhoneNumber(valueTelSecond, countryCodeSecond)) {
          formStore.validationErrors[componentSlug + '_secondaire'] = 'Format invalide'
        }
      }
    }

    if (component.type === 'SignalementFormAddress') {
      this.validateAddress(component)
    }

    if (variableTester.isNotEmtpy(value) && component.validate?.maxLength !== undefined) {
      if (value.length > component.validate?.maxLength) {
        const maxLength: string = component.validate?.maxLength.toString()
        formStore.validationErrors[componentSlug] = 'La valeur dépasse la longueur autorisée ' + maxLength
      }
    }
  },

  validateAddress (component: any) {
    const componentSlug: string = component.slug
    const validationError = 'Ce champ est requis'
    // si le composant est requis et que tous les champs sont vides, on affiche l'erreur sur le champ de recherche
    if (
      (component.validate === undefined || component.validate.required !== false) &&
      (formStore.data[componentSlug] === undefined || formStore.data[componentSlug] === '') &&
      (formStore.data[componentSlug + '_detail_numero'] === undefined || formStore.data[componentSlug + '_detail_numero'] === '') &&
      (formStore.data[componentSlug + '_detail_code_postal'] === undefined || formStore.data[componentSlug + '_detail_code_postal'] === '') &&
      (formStore.data[componentSlug + '_detail_commune'] === undefined || formStore.data[componentSlug + '_detail_commune'] === '')
    ) {
      formStore.validationErrors[componentSlug] = validationError

    // il y a eu une édition manuelle : on vérifie tous les sous-champs
    } else if (formStore.data[componentSlug + '_detail_manual'] !== 0 && formStore.data[componentSlug + '_detail_manual'] !== undefined) {
      const addressDetailNumero = formStore.data[componentSlug + '_detail_numero']
      if (addressDetailNumero === undefined || addressDetailNumero === '') {
        formStore.validationErrors[componentSlug + '_detail_numero'] = validationError
      } else {
        const regexPattern = /^[0-9]*$/
        if (regexPattern.test(addressDetailNumero) || addressDetailNumero.length < 6 || addressDetailNumero.length > 100) {
          formStore.validationErrors[componentSlug + '_detail_numero'] = 'Format invalide'
        }
      }

      if (formStore.data[componentSlug + '_detail_code_postal'] === undefined ||
        formStore.data[componentSlug + '_detail_code_postal'] === ''
      ) {
        formStore.validationErrors[componentSlug + '_detail_code_postal'] = validationError

      // vérification du code postal
      } else if (!/^\d{5}$/.test(formStore.data[componentSlug + '_detail_code_postal'])) {
        formStore.validationErrors[componentSlug + '_detail_code_postal'] = 'Le code postal doit être composé de 5 chiffres'
      }

      if (formStore.data[componentSlug + '_detail_commune'] === undefined ||
        formStore.data[componentSlug + '_detail_commune'] === ''
      ) {
        formStore.validationErrors[componentSlug + '_detail_commune'] = validationError
      }
    }
  }
}
