import * as Sentry from '@sentry/browser'

const inputElement = document.querySelector('[data-autocomplete-bailleur-url]');
let selectedSuggestionIndex = -1;
let isAutocompleteOpen = false;

document.addEventListener('click', function(event) {
    if (!event.target.closest('.fr-autocomplete-list') && isAutocompleteOpen) {
        document.querySelector('.fr-autocomplete-list').innerHTML = '';
        isAutocompleteOpen = false;
        selectedSuggestionIndex = -1;
    }
});

if (inputElement) {
    inputElement.addEventListener('keyup', function (event) {
        event.preventDefault();
        const isNavigationKey = ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key);
        const name = event.target.value.trim();
        if (!isNavigationKey && name.length > 1) {
            selectedSuggestionIndex = -1
            const url = `${inputElement.dataset.autocompleteBailleurUrl}&name=${name}`;
            fetchBailleur(url);
        }
    });

    inputElement.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            handleDown();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            handleUp();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            handleEnter();
        }
    });
}

async function fetchBailleur(url) {
    try {
        const response = await fetch(url);
        const resultList = await response.json();
        if (response.ok) {
            const ulElement = document.querySelector('.fr-autocomplete-list');
            ulElement.innerHTML = '';
            resultList.forEach((resultItem, index) => {
                let suggestion = document.createElement('li');
                suggestion.classList.add(
                    'fr-col-12',
                    'fr-p-3v',
                    'fr-text-label--blue-france',
                    'fr-autocomplete-suggestion'
                );
                suggestion.textContent = resultItem.name;
                suggestion.addEventListener('click', (event) => {
                    inputElement.value = event.target.textContent;
                    ulElement.innerHTML = '';
                });

                if (index === selectedSuggestionIndex) {
                    suggestion.classList.add('fr-autocomplete-suggestion-highlighted');
                }
                ulElement.append(suggestion);
            });
            isAutocompleteOpen = true;
        }
    } catch (error) {
        console.error(error);
        Sentry.captureException(new Error(error));
    }
}

function handleDown() {
    const suggestions = document.querySelectorAll('.fr-autocomplete-suggestion');
    if (selectedSuggestionIndex < suggestions.length - 1) {
        selectedSuggestionIndex++;
        updateSelectedSuggestion();
    }
}

function handleUp() {
    if (selectedSuggestionIndex > 0) {
        selectedSuggestionIndex--;
        updateSelectedSuggestion();
    }
}

function updateSelectedSuggestion() {
    const suggestions = document.querySelectorAll('.fr-autocomplete-suggestion');
    suggestions.forEach((suggestion, index) => {
        if (index === selectedSuggestionIndex) {
            suggestion.classList.add('fr-autocomplete-suggestion-highlighted');
        } else {
            suggestion.classList.remove('fr-autocomplete-suggestion-highlighted');
        }
    });
}

function handleEnter() {
    const suggestions = document.querySelectorAll('.fr-autocomplete-suggestion');
    if (selectedSuggestionIndex !== -1) {
        inputElement.value = suggestions[selectedSuggestionIndex].textContent;
        document.querySelector('.fr-autocomplete-list').innerHTML = '';
        selectedSuggestionIndex = -1;
        isAutocompleteOpen = false;
    }
}
