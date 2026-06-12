window.addEventListener('refreshSearchCheckboxContainerEvent', () => {
  initSearchCheckboxWidgets();
});
document.addEventListener('DOMContentLoaded', initSearchCheckboxWidgets);
export function initSearchCheckboxWidgets() {
  const all = document.querySelectorAll('.search-checkbox-container');
  Array.from(all).forEach((element, idx) => {
    // Vérifier si l'élément a déjà été initialisé
    if (element.dataset.searchCheckboxInitialized === 'true') {
      searchCheckboxCompleteInputValue(element);
      return;
    }

    try {
      // Marquer comme initialisé
      element.dataset.searchCheckboxInitialized = 'true';

      searchCheckboxCompleteInputValue(element);
      const input = element.querySelector('input[type="text"]');
      const badgesContainer = element.querySelector('.search-checkbox-badges');
      const checkboxesContainer = element.querySelector('.search-checkbox');
      const closeBtn = element.querySelector('.fr-btn--close');
      // init values
      const initialValues = [];
      checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
        initialValues.push(checkbox.value);
      });
      // init order
      checkboxesContainer.querySelectorAll('.fr-fieldset__element').forEach((checkbox, index) => {
        checkbox.setAttribute('data-order', index);
      });
      // show choices on focus
      input.addEventListener('focus', function () {
        const elements = checkboxesContainer.querySelectorAll('.fr-fieldset__element');
        if (!elements.length) {
          checkboxesContainer.style.display = 'block';
          return;
        }
        elements.forEach((checkbox) => {
          checkbox.style.display = '';
        });
        checkboxesContainer.style.display = 'block';
        checkboxesContainer.scrollTop = 0;
        searchCheckboxOrderCheckboxes(element);
        input.value = '';
        if (badgesContainer) {
          badgesContainer.innerHTML = '';
        }
        closeBtn.classList.remove('fr-hidden');
        // Rendre les checkboxes accessibles au clavier
        enableCheckboxesKeyboardAccess(checkboxesContainer);
      });
      // filter choices on input keyup
      input.addEventListener('keyup', function () {
        const value = input.value
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase();

        let mainGroup = null;
        let mainGroupHasMatch = false;

        let subGroup = null;
        let subGroupHasMatch = false;

        checkboxesContainer.querySelectorAll('.fr-fieldset__element').forEach((el) => {
          const label = el.querySelector('label');
          const isMainGroup = el.classList.contains('optgroup__element');

          // Main title
          if (isMainGroup) {
            if (mainGroup) {
              mainGroup.style.display = mainGroupHasMatch ? '' : 'none';
            }
            mainGroup = el;
            mainGroupHasMatch = false;
            subGroup = null;
            return;
          }

          // Subtitle
          if (!label) {
            if (subGroup) {
              subGroup.style.display = subGroupHasMatch ? '' : 'none';
            }
            subGroup = el;
            subGroupHasMatch = false;
            return;
          }

          // option
          const text = label.textContent
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

          const match = text.includes(value);
          el.style.display = match ? '' : 'none';

          if (match) {
            mainGroupHasMatch = true;
            subGroupHasMatch = true;
          }
        });

        // close last groups
        if (subGroup) {
          subGroup.style.display = subGroupHasMatch ? '' : 'none';
        }
        if (mainGroup) {
          mainGroup.style.display = mainGroupHasMatch ? '' : 'none';
        }
      });
      // hide choices on click outside and on close button
      document.addEventListener('click', function (event) {
        if (!input.contains(event.target) && !checkboxesContainer.contains(event.target)) {
          searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
        }
      });
      element.addEventListener('click', function (event) {
        event.stopPropagation();
      });
      closeBtn.addEventListener('click', function () {
        searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
      });
      // Gérer la navigation au clavier dans l'input
      input.addEventListener('keydown', function (event) {
        if (
          event.key === 'Tab' &&
          !event.shiftKey &&
          checkboxesContainer.style.display === 'block'
        ) {
          // Tab depuis l'input : aller à la première checkbox visible
          event.preventDefault();
          const firstVisibleCheckbox = getFirstVisibleCheckbox(checkboxesContainer);
          if (firstVisibleCheckbox) {
            firstVisibleCheckbox.focus();
          }
        } else if (
          event.key === 'Tab' &&
          event.shiftKey &&
          checkboxesContainer.style.display === 'block'
        ) {
          // Shift+Tab depuis l'input quand dropdown ouvert : laisser le comportement natif pour sortir du composant
          searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
        } else if (event.key === 'Escape' && checkboxesContainer.style.display === 'block') {
          // Escape : fermer le dropdown
          event.preventDefault();
          searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
          input.focus();
        }
      });

      // Gérer la navigation au clavier sur le bouton Fermer
      closeBtn.addEventListener('keydown', function (event) {
        if (event.key === 'Tab' && !event.shiftKey) {
          event.preventDefault();
          const nextFocusable = getNextFocusableElement(closeBtn);
          if (nextFocusable) {
            nextFocusable.focus();
            setTimeout(() => {
              if (document.activeElement !== nextFocusable) {
                nextFocusable.focus();
              }
              searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
            }, 10);
          } else {
            searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
          }
        } else if (event.key === 'Tab' && event.shiftKey) {
          event.preventDefault();
          const visibleCheckboxes = getVisibleCheckboxes(checkboxesContainer);
          if (visibleCheckboxes.length > 0) {
            visibleCheckboxes[visibleCheckboxes.length - 1].focus();
          }
        } else if (event.key === 'Escape') {
          event.preventDefault();
          searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues);
          input.focus();
        }
      });
      // reorder on uncheck
      checkboxesContainer.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('change', function () {
          if (
            !checkbox.checked &&
            checkbox.closest('.fr-fieldset__element').classList.contains('topped')
          ) {
            searchCheckboxOrderCheckboxes(element);
          }
        });
      });
    } catch (e) {
      console.error("Erreur dans l'init du widget", idx, e);
    }
  });
}

