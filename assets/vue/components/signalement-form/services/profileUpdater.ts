import formStore from '../store'

export const profileUpdater = {
  update () {
    if (formStore.data.signalement_concerne_profil === 'logement_occupez') {
      delete formStore.data.signalement_concerne_profil_detail_tiers // eviter les effets de bord
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_occupant
    } else {
      delete formStore.data.signalement_concerne_profil_detail_occupant // eviter les effets de bord
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_tiers
    }
  }
}
