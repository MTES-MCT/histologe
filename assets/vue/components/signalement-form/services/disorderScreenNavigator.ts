export function findPreviousScreen (
  formStore: any,
  index: number
): { currentCategory: string, decrementIndex: number, previousScreenSlug: string } {
  const currentSlug: string = formStore.data.currentSlug
  const currentCategory: string = currentSlug.includes('batiment') ? 'batiment' : 'logement'

  const disorderList = formStore.data.categorieDisorders[currentCategory]
  const decrementIndex = index < 0 ? 0 : index - 1
  let previousScreenSlug: string = 'ecran_intermediaire_les_desordres'

  if (decrementIndex >= 0) {
    previousScreenSlug = disorderList[decrementIndex]
  } else {
    switch (currentSlug) {
      case 'desordres_logement':
        previousScreenSlug = (formStore.data.zone_concernee_zone === 'batiment_logement') ? 'desordres_batiment' : 'ecran_intermediaire_les_desordres'
        break
      case 'desordres_batiment':
        previousScreenSlug = (formStore.data.zone_concernee_zone === 'batiment_logement') ? 'ecran_intermediaire_les_desordres' : 'desordres_logement'
        break
      default:
        previousScreenSlug = (['logement', 'batiment_logement'].includes(formStore.data.zone_concernee_zone) && currentSlug.includes('logement'))
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
  const currentSlug: string = formStore.data.currentSlug
  let nextScreenSlug: string = ''
  let incrementIndex: number = index
  let isDynamicScreen: boolean = false
  let currentCategory: string = 'batiment'

  switch (currentSlug) {
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
          nextScreenSlug = 'desordres_logement'
        } else if (formStore.data.zone_concernee_zone === 'batiment') {
          nextScreenSlug = 'ecran_intermediaire_procedure'
        }
      } else {
        nextScreenSlug = formStore.data.categorieDisorders.batiment[0]
      }
      break
    case 'desordres_logement':
      if (slugButton === 'desordres_logement_ras') {
        nextScreenSlug = 'ecran_intermediaire_procedure'
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
    if (currentSlug.includes('batiment')) {
      currentCategory = 'batiment'
    } else {
      currentCategory = 'logement'
    }
    const disorderList = formStore.data.categorieDisorders[currentCategory]

    if (incrementIndex >= disorderList.length) {
      switch (formStore.data.zone_concernee_zone) {
        case 'batiment':
        case 'logement':
          nextScreenSlug = 'ecran_intermediaire_procedure'
          break
        case 'batiment_logement':
          nextScreenSlug = slugButton.includes('batiment') ? 'desordres_logement' : 'ecran_intermediaire_procedure'
          break
      }
      incrementIndex = 0
    } else {
      nextScreenSlug = disorderList[incrementIndex]
    }
  }

  return { currentCategory, incrementIndex, nextScreenSlug }
}