function searchCheckboxCompleteInputValue(element) {
  const input = element.querySelector('input[type="text"]');
  const checkboxesContainer = element.querySelector('.search-checkbox');
  const checkedCheckboxes = checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked');
  const badgesContainer = element.querySelector('.search-checkbox-badges');

  if (badgesContainer) {
    badgesContainer.innerHTML = '';
  }
  input.value = '';

  if (checkedCheckboxes.length > 1) {
    input.value = checkedCheckboxes.length + ' éléments sélectionnés';
  } else {
    input.value = checkedCheckboxes.length + ' élément sélectionné';
  }

  checkedCheckboxes.forEach((checkbox) => {
    const label = checkbox.closest('.fr-fieldset__element').querySelector('label').textContent;
    const badge = document.createElement('span');
    badge.className = 'fr-badge fr-badge--blue-ecume fr-m-1v search-and-select-badge-remove';
    badge.setAttribute('aria-label', `Retirer ${label}`);
    badge.textContent = label;
    const closeIcon = document.createElement('span');
    closeIcon.className = 'fr-icon-close-line fr-ml-1v';
    closeIcon.setAttribute('aria-hidden', 'true');
    badge.appendChild(closeIcon);
    badge.addEventListener('click', () => {
      checkbox.checked = false;
      searchCheckboxCompleteInputValue(element);
    });

    if (badgesContainer) {
      badgesContainer.appendChild(badge);
    }
  });
}

function searchCheckboxOrderCheckboxes(element) {
  const checkboxesContainer = element.querySelector('.search-checkbox');
  const checkboxes = Array.from(checkboxesContainer.querySelectorAll('.fr-fieldset__element'));
  // order checkboxes by data-order attribute
  checkboxes.sort((a, b) => {
    const orderA = parseInt(a.getAttribute('data-order'), 10);
    const orderB = parseInt(b.getAttribute('data-order'), 10);
    return orderA - orderB;
  });
  checkboxesContainer.innerHTML = '';
  checkboxes.forEach((checkbox) => {
    checkbox.classList.remove('topped');
    checkboxesContainer.appendChild(checkbox);
  });
  // up checked checkboxes
  const checkedCheckboxes = Array.from(
    checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked')
  );
  checkedCheckboxes.reverse();
  checkedCheckboxes.forEach((checkbox) => {
    const parent = checkbox.closest('.fr-fieldset__element');
    parent.classList.add('topped');
    checkboxesContainer.prepend(parent);
  });
}

function searchCheckboxTriggerChange(element, initialValues) {
  const checkboxesContainer = element.querySelector('.search-checkbox');
  const currentValues = [];
  checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
    currentValues.push(checkbox.value);
  });
  if (JSON.stringify(currentValues) !== JSON.stringify(initialValues)) {
    element.dispatchEvent(new CustomEvent('searchCheckboxChange'));
  }
}

function searchCheckboxHideChoices(element, checkboxesContainer, closeBtn, initialValues) {
  checkboxesContainer.style.display = 'none';
  searchCheckboxCompleteInputValue(element);
  searchCheckboxTriggerChange(element, initialValues);
  closeBtn.classList.add('fr-hidden');
  // Retirer l'accessibilité au clavier des checkboxes
  disableCheckboxesKeyboardAccess(checkboxesContainer);
}

function enableCheckboxesKeyboardAccess(checkboxesContainer) {
  const checkboxes = checkboxesContainer.querySelectorAll('input[type="checkbox"]');
  checkboxes.forEach((checkbox) => {
    checkbox.setAttribute('tabindex', '0');

    // Gérer la navigation au clavier sur chaque checkbox
    checkbox.addEventListener('keydown', handleCheckboxKeydown);
  });

  // Rendre le bouton Fermer accessible au clavier
  const container = checkboxesContainer.closest('.search-checkbox-container');
  const closeBtn = container.querySelector('.fr-btn--close');
  if (closeBtn) {
    closeBtn.setAttribute('tabindex', '0');
  }
}

