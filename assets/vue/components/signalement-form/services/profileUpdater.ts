import formStore from '../store'

export const profileUpdater = {
  update () {
    if (formStore.data.signalement_concerne_profil === 'logement_occupez') {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_occupant
    } else {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_tiers
    }
  }
}
