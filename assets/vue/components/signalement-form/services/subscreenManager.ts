import formStore from '../store'

export const subscreenManager = {
  generateSubscreenData (id: string, data: any[], validateParent: any) {
    return data.map((component) => {
      let updatedSearchWhenEdit
      if (component.searchWhenEdit !== undefined) {
        const updatedValues = []
        for (const index of component.searchWhenEdit.values) {
          updatedValues.push(id + '_' + (index as string))
        }
        updatedSearchWhenEdit = {
          values: updatedValues,
          result: id + '_' + (component.searchWhenEdit.result as string)
        }
      }

      return {
        ...component,
        slug: id + '_' + (component.slug as string),
        tagWhenEdit: id + '_' + (component.tagWhenEdit as string),
        searchWhenEdit: updatedSearchWhenEdit,
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
