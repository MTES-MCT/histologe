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
      warningTimeout: 0,
    },
    iframe
  );
});
