import * as Sentry from '@sentry/browser';
import {
  jsonResponseHandler,
  addFlashMessage,
} from '../../services/component/component_json_response_handler.js';
import { clearErrors, displayFormErrors } from './ajax_form_errors.js';

const modalElements = document.querySelectorAll('[data-ajax-form] dialog');

modalElements.forEach((modalElement) => {
  modalElement.addEventListener('dsfr.conceal', (event) => {
    event.preventDefault();
    clearErrors();
  });
  modalElement.addEventListener('close', () => {
    clearErrors();
  });
});

function handleSubmitForm(containerElement) {
  containerElement.addEventListener('submit', (event) => {
    event.preventDefault();
    const formElement = event.target;
    const submitElements = containerElement.querySelectorAll('[type="submit"]');
    submitElements.forEach((el) => {
      el.disabled = true;
      el.classList.add('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
    });
    clearErrors();
    submitPayload(formElement, event.submitter);
  });
}

export async function submitPayload(formElement, submitter = null) {
  let response;
  try {
    const formData = new FormData(formElement, submitter);
    const container = formElement.closest('dialog, .single-ajax-form-container');
    const submitElements = container?.querySelectorAll('[type="submit"]') || [];

    if (submitter?.hasAttribute('data-step-next')) {
      const currentStepElement = formElement.querySelector('[data-step]:not([hidden])');
      formData.append('_validate_step', currentStepElement?.dataset.step ?? '');
    }

    if (
      formElement.enctype === 'multipart/form-data' ||
      formElement.dataset.submitType === 'formData'
    ) {
      response = await fetch(formElement.action, {
        method: 'POST',
        body: formData,
      });
    } else {
      const payload = {};
      formData.forEach((value, key) => {
        payload[key] = value;
      });
      response = await fetch(formElement.action, {
        method: 'POST',
        body: JSON.stringify(payload),
        headers: {
          'Content-Type': 'application/json',
        },
      });
    }
    if (response.redirected && response.url.endsWith('/connexion')) {
      addFlashMessage({
        type: 'alert',
        title: 'Erreur',
        message: 'Votre session a expiré. Veuillez vous reconnecter en rechargeant la page.',
      });
    } else if (response.redirected) {
      window.location.href = response.url;
    } else if (response.ok) {
      if (submitter?.hasAttribute('data-step-next')) {
        resetSubmitButton(submitElements);
        formElement.dispatchEvent(new CustomEvent('form-steps:validated'));
      } else {
        jsonResponseHandler(response);
        setTimeout(() => {
          resetSubmitButton(submitElements);
        }, 500);
      }
    } else if (response.status === 400) {
      const responseData = await response.json();
      displayFormErrors(container, formElement, responseData.errors);
      resetSubmitButton(submitElements);
    } else if (response.status === 403) {
      addFlashMessage({
        type: 'alert',
        title: 'Erreur',
        message: "Vous n'avez pas les permissions nécessaires pour effectuer cette action.",
      });
    } else if (response.status === 404) {
      addFlashMessage({
        type: 'alert',
        title: 'Erreur',
        message: 'Page introuvable.',
      });
    } else {
      const responseData = await response.json();
      alert(responseData.message);
    }
  } catch (error) {
    alert("Une erreur s'est produite. Veuillez actualiser la page.");
    Sentry.captureException(new Error(error));
  }
}

export function attachAjaxFormHandlers() {
  const containerElements = document.querySelectorAll(
    '[data-ajax-form] dialog, [data-ajax-form] .single-ajax-form-container'
  );
  containerElements.forEach((containerElement) => {
    if (containerElement.dataset.ajaxBound === '1') {
      return;
    }

    containerElement.dataset.ajaxBound = '1';
    handleSubmitForm(containerElement);
  });
}
attachAjaxFormHandlers();

function resetSubmitButton(submitElements) {
  submitElements.forEach((el) => {
    el.disabled = false;
    el.classList.remove('fr-btn--loading', 'fr-icon-refresh-line');
    if (!el.classList.contains('fr-icon-check-line')) {
      el.classList.remove('fr-btn--icon-left');
    }
  });
}

//gère la suppression des affectations et des suivis
document.addEventListener('click', (event) => {
  const actionBtn = event.target.closest('[data-delete]');

  if (!actionBtn) return;

  event.preventDefault();

  let messageConfirmation = 'Voulez-vous vraiment supprimer cet élément ?';
  if (actionBtn.dataset.isSynchronized === '1') {
    messageConfirmation =
      'Attention, ce dossier est synchronisé avec un service externe.\nVoulez-vous vraiment supprimer cet élément ?';
  }
  if (confirm(messageConfirmation)) {
    const formData = new FormData();
    formData.append('_token', actionBtn.getAttribute('data-token'));
    fetch(actionBtn.getAttribute('data-delete'), {
      method: 'POST',
      body: formData,
    }).then((r) => {
      if (r.ok) {
        jsonResponseHandler(r);
      }
    });
  }
});

document.addEventListener('click', (event) => {
  const link = event.target.closest('.simple-ajax-link');

  if (!link) return;

  event.preventDefault();
  fetch(link.href, { method: 'GET' }).then((response) => {
    if (response.ok) {
      jsonResponseHandler(response);
    }
  });
});

document.addEventListener('submit', (event) => {
  const formElement = event.target.closest('.simple-ajax-form');

  if (!formElement) return;

  event.preventDefault();

  const submitElement = formElement.querySelector('[type="submit"]');
  submitElement.disabled = true;

  const formData = new FormData(formElement);

  fetch(formElement.action, {
    method: 'POST',
    body: formData,
  }).then((response) => {
    if (response.redirected && response.url.endsWith('/connexion')) {
      addFlashMessage({
        type: 'alert',
        title: 'Erreur',
        message: 'Votre session a expiré. Veuillez vous reconnecter en rechargeant la page.',
      });
    } else if (response.ok) {
      jsonResponseHandler(response);
    }
    submitElement.disabled = false;
  });
});
