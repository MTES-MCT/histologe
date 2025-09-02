import * as Sentry from '@sentry/browser';

const modalElements = document.querySelectorAll('[data-ajax-form] dialog');

modalElements.forEach((modalElement) => {
  modalElement.addEventListener('dsfr.conceal', (event) => {
    event.preventDefault();
    clearErrors();
  });
});

function clearErrors() {
  const divErrorElements = document.querySelectorAll('.fr-input-group--error');
  divErrorElements.forEach((divErrorElement) => {
    divErrorElement.querySelectorAll('.fr-error-text').forEach((pErrorElement) => {
      pErrorElement.remove();
    });
    divErrorElement.classList.remove('fr-input-group--error');
  });
}

function handleSubmitForm(containerElement) {
  containerElement.addEventListener('submit', (event) => {
    event.preventDefault();
    const formElement = event.target;
    const submitElement = document.querySelector('.fr-modal--opened [type="submit"], .single-ajax-form-container [type="submit"]');
    submitElement.disabled = true;
    submitElement.classList.add('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
    clearErrors();
    submitPayload(formElement);
  });
}

async function submitPayload(formElement) {
  let response;
  try {
    const formData = new FormData(formElement);

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
      alert('Votre session a expiré. Veuillez vous reconnecter en rechargeant la page.');
    } else if (response.redirected) {
      window.location.href = response.url;
    } else if (response.ok) {
      response.json().then((response) => {
        if (response.redirect) {
          window.location.href = response.url;
        }
      });
      location.reload();
      window.scrollTo(0, 0);
    } else if (response.status === 400) {
      const responseData = await response.json();
      const errors = responseData.errors;
      const submitElement = document.querySelector('.fr-modal--opened [type="submit"], .single-ajax-form-container [type="submit"]');
      let firstErrorElement = true;
      for (const property in errors) {
        const inputElements = document.querySelectorAll(`.fr-modal--opened [name="${property}"], .single-ajax-form-container [name="${formElement.name}[${property}]"]`);
        let inputElement;
        let parentElement;
        if (inputElements.length > 1) {
          inputElement = inputElements[0];
          parentElement = inputElement.closest('.fr-fieldset');
        } else {
          inputElement =
            document.querySelector(`.fr-modal--opened [name="${property}"], .single-ajax-form-container [name="${formElement.name}[${property}]"] `) ||
            document.querySelector('.fr-modal--opened .no-field-errors, .single-ajax-form-container .no-field-errors') ||
            document.querySelector('.fr-modal--opened input, .single-ajax-form-container input');
          parentElement = inputElement.parentElement;
        }
        inputElement.setAttribute('aria-describedby', `${property}-desc-error`);
        parentElement.classList.add('fr-input-group--error');

        const existingErrorElement = document.getElementById(`${property}-desc-error`);
        if (!existingErrorElement) {
          const pElement = document.createElement('p');
          pElement.classList.add('fr-error-text');
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
      submitElement.disabled = false;
      submitElement.classList.remove('fr-btn--loading', 'fr-icon-refresh-line');
      if (!submitElement.classList.contains('fr-icon-check-line')) {
        submitElement.classList.remove('fr-btn--icon-left');
      }
        
    } else {
      const responseData = await response.json();
      alert(responseData.message);
    }
  } catch (error) {
    alert("Une erreur s'est produite. Veuillez actualiser la page.");
    Sentry.captureException(new Error(error));
  }
}

const containerElements = document.querySelectorAll('[data-ajax-form] dialog, [data-ajax-form] .single-ajax-form-container');
containerElements.forEach((containerElement) => handleSubmitForm(containerElement));
