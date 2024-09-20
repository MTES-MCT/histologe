export function findPreviousScreen (
  formStore: any,
  index: number
): { currentCategory: string, decrementIndex: number, previousScreenSlug: string } {
  const currentStep: string = formStore.data.currentStep
  const currentCategory: string = currentStep.includes('batiment') ? 'batiment' : 'logement'

  const disorderList = formStore.data.categorieDisorders[currentCategory]
  const decrementIndex = index < 0 ? 0 : index - 1
  let previousScreenSlug: string

  if (decrementIndex >= 0) {
    previousScreenSlug = disorderList[decrementIndex]
  } else {
    switch (currentStep) {
      case 'desordres_logement':
        previousScreenSlug = (formStore.data.zone_concernee_zone === 'batiment_logement') ? 'desordres_batiment' : 'ecran_intermediaire_les_desordres'
        break
      case 'desordres_batiment':
        previousScreenSlug = (formStore.data.zone_concernee_zone === 'batiment_logement') ? 'ecran_intermediaire_les_desordres' : 'desordres_logement'
        break
      case 'desordres_renseignes':
        previousScreenSlug = (formStore.data.zone_concernee_zone === 'batiment') ? 'desordres_batiment' : 'desordres_logement'
        break
      default:
        previousScreenSlug = (['logement', 'batiment_logement'].includes(formStore.data.zone_concernee_zone) && currentStep.includes('logement'))
          ? 'desordres_logement'
          : 'desordres_batiment'
        break
    }
  }

  return { currentCategory, decrementIndex, previousScreenSlug }
}

export function findNextScreen (
  formStore: any,
  index: number,
  slugButton: string = ''
): { currentCategory: string, incrementIndex: number, nextScreenSlug: string } {
  const currentStep: string = formStore.data.currentStep
  let nextScreenSlug: string = ''
  let incrementIndex: number = index
  let isDynamicScreen: boolean = false
  let currentCategory: string = 'batiment'

  switch (currentStep) {
    case 'ecran_intermediaire_les_desordres':
      if (['batiment', 'batiment_logement'].includes(formStore.data.zone_concernee_zone)) {
        nextScreenSlug = 'desordres_batiment'
      } else {
        nextScreenSlug = 'desordres_logement'
        currentCategory = 'logement'
      }
      break
    case 'desordres_batiment':
      if (slugButton === 'desordres_batiment_ras') {
        if (formStore.data.zone_concernee_zone === 'batiment_logement') {
          nextScreenSlug = 'desordres_renseignes_batiment'
        } else if (formStore.data.zone_concernee_zone === 'batiment') {
          nextScreenSlug = 'desordres_renseignes'
        }
      } else {
        nextScreenSlug = formStore.data.categorieDisorders.batiment[0]
      }
      break
    case 'desordres_logement':
      if (slugButton === 'desordres_logement_ras') {
        nextScreenSlug = 'desordres_renseignes'
      } else {
        nextScreenSlug = formStore.data.categorieDisorders.logement[0]
      }
      currentCategory = 'logement'
      break
    default:
      isDynamicScreen = true
      incrementIndex = index + 1
  }

  if (isDynamicScreen) {
    if (currentStep.includes('batiment')) {
      currentCategory = 'batiment'
    } else {
      currentCategory = 'logement'
    }
    const disorderList = formStore.data.categorieDisorders[currentCategory]

    if (incrementIndex >= disorderList.length) {
      switch (formStore.data.zone_concernee_zone) {
        case 'batiment':
        case 'logement':
          nextScreenSlug = 'desordres_renseignes'
          break
        case 'batiment_logement':
          nextScreenSlug = slugButton.includes('batiment') ? 'desordres_renseignes_batiment' : 'desordres_renseignes'
          break
      }
      incrementIndex = 0
    } else {
      nextScreenSlug = disorderList[incrementIndex]
    }
  }

  return { currentCategory, incrementIndex, nextScreenSlug }
}
