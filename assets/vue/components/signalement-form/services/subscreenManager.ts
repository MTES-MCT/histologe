// import formStore from '../store'

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
    // const componentIndex = formStore.currentScreen?.components.body?.findIndex((component: any) => component.slug === slug)
    // pour ce composant on ajoute un objet "components" qu'on alimente avec les data reçues
    // formStore.currentScreen?.components.body?[componentIndex].components = {}
    // formStore.currentScreen?.components.body?[componentIndex].components.body = data
  }
}
