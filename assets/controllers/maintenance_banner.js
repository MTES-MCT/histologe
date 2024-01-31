import { setCookie, getCookie } from "./cookie_utils";

const maintenanceBannerElement = document.getElementById('maintenance-banner');
if (null !== maintenanceBannerElement) {
    if (document.body.dataset.modeMaintenance) {
        setCookie('maintenanceBannerClosed', false, 1); // 1 day
    }

    const closeButtonElement = document.querySelector('#maintenance-banner .fr-btn--close');
    if (null !== closeButtonElement) {
        closeButtonElement.addEventListener('click', (event) => {
            const notice = event.target.parentNode.parentNode.parentNode;
            notice.parentNode.removeChild(notice);
            setCookie('maintenanceBannerClosed', true, 1);  // 1 day
        });
    }

    if ('true' === getCookie('maintenanceBannerClosed')) {
        maintenanceBannerElement.style.display = 'none';
    }
}

