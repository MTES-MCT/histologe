window.addEventListener('refreshSearchCheckboxContainerEvent', () => {
  initSearchCheckboxWidgets();
});
document.addEventListener('DOMContentLoaded', initSearchCheckboxWidgets);
function initSearchCheckboxWidgets() {
  const all = document.querySelectorAll('.search-checkbox-container');
  Array.from(all).forEach((element, idx) => {
    try {
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
}
