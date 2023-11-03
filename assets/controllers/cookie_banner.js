import { setCookie, getCookie, deleteCookie } from "./cookie_utils";
import { disableMatomoTracking, enableMatomoTracking } from "./matomo_tracking";

const consentAllAcceptRadioButton = document.getElementById('consent-all-accept');
const consentAllRefuseRadioButton = document.getElementById('consent-all-refuse');

const acceptRadioButtonList = document.querySelectorAll('.accept input[type="radio"]');
const refuseRadioButtonList = document.querySelectorAll('.refuse input[type="radio"]');

const consentButtons = document.querySelector('.fr-consent-banner__buttons');

const consentConfirmationChoice = document.querySelector('.fr-consent-manager__buttons .fr-btn');

consentAllAcceptRadioButton.addEventListener('click', () => {
   acceptRadioButtonList.forEach(radioButtonItem => {
      radioButtonItem.checked = true;
   });

   refuseRadioButtonList.forEach(radioButtonItem => {
      radioButtonItem.checked = false;
   })
});

consentAllRefuseRadioButton.addEventListener('click', () => {
   refuseRadioButtonList.forEach(radioButtonItem => {
      if (!radioButtonItem.disabled) {
         radioButtonItem.checked = true;
      }
   });

   acceptRadioButtonList.forEach(radioButtonItem => {
      radioButtonItem.checked = false;
   })
});

consentButtons.addEventListener('click', (event) => {
   const clickedButton = event.target;
   if (clickedButton.classList.contains('btn-all-accept')) {
      setCookie('cookieConsent', true, 395);
      enableMatomoTracking();
   } else if (clickedButton.classList.contains('btn-all-refuse')) {
      setCookie('cookieConsent', false, 395);
      disableMatomoTracking();
   }
   displayOrHideConsentBannerCookie();
});

consentConfirmationChoice.addEventListener('click', () => {
   const consentAcceptCustomMatomo = document.getElementById("consent-finality-1-accept");
   const consentRefuseCustomMatomo = document.getElementById("consent-finality-1-refuse");
   console.log(consentRefuseCustomMatomo.checked);
   if (consentAcceptCustomMatomo.checked) {
      setCookie('cookieConsent', true, 395);
      enableMatomoTracking();
   } else if (consentRefuseCustomMatomo.checked) {
      setCookie('cookieConsent', false, 395);
      disableMatomoTracking();
   }
});

function displayOrHideConsentBannerCookie() {
   const cookieConsent = getCookie('cookieConsent');
   const consentBanner = document.querySelector('.fr-consent-banner');
   if (cookieConsent) {
      consentBanner.classList.add('fr-hidden');
   } else {
      consentBanner.classList.remove('fr-hidden');
   }
}

window.addEventListener('load', displayOrHideConsentBannerCookie);
