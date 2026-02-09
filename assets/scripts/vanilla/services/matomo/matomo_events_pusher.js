/**
 * Permet d'envoyer des événements personnalisés à Matomo.
 *
 */

document.querySelectorAll('[data-matomo-clickable-event-category]')?.forEach((element) => {
  element.addEventListener('click touchdown', () => {
    var _paq = (window._paq = window._paq || []);
    eventCategory = element.getAttribute('data-matomo-clickable-event-category');
    eventAction = element.getAttribute('data-matomo-clickable-event-action');
    eventName = element.getAttribute('data-matomo-clickable-event-name');
    _paq.push(['trackEvent', eventCategory, eventAction, eventName]);
  });
});

var hasTrackedScrollEvents = [];
document.querySelectorAll('[data-matomo-scrollable-event-category]')?.forEach((element) => {
  hasTrackedScrollEvents[element] = false;
  let elementToScroll = element;
  if (element.getAttribute('data-matomo-scrollable-event-element') === 'window') {
    elementToScroll = window;
  }
  elementToScroll.addEventListener('scroll', () => {
    if (hasTrackedScrollEvents[element]) {
      return;
    }

    // eventValue contains the scroll percentage (10, 25, 50, 75, 90, 100)
    eventValue = element.getAttribute('data-matomo-scrollable-event-value');

    // When scrolled more than eventValue percentage, send event to Matomo
    const scrollTop =
      element.getAttribute('data-matomo-scrollable-event-element') === 'window'
        ? document.documentElement.scrollTop || document.body.scrollTop
        : element.scrollTop;
    const scrollHeight =
      element.getAttribute('data-matomo-scrollable-event-element') === 'window'
        ? (document.documentElement.scrollHeight || document.body.scrollHeight) -
          document.documentElement.clientHeight
        : element.scrollHeight - element.clientHeight;
    const scrolledPercentage = (scrollTop / scrollHeight) * 100;
    if (scrolledPercentage < eventValue) {
      return;
    }

    hasTrackedScrollEvents[element] = true;
    var _paq = (window._paq = window._paq || []);
    eventCategory = element.getAttribute('data-matomo-scrollable-event-category');
    eventAction = element.getAttribute('data-matomo-scrollable-event-action');
    eventName = element.getAttribute('data-matomo-scrollable-event-name');
    _paq.push(['trackEvent', eventCategory, eventAction, eventName]);
  });
});

document.querySelectorAll('[data-matomo-timer-event-category]')?.forEach((element) => {
  let timer;
  element.addEventListener('mousemove', () => {
    // Start timer on mousemove
    if (!timer) {
      timer = setTimeout(
        () => {
          var _paq = (window._paq = window._paq || []);
          eventCategory = element.getAttribute('data-matomo-timer-event-category');
          eventAction = element.getAttribute('data-matomo-timer-event-action');
          eventName = element.getAttribute('data-matomo-timer-event-name');
          _paq.push(['trackEvent', eventCategory, eventAction, eventName]);
          timer = null; // Reset timer
        },
        parseInt(element.getAttribute('data-matomo-timer-event-name')) * 1000
      ); // Convert seconds to milliseconds
    }
  });
});
