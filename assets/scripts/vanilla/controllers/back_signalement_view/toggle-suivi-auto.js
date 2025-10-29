const allSuivis = document?.querySelectorAll('.suivi-item');
const items = Array.from(allSuivis).map(item => ({
  el: item,
  isAuto: item.classList.contains('suivi-auto')
}));
const btnDisplayAll = document?.querySelector('#btn-display-all-suivis');
const toggleWrapper = document?.querySelector('#toggle-hide-technical')?.closest('.fr-toggle');
const toggle = document?.querySelector('#toggle-hide-technical');
let expanded = false;
if (toggleWrapper) {
  const hasAuto = items.some(({ isAuto }) => isAuto);
  if (!hasAuto) {
    toggleWrapper.classList.add('fr-hidden');
  }
}
function applyFilter() {
  const hideAuto = toggle?.checked === true;

  if (expanded) {
    items.forEach(({ el, isAuto }) => {
      if (hideAuto) {
        if (isAuto) el.classList.add('fr-hidden'); else el.classList.remove('fr-hidden');
      } else {
        el.classList.remove('fr-hidden');
      }
    });
  } else {
    if (hideAuto) {
      let shown = 0;
      items.forEach(({ el, isAuto }) => {
        if (!isAuto && shown < 3) {
          el.classList.remove('fr-hidden');
          shown++;
        } else {
          el.classList.add('fr-hidden');
        }
      });
    } else {
      let shown = 0;
      items.forEach(({ el }) => {
        if (shown < 3) {
          el.classList.remove('fr-hidden');
          shown++;
        } else {
          el.classList.add('fr-hidden');
        }
      });
    }
  }

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

btnDisplayAll?.addEventListeners('click touchdown', (e) => {
  e.preventDefault();
  expanded = true;
  applyFilter();
  btnDisplayAll.classList.add('fr-hidden');
});