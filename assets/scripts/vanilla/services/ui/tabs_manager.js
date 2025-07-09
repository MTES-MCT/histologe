const currentHash = window.location.hash !== '' ? window.location.hash.substring(1) : '';
const btnElements = document.querySelectorAll('.fr-tabs__tab');
const tabElements = document.querySelectorAll('.fr-tabs__panel');
const buttonElement = document.getElementById('tabpanel-' + currentHash);
const tabElement = document.getElementById('tabpanel-' + currentHash + '-panel');
if (buttonElement && tabElement) {
  if (!tabElement.disabled) {
    btnElements.forEach((btnElement) => {
      btnElement.setAttribute('aria-selected', 'false');
    });
    tabElements.forEach((tabElement) => {
      tabElement.classList.remove('fr-tabs__panel--selected');
    });
    buttonElement.setAttribute('aria-selected', 'true');
    tabElement.classList.add('fr-tabs__panel--selected');
  }
}
btnElements.forEach((btnElement) => {
  btnElement.addEventListener('click', () => {
    window.location.hash = btnElement.id.substring(9);
  });
});
