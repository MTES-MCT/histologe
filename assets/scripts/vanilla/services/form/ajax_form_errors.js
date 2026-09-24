export function clearErrors() {
  // On supprime les erreurs des formulaires sauf celles qui sont dans les panels de visites, car elles sont gérées par le panel lui-même
  // Un jour il faudra faire les panels de visites comme les autres panels :)
  const divErrorElements = document.querySelectorAll(
    '[data-ajax-form] dialog:not([data-panel-visite]) .fr-input-group--error, [data-ajax-form] dialog:not([data-panel-visite]) .fr-fieldset--error'
  );
  divErrorElements.forEach((divErrorElement) => {
    divErrorElement.classList.remove(
      'fr-label--error',
      'fr-input-group--error',
      'fr-fieldset--error'
    );
  });

  // On supprime les messages d'erreur .fr-error-text qui ne sont pas dans des panels de visites, car elles sont gérées par le panel lui-même
  const pErrorElements = document.querySelectorAll(
    '[data-ajax-form] dialog:not([data-panel-visite]) .fr-error-text'
  );
  pErrorElements.forEach((pErrorElement) => {
    if (!pErrorElement.classList.contains('static-error')) {
      pErrorElement.remove();
    }
  });
}

export function displayFormErrors(container, formElement, errors) {
  let firstErrorElement = true;
  for (const property in errors) {
    const labelTargetErrors = container.querySelectorAll('[data-error-target="1"]');

    if (labelTargetErrors.length > 0) {
      const labelElement = labelTargetErrors[0];
      labelElement.classList.add('fr-label--error', 'fr-input-group--error');

      const pElement = document.createElement('p');
      pElement.classList.add('fr-error-text', 'fr-my-3v');
      pElement.id = `${property}-desc-error`;

      let messageError = '';
      errors[property].errors.forEach((error) => {
        messageError = messageError + error;
      });
      pElement.innerHTML = messageError;

      labelElement.after(pElement);
    } else {
      const inputElements = container.querySelectorAll(
        `[name="${property}"], [name="${formElement.name}[${property}]"]`
      );
      let inputElement;
      let parentElement;
      if (inputElements.length > 1) {
        inputElement = inputElements[0];
        parentElement = inputElement.closest('.fr-fieldset') || inputElement.parentElement;
      } else {
        inputElement =
          container.querySelector(
            `[name="${property}"], [name="${formElement.name}[${property}]"] `
          ) ||
          container.querySelector('.no-field-errors') ||
          container.querySelector('input');
        parentElement = inputElement.parentElement;
      }

      inputElement.setAttribute('aria-describedby', `${property}-desc-error`);
      const isFieldset = parentElement.tagName === 'FIELDSET';
      parentElement.classList.add(isFieldset ? 'fr-fieldset--error' : 'fr-input-group--error');

      const existingErrorElement = document.getElementById(`${property}-desc-error`);
      if (!existingErrorElement) {
        const pElement = document.createElement('p');
        pElement.classList.add('fr-error-text');
        if (isFieldset) {
          pElement.classList.add('fr-fieldset__element');
        }
        pElement.id = `${property}-desc-error`;

        let messageError = '';
        errors[property].errors.forEach((error) => {
          messageError = messageError + error;
        });
        pElement.innerHTML = messageError;

        parentElement.appendChild(pElement);
      }
      if (firstErrorElement) {
        inputElement.focus();
        firstErrorElement = false;
      }
    }
  }
}
