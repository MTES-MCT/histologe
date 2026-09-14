import { registerClickRoute } from './click_dispatcher';

registerClickRoute(
  '.fr-notice .fr-btn--close, .fr-notice .fr-icon-close-circle-fill',
  (closeButton, event) => {
    if (closeButton.dataset.closeUrl) {
      fetch(closeButton.dataset.closeUrl, { method: 'POST' }).catch(() => {});
    }

    const notice = closeButton.closest('.fr-notice');
    if (notice) {
      notice.remove();
    }
  }
);
