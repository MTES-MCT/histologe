export const matomo = {
  pushEvent (eventAction: string, eventName: string) {
    const _paq = Object(window)._paq = Object(window)._paq || [];
    const eventCategory = 'Signaler un problème de logement'
    _paq.push(['trackEvent', eventCategory, eventAction, eventName])
  }
}
