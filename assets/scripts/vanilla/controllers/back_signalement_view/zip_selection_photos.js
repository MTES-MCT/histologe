document.addEventListener('DOMContentLoaded', () => {
  initZipSelectionPhotos();
});

export function initZipSelectionPhotos() {
  const selectionButton = document.getElementById('btn-enter-zip-selection');
  if (!selectionButton) return;

  const selectionBar = document.getElementById('zip-selection-bar');
  const countElement = document.getElementById('zip-selection-count');

  const form = document.getElementById('zip-selection-form');
  const hiddenFields = document.getElementById('zip-selection-hidden-fields');
  const submitButton = document.getElementById('zip-selection-submit');
  const cancelButton = document.getElementById('zip-selection-cancel');

  const photoItems = document.querySelectorAll('#tabpanel-documents-panel .container-situation .signalement-file-item');

  let selectionMode = false;

  const updateSubmitButtonState = () => {
    const selectedCount = hiddenFields.querySelectorAll('input[name="fileIds[]"]').length;
    const enabled = selectedCount > 0;

    submitButton.disabled = !enabled;
    submitButton.setAttribute('aria-disabled', (!enabled).toString());
  };

  const disableTooltips = () => {
    document.querySelectorAll('.part-infos-hover[aria-describedby]').forEach(
      /** @param {HTMLElement} element */
      (element) => {
        element.dataset.tooltipId = element.getAttribute('aria-describedby') ?? '';
        element.removeAttribute('aria-describedby');
      }
    );
  };

  const enableTooltips = () => {
    document.querySelectorAll('.part-infos-hover[data-tooltip-id]').forEach(
      /** @param {HTMLElement} element */
      (element) => {
        if (element?.dataset.tooltipId) {
          element.setAttribute('aria-describedby', element.dataset.tooltipId);
        }
        delete element?.dataset.tooltipId;
      }
    );
  };

  const updateCount = () => {
    countElement.textContent = hiddenFields.querySelectorAll('input[name="fileIds[]"]').length.toString();
  };

  const clearHiddenFields = () => {
    hiddenFields.innerHTML = '';
  };

  const setHiddenField = (fileId, enabled) => {
    const value = fileId.toString();
    const existingHiddenInput = [...hiddenFields.querySelectorAll('input[name="fileIds[]"]')].find(
      (inputElement) => inputElement.value === value
    );

    if (enabled) {
      if (!existingHiddenInput) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'fileIds[]';
        input.value = value;
        hiddenFields.appendChild(input);
      }
    } else if (existingHiddenInput) {
      existingHiddenInput.remove();
    }

    updateSubmitButtonState();
  };

  const resetSelection = () => {
    document.querySelectorAll('.zip-select-input').forEach((input) => {
      input.checked = false;
    });

    photoItems.forEach((item) => item.classList.remove('zip-photo-item--selected'));

    clearHiddenFields();
    updateCount();
    updateSubmitButtonState();
  };

  const setSelectionMode = (enabled) => {
    selectionMode = enabled;

    document.querySelectorAll('.zip-child-position-absolute').forEach((element) => {
      element.classList.toggle('zip-child-position-absolute--visible', enabled);
    });

    photoItems.forEach((item) => {
      item
        .querySelectorAll(
          '.open-photo-album, .btn-signalement-file-edit, .btn-signalement-file-delete'
        )
        .forEach((button) => {
          button.classList.toggle('fr-hidden', enabled);
        });
    });

    selectionBar.classList.toggle('zip-selection-bar--visible', enabled);

    if (enabled) {
      disableTooltips();
    } else {
      enableTooltips();
    }

    resetSelection();
  };

  const updateSelected = (item, isSelected) => {
    /** @var {HTMLInputElement|null} */
    const checkbox = item.querySelector('.zip-select-input');
    if (!checkbox) return;

    checkbox.checked = isSelected;
    item.classList.toggle('zip-photo-item--selected', isSelected);

    setHiddenField(checkbox.value, isSelected);
    updateCount();
  };

  const toggleSelected = (item) => {
    /** @var {HTMLInputElement|null} */
    const checkbox = item.querySelector('.zip-select-input');
    if (!checkbox) return;
    updateSelected(item, !checkbox.checked);
  };

  cancelButton?.addEventListener('click', () => {
    if (!selectionMode) return;
    setSelectionMode(false);
  });

  selectionButton.addEventListener('click', () => {
    setSelectionMode(!selectionMode);
  });

  form?.addEventListener('submit', (e) => {
    const selected = hiddenFields.querySelectorAll('input[name="fileIds[]"]').length;
    if (selected === 0) {
      e.preventDefault();
    }
  });

  photoItems.forEach((item) => {
    /** @var {HTMLInputElement|null} */
    const checkbox = item.querySelector('.zip-select-input');
    if (!checkbox) return;

    checkbox.addEventListener('change', () => {
      if (!selectionMode) return;

      item.classList.toggle('zip-photo-item--selected', checkbox.checked);
      setHiddenField(checkbox.value, checkbox.checked);
      updateCount();
    });

    const tile = item.querySelector('.zip-photo-tile') ?? item.querySelector('.part-infos-hover');

    if (!tile) return;

    tile.addEventListener('click', (e) => {
      if (!selectionMode) return;

      const target = e.target;
      if (target instanceof HTMLElement && target.closest('.zip-child-position-absolute')) {
        return;
      }

      toggleSelected(item);
    });
  });

  updateSubmitButtonState();
}
