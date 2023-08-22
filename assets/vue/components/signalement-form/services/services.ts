import formStore from '../store'

export const services = {
  updateProfil () {
    if (formStore.data.signalement_concerne_profil === 'logement_occupez') {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_occupant
    } else {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_tiers
    }
  },
  isScreenAfterCurrent (slug: string): boolean {
    const nextScreenIndex = formStore.screenData.findIndex((screen: any) => screen.slug === slug)
    if (nextScreenIndex <= formStore.currentScreenIndex) {
      return false
    }
    return true
  }
}
