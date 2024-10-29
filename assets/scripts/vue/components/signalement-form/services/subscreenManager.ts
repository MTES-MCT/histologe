import formStore from '../store'

export const subscreenManager = {
  generateSubscreenData (id: string, data: any[]) {
    return data.map((component) => {
      return {
        ...component,
        slug: id + '_' + (component.slug as string),
        tagWhenEdit: id + '_' + (component.tagWhenEdit as string),
        validate: component.validate
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
