import * as Sentry from '@sentry/browser';

export default function paginationHandler(loadPanelContent) {
  document.addEventListener('click', async function (e) {
    const link = e.target.closest('.fr-pagination__link');
    if (!link || link.getAttribute('aria-disabled')) return;

    e.preventDefault();

    const url = new URL(link.href);
    const page = url.searchParams.get('page');

    if (!page) return;

    let container = link.closest('[data-url]');
    if (!container) {
      container = link.closest('.fr-tabs__panel')?.querySelector('[data-url]');
    }
    if (!container) {
      const error = "Pagination: aucun container avec data-url trouvé";
      console.error(error);
      Sentry.captureException(new Error(error));
      return;
    }

    const baseUrl = container.dataset.url.split('?')[0];
    const currentParams = new URLSearchParams(container.dataset.url.split('?')[1] || '');

    currentParams.set('page', page);

    container.dataset.url = baseUrl + '?' + currentParams.toString();

    await loadPanelContent(container);
  });
}
