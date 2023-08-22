import formStore from '../store'

export const navManager = {
  isScreenAfterCurrent (slug: string): boolean {
    const nextScreenIndex = formStore.screenData.findIndex((screen: any) => screen.slug === slug)
    if (nextScreenIndex <= formStore.currentScreenIndex) {
      return false
    }
    return true
  }
}
