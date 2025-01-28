import formStore from '../store'
import { isValidPhoneNumber } from 'libphonenumber-js'
import { variableTester } from '../../../utils/variableTester'

export const componentValidator = {
  validate (component: any) {
    const componentSlug: string = component.slug
    const value = formStore.data[componentSlug]

    let regexPattern
    // s'il y a une valeur, on vérifie si un pattern est requis (ou si c'est un type email)
    if (variableTester.isNotEmpty(value) && component.type === 'SignalementFormEmailfield') {
      regexPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    } else if (variableTester.isNotEmpty(value) && component.validate?.pattern !== undefined) {
      regexPattern = new RegExp(component.validate.pattern)
    }
    if (regexPattern !== undefined) {
      if (!regexPattern.test(value)) {
        formStore.validationErrors[componentSlug] = component.validate?.patternMessage ?? 'Format invalide'
      }
    }

    if (variableTester.isNotEmpty(value) && component.type === 'SignalementFormPhonefield') {
      const countryCode = formStore.data[componentSlug + '_countrycode'].split(':')[0]
      if (!isValidPhoneNumber(value, countryCode)) {
        formStore.validationErrors[componentSlug] = 'Veuillez renseigner un numéro de téléphone valide.'
      }

      const valueTelSecond = formStore.data[componentSlug + '_secondaire']
      if (variableTester.isNotEmpty(valueTelSecond)) {
        const countryCodeSecond = formStore.data[componentSlug + '_secondaire_countrycode'].split(':')[0]
        if (!isValidPhoneNumber(valueTelSecond, countryCodeSecond)) {
          formStore.validationErrors[componentSlug + '_secondaire'] = 'Veuillez renseigner un numéro de téléphone valide.'
        }
      }
    }

    if (component.type === 'SignalementFormAddress') {
      this.validateAddress(component)
    }

    if (variableTester.isNotEmpty(value) && component.validate?.maxLength !== undefined) {
      if (value.length > component.validate?.maxLength) {
        const maxLength: string = component.validate?.maxLength.toString()
        formStore.validationErrors[componentSlug] = 'La valeur dépasse la longueur autorisée ' + maxLength
      }
    }
  },

  validateAddress (component: any) {
    const componentSlug: string = component.slug
    const validationError = 'Veuillez renseigner et sélectionner l\'adresse du logement.'
    // si le composant est requis et que tous les champs sont vides, on affiche l'erreur sur le champ de recherche
    if (
      (component.validate === undefined || component.validate.required !== false) &&
      (variableTester.isEmpty(formStore.data[componentSlug])) &&
      (variableTester.isEmpty(formStore.data[componentSlug + '_detail_numero'])) &&
      (variableTester.isEmpty(formStore.data[componentSlug + '_detail_code_postal'])) &&
      (variableTester.isEmpty(formStore.data[componentSlug + '_detail_commune']))
    ) {
      formStore.validationErrors[componentSlug] = validationError

    // il y a eu une édition manuelle : on vérifie tous les sous-champs
    } else if (formStore.data[componentSlug + '_detail_manual'] !== 0 && formStore.data[componentSlug + '_detail_manual'] !== undefined) {
      const addressDetailNumero = formStore.data[componentSlug + '_detail_numero']
      if (variableTester.isEmpty(addressDetailNumero)) {
        formStore.validationErrors[componentSlug + '_detail_numero'] = 'Veuillez renseigner l\'adresse du logement.'
      } else {
        const regexPattern = /^[0-9]*$/
        if (regexPattern.test(addressDetailNumero) || addressDetailNumero.length < 6 || addressDetailNumero.length > 100) {
          formStore.validationErrors[componentSlug + '_detail_numero'] = 'Veuillez renseigner une adresse valide.'
        }
      }

      if (variableTester.isEmpty(formStore.data[componentSlug + '_detail_code_postal'])) {
        formStore.validationErrors[componentSlug + '_detail_code_postal'] = 'Veuillez renseigner le code postal du logement.'

      // vérification du code postal
      } else if (!/^\d{5}$/.test(formStore.data[componentSlug + '_detail_code_postal'])) {
        formStore.validationErrors[componentSlug + '_detail_code_postal'] = 'Le code postal doit être composé de 5 chiffres'
      }

      const addressDetailCommune = formStore.data[componentSlug + '_detail_commune']
      if (variableTester.isEmpty(addressDetailCommune)) {
        formStore.validationErrors[componentSlug + '_detail_commune'] = 'Veuillez renseigner la commune du logement.'
      } else {
        // en bdd on peut mettre jusqu'à 100 caractères pour le nom de la commune, mais l'API BAN crash au-dessus de 200 caractères
        // et le nom de commune le plus long de France est Saint-Remy-en-Bouzemont-Saint-Genest-et-Isson (45 caractères)
        if (addressDetailCommune.length > 95) {
          formStore.validationErrors[componentSlug + '_detail_commune'] = 'Veuillez renseigner une commune valide.'
        }
      }
    }
  }
}
