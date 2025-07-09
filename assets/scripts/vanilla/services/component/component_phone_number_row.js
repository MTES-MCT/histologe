window.addEventListener('refreshPhoneNumberEvent', () => {
  document.querySelectorAll('.phone-number-row-container').forEach((containerPhoneNumber) => {
    initContainerPhoneNumber(containerPhoneNumber);
  });
});

function initContainerPhoneNumber(containerPhoneNumber) {
  const selectCode = containerPhoneNumber.querySelector('.fr-select');
  const inputNumber = containerPhoneNumber.querySelector('.fr-input');
  selectCode.addEventListener('change', () => {
    refreshInputHidden(containerPhoneNumber);
  });
  inputNumber.addEventListener('input', () => {
    refreshInputHidden(containerPhoneNumber);
  });
}

function refreshInputHidden(containerPhoneNumber) {
  const selectCode = containerPhoneNumber.querySelector('.fr-select');
  const inputNumber = containerPhoneNumber.querySelector('.fr-input');
  const inputHidden = containerPhoneNumber.querySelector('[type=hidden]');
  inputHidden.value = '';
  if (selectCode.value !== '' && inputNumber.value !== '') {
    inputHidden.value = selectCode.value + inputNumber.value;
  }
}

window.dispatchEvent(new Event('refreshPhoneNumberEvent'));
