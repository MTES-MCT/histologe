import { setCookie, getCookie } from "./cookie_utils";

const maintenanceBannerElement = document.getElementById('maintenance-banner');
if (null !== maintenanceBannerElement) {
    if ('1' === document.body.dataset.modeMaintenance) {
        setCookie('maintenanceBannerShow', true, 1); // 1 day
    }

    const closeButtonElement = document.querySelector('#maintenance-banner .fr-btn--close');
    if (null !== closeButtonElement) {
        closeButtonElement.addEventListener('click', (event) => {
            const notice = event.target.parentNode.parentNode.parentNode;
            notice.parentNode.removeChild(notice);
            setCookie('maintenanceBannerShow', false, 1);  // 1 day
        });
    }

    if ('false' === getCookie('maintenanceBannerShow')) {
        maintenanceBannerElement.classList.add('fr-hidden');
    } else {
        maintenanceBannerElement.classList.remove('fr-hidden');
    }
}

