import formStore from './store'

export const services = {
  generateSubscreenData (id: string, data: any[]) {
    return data.map((component) => {
      return {
        ...component,
        slug: id + '_' + (component.slug as string)
      }
    })
  },
  addSubscreenData (id: string, data: any[]) {
    const componentIndex = formStore.screenData[formStore.currentScreenIndex].components.body.findIndex((component: any) => component.slug === id)
    console.log(componentIndex)
    formStore.screenData[formStore.currentScreenIndex].components.body[componentIndex].components = {}
    formStore.screenData[formStore.currentScreenIndex].components.body[componentIndex].components.body = data
    console.log(formStore.screenData[formStore.currentScreenIndex].components.body[componentIndex])
  },
  updateProfil () {
    if (formStore.data.signalement_concerne_profil === 'logement_occupez') {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_occupant
    } else {
      formStore.data.profil = formStore.data.signalement_concerne_profil_detail_tiers
    }
  }
}
