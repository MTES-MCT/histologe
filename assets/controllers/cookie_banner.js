import { setCookie, getCookie, EXPIRATION_DAYS } from "./cookie_utils";
import { disableMatomoTracking, enableMatomoTracking } from "./cookie_matomo_tracking";

const consentAllAcceptRadioButton = document.getElementById('consent-all-accept');
const consentAllRefuseRadioButton = document.getElementById('consent-all-refuse');
const acceptRadioButtonList = document.querySelectorAll('.accept input[type="radio"]');
const refuseRadioButtonList = document.querySelectorAll('.refuse input[type="radio"]');
const consentButtons = document.querySelector('.fr-consent-banner__buttons');
const consentConfirmationChoice = document.querySelector('.fr-consent-manager__buttons .fr-btn');
const modalConsent = document.getElementById('fr-consent-modal');

function initChoiceUserBasedOnConsent() {
   if ("true" === getCookie('cookieConsent')) {
      consentAllAcceptRadioButton.checked = true;
      handleAcceptButtonClick();
   } else {
      handleRefuseButtonClick();
      consentAllRefuseRadioButton.checked = true;
   }
}

function handleAcceptButtonClick() {
   acceptRadioButtonList.forEach(radioButtonItem => {
      radioButtonItem.checked = true;
   });

   refuseRadioButtonList.forEach(radioButtonItem => {
      if (!radioButtonItem.disabled) {
         radioButtonItem.checked = false;
      }
   });
}

function handleRefuseButtonClick() {
   refuseRadioButtonList.forEach(radioButtonItem => {
      if (!radioButtonItem.disabled) {
         radioButtonItem.checked = true;
      }
   });

   acceptRadioButtonList.forEach(radioButtonItem => {
      if (!radioButtonItem.disabled) {
         radioButtonItem.checked = false;
      }
   });
}

function handleConsentButtonClick(clickedButton) {
   if (clickedButton.classList.contains('btn-all-accept')) {
      setCookie('cookieConsent', true, EXPIRATION_DAYS);
      enableMatomoTracking();
   } else if (clickedButton.classList.contains('btn-all-refuse')) {
      setCookie('cookieConsent', false, EXPIRATION_DAYS);
      disableMatomoTracking();
   }
   displayOrHideConsentBannerCookie();
}

function handleFinalConsentChoice() {
   const consentAcceptCustomMatomo = document.getElementById("consent-finality-1-accept");
   const consentRefuseCustomMatomo = document.getElementById("consent-finality-1-refuse");

   if (consentAcceptCustomMatomo.checked) {
      setCookie('cookieConsent', true, EXPIRATION_DAYS);
      enableMatomoTracking();
   } else if (consentRefuseCustomMatomo.checked) {
      setCookie('cookieConsent', false, EXPIRATION_DAYS);
      disableMatomoTracking();
   }
   dsfr(modalConsent).modal.conceal();
   displayOrHideConsentBannerCookie();
}

function displayOrHideConsentBannerCookie() {
   const cookieConsent = getCookie('cookieConsent');
   const consentBanner = document.querySelector('.fr-consent-banner');
   if (cookieConsent) {
      consentBanner?.classList.add('fr-hidden');
   } else {
      consentBanner?.classList.remove('fr-hidden');
   }
}

consentAllAcceptRadioButton?.addEventListener('click', handleAcceptButtonClick);
consentAllRefuseRadioButton?.addEventListener('click', handleRefuseButtonClick);
consentButtons?.addEventListener('click', (event) => handleConsentButtonClick(event.target));
consentConfirmationChoice?.addEventListener('click', handleFinalConsentChoice);
modalConsent?.addEventListener('dsfr.disclose', initChoiceUserBasedOnConsent);
window.addEventListener('load', displayOrHideConsentBannerCookie);
