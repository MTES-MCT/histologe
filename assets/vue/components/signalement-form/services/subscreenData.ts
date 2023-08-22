import formStore from '../store'

export const subscreenServices = {
  generateSubscreenData (id: string, data: any[]) {
    return data.map((component) => {
      return {
        ...component,
        slug: id + '_' + (component.slug as string)
      }
    })
  },
  addSubscreenData (slug: string, data: any[]) {
    // on récupère l'index du composant dans son screen
    const componentIndex = formStore.screenData[formStore.currentScreenIndex].components.body.findIndex((component: any) => component.slug === slug)
    // pour ce composant on ajoute un objet "components" qu'on alimente avec les data reçues
    formStore.screenData[formStore.currentScreenIndex].components.body[componentIndex].components = {}
    formStore.screenData[formStore.currentScreenIndex].components.body[componentIndex].components.body = data
  }
}
