import * as Sentry from '@sentry/browser'

const modalElements = document.querySelectorAll('[data-ajax-form] dialog');

modalElements.forEach((modalElement) => {
    modalElement.addEventListener('dsfr.conceal', (event) => {
        event.preventDefault();
        clearErrors();
    })
})

function clearErrors() {
    const divErrorElements = document.querySelectorAll('.fr-input-group--error');
    divErrorElements.forEach((divErrorElement) => {
        divErrorElement.classList.remove('fr-input-group--error');
        let pErrorElement = divErrorElement.querySelector('.fr-error-text');
        if (pErrorElement) {
            pErrorElement.remove();
        }
    })
}

function handleEditSignalementModalForm(element) {
    element.addEventListener('submit', (event) => {
        event.preventDefault();
        const formElement = document.getElementById(event.target.id);
        const submitElement = document.querySelector(`.fr-modal--opened [type="submit"]`);
        submitElement.disabled = true;
        submitElement.classList.add('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
        clearErrors();
        submitPayload(formElement);
    });
}

async function submitPayload(formElement) {
    try {
        const formData = new FormData(formElement);
        const payload = {};
        formData.forEach((value, key) => {
            payload[key] = value;
        });

        const response = await fetch(formElement.action, {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const responseData = await response.json();
        if (response.ok) {
            location.reload();
        } else if (response.status === 400) {
            const errors = responseData.errors;
            const submitElement = document.querySelector(`.fr-modal--opened [type="submit"]`);
            let firstErrorElement = true;
            for (const property in errors) {
                const inputElement = document.querySelector(`.fr-modal--opened [name="${property}"]`);
                inputElement.setAttribute('aria-describedby', `${property}-desc-error`);
                inputElement.parentElement.classList.add('fr-input-group--error');

                const existingErrorElement = document.getElementById(`${property}-desc-error`);
                if (!existingErrorElement) {
                    let pElement = document.createElement('p');
                    pElement.classList.add('fr-error-text');
                    pElement.id = `${property}-desc-error`;

                    let messageError = '';
                    errors[property].errors.forEach((error) => {
                        messageError = messageError + error;
                    })
                    pElement.innerHTML = messageError;
                    inputElement.insertAdjacentElement('afterend', pElement);
                }
                if (firstErrorElement) {
                    inputElement.focus();
                    firstErrorElement = false;
                }
            }
            submitElement.disabled = false;
            submitElement.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
        } else {
            alert(responseData.message);
        }
    } catch (error) {
        alert('Une erreur s\'est produite. Veuillez actualiser la page.');
        Sentry.captureException(new Error(error));
    }
}

modalElements.forEach(modalElement => handleEditSignalementModalForm(modalElement));
