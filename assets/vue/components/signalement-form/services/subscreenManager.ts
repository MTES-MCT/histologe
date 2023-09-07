import formStore from '../store'

export const subscreenManager = {
  generateSubscreenData (id: string, data: any[], validateParent: any) {
    return data.map((component) => {
      return {
        ...component,
        slug: id + '_' + (component.slug as string),
        validate: validateParent
      }
    })
  },
  addSubscreenData (slug: string, data: any[]) {
    // on récupère l'index du composant dans son screen
    const component = formStore.currentScreen?.components.body?.find((component: any) => component.slug === slug)
    if (component !== undefined) {
      // pour ce composant on ajoute un objet "components" qu'on alimente avec les data reçues
      component.components = {
        body: data
      }
    }
  }
}
