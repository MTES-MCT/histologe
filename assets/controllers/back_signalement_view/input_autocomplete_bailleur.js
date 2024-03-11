import * as Sentry from '@sentry/browser'

const inputElement = document.querySelector('[data-autocomplete-bailleur-url]');

if (inputElement) {
    inputElement.addEventListener('keyup', function (event) {
        event.preventDefault();
        if (event.target.value.length > 1) {
            const url = `${inputElement.dataset.autocompleteBailleurUrl}&name=${event.target.value}`;
            fetchBailleur(url);
        }
    });
}

async function fetchBailleur(url) {
    try {
        const response = await fetch(url);
        const resultList = await response.json();
        if (response.ok) {
            const divElement = document.querySelector('.search-bailleur-autocomplete-list');
            divElement.innerHTML = '';
            resultList.forEach((resultItem) => {
                let suggestion = document.createElement('div');
                suggestion.classList.add(
                    'fr-col-12',
                    'fr-p-3v',
                    'fr-text-label--blue-france',
                    'fr-bailleur-suggestion'
                );
                suggestion.textContent = resultItem.name;
                suggestion.addEventListener('click', (event) => {
                    inputElement.value = event.target.textContent;
                    divElement.innerHTML = '';
                })
                divElement.append(suggestion);
            });
        }
    } catch (error) {
        console.error(error);
        Sentry.captureException(new Error(error));
    }
}
