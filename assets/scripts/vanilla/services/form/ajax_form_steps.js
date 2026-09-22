import * as Sentry from '@sentry/browser';
import { clearErrors, displayFormErrors } from './ajax_form_errors.js';

function initializeFormSteps(formElement) {
  const containerElement = formElement.closest('[data-form-steps]');
  const stepElements = Array.from(formElement.querySelectorAll('[data-step]'));

  if (!containerElement || stepElements.length === 0) return;

  const nextButton = containerElement.querySelector('[data-step-next]');
  const previousButton = containerElement.querySelector('[data-step-previous]');
  const submitButton = containerElement.querySelector('[data-step-submit]');
  let currentStepIndex = 0;

  // Poste le formulaire avec uniquement les champs de l'étape actuelle pour les valider côté back-end
  // Puis afficher les erreurs proprement en se servant de la fonction displayFormErrors qui gère les formulaires Ajax habituels
  async function validateCurrentStep(callback) {
    const currentStepElement = stepElements[currentStepIndex];
    const fieldNames = new Set(
      Array.from(currentStepElement.querySelectorAll('[name]')).map((element) => element.name)
    );

    window.tinymce?.triggerSave();
    const formData = new FormData(formElement);
    fieldNames.forEach((name) => formData.append('_validate_step[]', name));

    nextButton.disabled = true;
    clearErrors();
    try {
      const response = await fetch(formElement.action, { method: 'POST', body: formData });
      if (response.ok) {
        callback();
      } else if (response.status === 400) {
        const responseData = await response.json();
        const container = formElement.closest('dialog, .single-ajax-form-container');
        displayFormErrors(container, formElement, responseData.errors);
      } else {
        alert("Une erreur s'est produite. Veuillez actualiser la page.");
      }
    } catch (error) {
      Sentry.captureException(error);
      alert("Une erreur s'est produite. Veuillez actualiser la page.");
    } finally {
      nextButton.disabled = false;
    }
  }

  function showStep(stepIndex) {
    currentStepIndex = Math.max(0, Math.min(stepIndex, stepElements.length - 1));

    stepElements.forEach((stepElement, index) => {
      stepElement.hidden = index !== currentStepIndex;
    });

    previousButton?.classList.toggle('fr-hidden', currentStepIndex === 0);
    nextButton?.classList.toggle('fr-hidden', currentStepIndex === stepElements.length - 1);
    submitButton?.classList.toggle('fr-hidden', currentStepIndex !== stepElements.length - 1);
  }

  function showStepContaining(element) {
    const stepElement = element?.closest('[data-step]');
    const stepIndex = stepElements.indexOf(stepElement);

    if (stepIndex !== -1) {
      showStep(stepIndex);
    }
  }

  nextButton?.addEventListener('click', () => validateCurrentStep(() => showStep(currentStepIndex + 1)));
  previousButton?.addEventListener('click', () => showStep(currentStepIndex - 1));
  formElement.addEventListener('invalid', (event) => showStepContaining(event.target), true);
  const errorObserver = new MutationObserver((mutations) => {
    const hasNewError = mutations.some((mutation) =>
      Array.from(mutation.addedNodes).some(
        (node) =>
          node instanceof Element &&
          (node.matches('.fr-error-text') || node.querySelector('.fr-error-text'))
      )
    );

    if (!hasNewError) return;

    const firstStepWithError = stepElements.find((stepElement) =>
      stepElement.querySelector('.fr-error-text')
    );
    showStepContaining(firstStepWithError);
  });
  errorObserver.observe(formElement, { childList: true, subtree: true });
  containerElement.addEventListener('close', () => showStep(0));

  showStep(0);
}

document.querySelectorAll('.form-with-steps').forEach(initializeFormSteps);
