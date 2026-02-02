export const matomo = {
  pushFormEvent (eventAction: string, eventName: string) {
    const _paq = Array.isArray(Object(window)._paq) ? Object(window)._paq : (Object(window)._paq = [])
    const eventCategory = 'Signaler un problème de logement'
    _paq.push(['trackEvent', eventCategory, eventAction, eventName])
    this.pushInjonctionEvent(eventAction, eventName)
  },
  pushInjonctionEvent (eventAction: string, eventName: string) {
    const _paq = Array.isArray(Object(window)._paq) ? Object(window)._paq : (Object(window)._paq = [])
    const eventCategory = 'Parcours démarche accélérée'
    _paq.push(['trackEvent', eventCategory, eventAction, eventName])
  }
}
