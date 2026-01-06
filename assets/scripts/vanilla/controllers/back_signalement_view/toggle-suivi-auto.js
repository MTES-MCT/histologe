const toggleWrapper = document?.querySelector('#toggle-hide-technical')?.closest('.fr-toggle');
const toggle = document?.querySelector('#toggle-hide-technical');
let expanded = false;
export function applyFilter() {
  const allSuivis = document?.querySelectorAll('.suivi-item');
  const items = Array.from(allSuivis).map((item) => ({
    el: item,
    isAuto: item.classList.contains('suivi-auto'),
  }));
  const btnDisplayAll = document?.querySelector('#btn-display-all-suivis');
  const hideAuto = toggle?.checked === true;
  let shown = 0;

  if (toggleWrapper) {
    const hasAuto = items.some(({ isAuto }) => isAuto);
    if (!hasAuto) {
      toggleWrapper.classList.add('fr-hidden');
    }
  }

  items.forEach(({ el, isAuto }) => {
    let shouldShow = true;

    if (expanded) {
      if (hideAuto && isAuto) shouldShow = false;
    } else {
      if (hideAuto && isAuto) {
        shouldShow = false;
      } else if (shown >= 3) {
        shouldShow = false;
      }
    }

    if (shouldShow) {
      el.classList.remove('fr-hidden');
      if (!expanded) shown++;
    } else {
      el.classList.add('fr-hidden');
    }
  });

  if (!btnDisplayAll) return;
  const anyHidden = items.some(({ el }) => el.classList.contains('fr-hidden'));
  if (anyHidden && !expanded) {
    btnDisplayAll.classList.remove('fr-hidden');
  } else {
    btnDisplayAll.classList.add('fr-hidden');
  }
}

applyFilter();

toggle?.addEventListener('change', () => {
  applyFilter();
});

document.addEventListener('click', (e) => {
  const target = e.target;
  const btn = target.closest('#btn-display-all-suivis');
  if (!btn) return;

  e.preventDefault();
  expanded = true;
  applyFilter();
  btn.classList.add('fr-hidden');
});
