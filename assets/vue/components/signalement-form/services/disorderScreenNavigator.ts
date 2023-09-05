export function findPreviousScreen (
  formStore: any,
  index: number
): { currrentCategory: string, decrementIndex: number, previousScreenSlug: string } {
  const currentSlug: string = formStore.data.currentSlug
  let currrentCategory: string | null = null
  if (currentSlug.includes('batiment')) {
    currrentCategory = 'batiment'
  } else {
    currrentCategory = 'logement'
  }

  const disorderList = formStore.data.categorieDisorders[currrentCategory]
  const decrementIndex = index < 0 ? 0 : index - 1
  const previousScreenSlug = decrementIndex < 0 ? `desordres_${currrentCategory}` : disorderList[decrementIndex]

  return { currrentCategory, decrementIndex, previousScreenSlug }
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
