import { registerClickRoute } from '../ui/click_dispatcher';

registerClickRoute('[data-select-all-in-target]', (link, e) => {
  e.preventDefault();

  const targetSelector = link.dataset.selectAllInTarget;
  const target = document.querySelector(targetSelector);
  if (!targetSelector) return;

  /** @type {NodeListOf<HTMLInputElement>} */
  const checkboxes = Array.from(target.querySelectorAll('input[type="checkbox"]')).filter(
    (cb) => !cb.disabled
  );
  const allChecked = Array.from(checkboxes).every((cb) => cb.checked);

  checkboxes.forEach((cb) => {
    cb.checked = !allChecked;
  });

  link.textContent = allChecked ? 'Tout sélectionner' : 'Tout désélectionner';
});