function disableCheckboxesKeyboardAccess(checkboxesContainer) {
  const checkboxes = checkboxesContainer.querySelectorAll('input[type="checkbox"]');
  checkboxes.forEach((checkbox) => {
    checkbox.setAttribute('tabindex', '-1');
    checkbox.removeEventListener('keydown', handleCheckboxKeydown);
  });

  // Retirer l'accessibilité du bouton Fermer
  const container = checkboxesContainer.closest('.search-checkbox-container');
  const closeBtn = container.querySelector('.fr-btn--close');
  if (closeBtn) {
    closeBtn.setAttribute('tabindex', '-1');
  }
}

function handleCheckboxKeydown(event) {
  const checkbox = event.target;
  const checkboxesContainer = checkbox.closest('.search-checkbox');
  const visibleCheckboxes = getVisibleCheckboxes(checkboxesContainer);
  const currentIndex = visibleCheckboxes.indexOf(checkbox);

  if (event.key === 'ArrowDown') {
    // Aller à la checkbox suivante
    event.preventDefault();
    if (currentIndex < visibleCheckboxes.length - 1) {
      visibleCheckboxes[currentIndex + 1].focus();
    }
  } else if (event.key === 'ArrowUp') {
    // Aller à la checkbox précédente
    event.preventDefault();
    if (currentIndex > 0) {
      visibleCheckboxes[currentIndex - 1].focus();
    } else {
      // Si on est sur la première checkbox, revenir à l'input
      const container = checkboxesContainer.closest('.search-checkbox-container');
      const input = container.querySelector('input[type="text"]');
      if (input) {
        input.focus();
      }
    }
  } else if (event.key === 'Escape') {
    // Fermer le dropdown et revenir à l'input
    event.preventDefault();
    const container = checkboxesContainer.closest('.search-checkbox-container');
    const input = container.querySelector('input[type="text"]');
    const closeBtn = container.querySelector('.fr-btn--close');
    const initialValues = [];
    checkboxesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach((cb) => {
      initialValues.push(cb.value);
    });
    searchCheckboxHideChoices(container, checkboxesContainer, closeBtn, initialValues);
    if (input) {
      input.focus();
    }
  } else if (event.key === 'Tab' && !event.shiftKey) {
    // Tab depuis la dernière checkbox : aller au bouton Fermer
    if (currentIndex === visibleCheckboxes.length - 1) {
      event.preventDefault();
      const container = checkboxesContainer.closest('.search-checkbox-container');
      const closeBtn = container.querySelector('.fr-btn--close');
      if (closeBtn) {
        closeBtn.focus();
      }
    }
  } else if (event.key === 'Tab' && event.shiftKey) {
    // Shift+Tab depuis la première checkbox : revenir à l'input
    if (currentIndex === 0) {
      event.preventDefault();
      const container = checkboxesContainer.closest('.search-checkbox-container');
      const input = container.querySelector('input[type="text"]');
      if (input) {
        input.focus();
      }
    }
  }
}

function getVisibleCheckboxes(checkboxesContainer) {
  const checkboxes = Array.from(checkboxesContainer.querySelectorAll('input[type="checkbox"]'));
  return checkboxes.filter((checkbox) => {
    const parent = checkbox.closest('.fr-fieldset__element');
    return parent && parent.style.display !== 'none';
  });
}

function getFirstVisibleCheckbox(checkboxesContainer) {
  const visibleCheckboxes = getVisibleCheckboxes(checkboxesContainer);
  return visibleCheckboxes.length > 0 ? visibleCheckboxes[0] : null;
}

function getNextFocusableElement(currentElement) {
  const modal = currentElement.closest('dialog.fr-modal');
  const searchContext = modal || document;
  const focusableSelectors =
    'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]';

  const allFocusable = Array.from(searchContext.querySelectorAll(focusableSelectors));

  const visibleFocusable = allFocusable.filter((elem) => {
    if (elem.getAttribute('tabindex') === '-1' || elem.classList.contains('fr-hidden')) {
      return false;
    }
    if (elem.type === 'checkbox' && elem.closest('.search-checkbox')) {
      return false;
    }
    const searchCheckboxContainer = elem.closest('.search-checkbox');
    if (searchCheckboxContainer && searchCheckboxContainer.style.display === 'none') {
      return false;
    }
    return true;
  });

  const currentIndex = visibleFocusable.indexOf(currentElement);

  if (currentIndex === -1) {
    const container = currentElement.closest('.search-checkbox-container');
    if (container) {
      for (let i = 0; i < visibleFocusable.length; i++) {
        if (
          !container.contains(visibleFocusable[i]) &&
          isAfterInDOM(container, visibleFocusable[i])
        ) {
          return visibleFocusable[i];
        }
      }
    }
  } else if (currentIndex + 1 < visibleFocusable.length) {
    return visibleFocusable[currentIndex + 1];
  }

  return null;
}

function isAfterInDOM(elementA, elementB) {
  const position = elementA.compareDocumentPosition(elementB);
  return (position & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
}
