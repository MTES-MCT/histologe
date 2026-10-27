import * as Sentry from '@sentry/browser';

const inputElement = document.querySelector('[data-autocomplete-bailleur-url]');
// liste de suggestions la plus proche du champ (sœur directe ou dans un ancêtre selon les formulaires)
const listElement = findSuggestionsList(inputElement);
let selectedSuggestionIndex = -1;
let isAutocompleteOpen = false;

if (inputElement && listElement) {
  document.addEventListener('click', function (event) {
    if (
      isAutocompleteOpen &&
      !listElement.contains(event.target) &&
      !inputElement.contains(event.target)
    ) {
      closeSuggestions();
    }
  });

  inputElement.addEventListener('keyup', function (event) {
    event.preventDefault();
    const isNavigationKey = ['ArrowDown', 'ArrowUp', 'Enter', 'Escape', 'Tab', ' '].includes(
      event.key
    );
    const name = event.target.value.trim();
    if (!isNavigationKey && name.length > 1) {
      selectedSuggestionIndex = -1;
      const url = `${inputElement.dataset.autocompleteBailleurUrl}&name=${encodeURIComponent(name)}`;
      fetchBailleur(url);
    } else if (name.length <= 1) {
      closeSuggestions();
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
    } else if (event.key === 'Escape' || event.key === 'Tab') {
      closeSuggestions();
    }
  });
}

function findSuggestionsList(input) {
  let ancestor = input?.parentElement;
  while (ancestor && !ancestor.querySelector('.fr-autocomplete-list')) {
    ancestor = ancestor.parentElement;
  }
  return ancestor?.querySelector('.fr-autocomplete-list') ?? null;
}

async function fetchBailleur(url) {
  try {
    const response = await fetch(url);
    const resultList = await response.json();
    if (response.ok) {
      listElement.innerHTML = '';
      resultList.forEach((resultItem, index) => {
        const suggestion = document.createElement('li');
        suggestion.classList.add(
          'fr-col-12',
          'fr-p-3v',
          'fr-text-label--blue-france',
          'fr-autocomplete-suggestion'
        );
        suggestion.textContent = resultItem.name;
        suggestion.addEventListener('click', (event) => {
          inputElement.value = event.target.textContent;
          closeSuggestions();
        });

        if (index === selectedSuggestionIndex) {
          suggestion.classList.add('fr-autocomplete-suggestion-highlighted');
        }
        listElement.append(suggestion);
      });
      isAutocompleteOpen = true;
    }
  } catch (error) {
    console.error(error);
    Sentry.captureException(new Error(error));
  }
}

function handleDown() {
  const suggestions = listElement.querySelectorAll('.fr-autocomplete-suggestion');
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
  const suggestions = listElement.querySelectorAll('.fr-autocomplete-suggestion');
  suggestions.forEach((suggestion, index) => {
    if (index === selectedSuggestionIndex) {
      suggestion.classList.add('fr-autocomplete-suggestion-highlighted');
    } else {
      suggestion.classList.remove('fr-autocomplete-suggestion-highlighted');
    }
  });
}

function handleEnter() {
  const suggestions = listElement.querySelectorAll('.fr-autocomplete-suggestion');
  if (selectedSuggestionIndex !== -1) {
    inputElement.value = suggestions[selectedSuggestionIndex].textContent;
    closeSuggestions();
  }
}

function closeSuggestions() {
  listElement.innerHTML = '';
  selectedSuggestionIndex = -1;
  isAutocompleteOpen = false;
}
