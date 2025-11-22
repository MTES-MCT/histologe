import iframeResize from "@iframe-resizer/parent";

window.addEventListener('load', () => {
    const iframe = document.querySelector('iframe[data-type="metabase"]');

    if (!(iframe instanceof HTMLIFrameElement)) {
        return;
    }
    iframeResize(
        {
            license: 'GPLv3',
            log: false,
            checkOrigin: false,
            heightCalculationMethod: 'lowestElement'
        },
        iframe
    );
});
