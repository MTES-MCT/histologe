import formStore from '../store'

export const subscreenManager = {
  generateSubscreenData (id: string, data: any[], validateParent: any) {
    return data.map((component) => {
      return {
        ...component,
        slug: id + '_' + (component.slug as string),
        tagWhenEdit: id + '_' + (component.tagWhenEdit as string),
        // Si les deux objets validateParent et component.validate ont des clés en commun, les valeurs de component.validate écraseront celles de validateParent pour les mêmes clés lors de la fusion.
        validate: (validateParent != null && component.validate != null)
          ? { ...validateParent, ...component.validate }
          : (validateParent != null ? validateParent : component.validate)
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
