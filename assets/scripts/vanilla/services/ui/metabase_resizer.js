import 'iframe-resizer/js/iframeResizer';

window.addEventListener('load', () => {
  const iframe = document.querySelector('iframe[data-type="metabase"]');

  if (!(iframe instanceof HTMLIFrameElement)) {
    return;
  }
  iFrameResize(
    {
      log: false,
      checkOrigin: false,
      heightCalculationMethod: 'max',
      // Disable iframe-resizer warning about slow iframe responses to avoid noisy production logs.
      // Metabase embeds can legitimately take longer to respond; this is intentional, not to hide real issues.
      warningTimeout: 0,
    },
    iframe
  );
});
