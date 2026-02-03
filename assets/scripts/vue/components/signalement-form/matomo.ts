export const matomo = {
  pushFormEvent (eventAction: string, eventName: string) {
    // @ts-ignore
    const _paq = window._paq = window._paq || []
    const eventCategory = 'Signaler un problème de logement'
    _paq.push(['trackEvent', eventCategory, eventAction, eventName])
  }
}
