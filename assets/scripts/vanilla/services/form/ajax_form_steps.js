function getStepElements(formElement) {
  return Array.from(formElement.querySelectorAll('[data-step]'));
}

function getCurrentStepIndex(formElement) {
  return getStepElements(formElement).findIndex((stepElement) => !stepElement.hidden);
}

function showStep(formElement, stepIndex) {
  const containerElement = formElement.closest('[data-form-steps]');
  const stepElements = getStepElements(formElement);
  const index = Math.max(0, Math.min(stepIndex, stepElements.length - 1));

  stepElements.forEach((stepElement, i) => {
    stepElement.hidden = i !== index;
  });

  containerElement
    ?.querySelector('[data-step-previous]')
    ?.classList.toggle('fr-hidden', index === 0);
  containerElement
    ?.querySelector('[data-step-next]')
    ?.classList.toggle('fr-hidden', index === stepElements.length - 1);
  containerElement
    ?.querySelector('[data-step-submit]')
    ?.classList.toggle('fr-hidden', index !== stepElements.length - 1);
}

function showStepContaining(formElement, element) {
  const stepIndex = getStepElements(formElement).indexOf(element?.closest('[data-step]'));
  if (stepIndex !== -1) {
    showStep(formElement, stepIndex);
  }
}

function initializeFormStepsContainer(containerElement) {
  const getForm = () => containerElement.querySelector('.form-with-steps');

  containerElement.querySelector('[data-step-previous]')?.addEventListener('click', () => {
    const formElement = getForm();
    if (formElement) showStep(formElement, getCurrentStepIndex(formElement) - 1);
  });
  containerElement.addEventListener('close', () => {
    const formElement = getForm();
    if (formElement) showStep(formElement, 0);
  });
}

export function initializeFormSteps(formElement) {
  if (getStepElements(formElement).length === 0) return;

  formElement.addEventListener('form-steps:validated', () =>
    showStep(formElement, getCurrentStepIndex(formElement) + 1)
  );
  formElement.addEventListener(
    'invalid',
    (event) => showStepContaining(formElement, event.target),
    true
  );
  new MutationObserver((mutations) => {
    const hasNewError = mutations.some((mutation) =>
      Array.from(mutation.addedNodes).some(
        (node) =>
          node instanceof Element &&
          (node.matches('.fr-error-text') || node.querySelector('.fr-error-text'))
      )
    );
    if (!hasNewError) return;

    const firstStepWithError = getStepElements(formElement).find((stepElement) =>
      stepElement.querySelector('.fr-error-text')
    );
    showStepContaining(formElement, firstStepWithError);
  }).observe(formElement, { childList: true, subtree: true });

  showStep(formElement, 0);
}

document.querySelectorAll('[data-form-steps]').forEach(initializeFormStepsContainer);
document.querySelectorAll('.form-with-steps').forEach(initializeFormSteps);
