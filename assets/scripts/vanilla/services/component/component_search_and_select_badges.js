export function initSearchAndSelectBadges() {
  document?.querySelectorAll('.search-and-select-badges-container')?.forEach((container) => {
    container.querySelectorAll('.search-and-select-badge-add')?.forEach((element) => {
      element.addEventListener('click', () => {
        element.classList?.add('fr-hidden', 'disabled');

        const badge = document.createElement('span');
        badge.classList.add(
          'fr-badge',
          'fr-badge--blue-ecume',
          'fr-m-1v',
          'search-and-select-badge-remove'
        );
        badge.setAttribute('data-badge-id', element.getAttribute('data-badge-id'));
        badge.innerText = element.getAttribute('data-badge-label') + ' ';
        badge.addEventListener('click', () => {
          removeBadge(container, badge);
        });

        const badgesSelected = container.querySelector('.search-and-select-badges-selected');
        badgesSelected.append(badge);

        const badgeIcon = document.createElement('span');
        badgeIcon.classList.add('fr-icon-close-line');
        badgeIcon.setAttribute('aria-hidden', true);
        badge.append(badgeIcon);

        const noSelection = badgesSelected.querySelector('.search-and-select-badges-no-selection');
        noSelection?.classList?.add('fr-hidden');

        refreshHiddenInput(container);
      });
    });
    container.querySelectorAll('.search-and-select-badge-remove')?.forEach((badge) => {
      badge.addEventListener('click', () => {
        removeBadge(container, badge);
      });
    });
    const searchInput = container.querySelector('.search-and-select-badges-search-input');
    searchInput?.addEventListener('input', (event) => {
      const inputValue = event.target.value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
      container.querySelectorAll('.search-and-select-badge-add')?.forEach((element) => {
        const normalizedLabel = element
          .getAttribute('data-badge-label')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase();
        if (normalizedLabel.indexOf(inputValue) > -1 && !element.classList.contains('disabled')) {
          element.classList?.remove('fr-hidden');
        } else {
          element.classList?.add('fr-hidden');
        }
      });
    });
  });
}

const removeBadge = (container, badge) => {
  const badgeId = badge.getAttribute('data-badge-id');
  const badgeAdd = container.querySelector('.search-and-select-badge-add-' + badgeId);
  badgeAdd.classList?.remove('fr-hidden', 'disabled');

  badge.remove();

  refreshHiddenInput(container);
};

const refreshHiddenInput = (container) => {
  const inputHidden = container.querySelector('.search-and-select-badges-input');
  inputHidden.setAttribute('value', '');
  let selectedList = container.querySelectorAll('.search-and-select-badge-remove');
  if (selectedList.length === 0) {
    const noSelection = container.querySelector('.search-and-select-badges-no-selection');
    noSelection?.classList?.remove('fr-hidden');
  }
  selectedList.forEach((element) => {
    if (inputHidden.getAttribute('value') !== '') {
      inputHidden.setAttribute(
        'value',
        inputHidden.getAttribute('value') + ',' + element.getAttribute('data-badge-id')
      );
    } else {
      inputHidden.setAttribute('value', element.getAttribute('data-badge-id'));
    }
  });
};

initSearchAndSelectBadges();
