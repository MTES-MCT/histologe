function histoUpdateSubmitButton(elementName, elementLabel) {
  document.querySelector(elementName).innerHTML = elementLabel;
  document.querySelector(elementName).disabled = true;
}

function histoUpdateValueFromData(elementName, elementData, target) {
  document.querySelector(elementName).value = target.getAttribute(elementData);
}

document.querySelectorAll('.btn-disable-user').forEach((swbtn) => {
  swbtn.addEventListener('click', (evt) => {
    const target = evt.target;
    document.querySelectorAll('#fr-modal-user-disable_username').forEach((el) => {
      el.textContent = target.getAttribute('data-username');
    });
    histoUpdateValueFromData('#fr-modal-user-disable_userid', 'data-userid', target);
    document.querySelector('#user_disable_form').addEventListener('submit', () => {
      histoUpdateSubmitButton('#user_disable_form_submit', 'Désactivation en cours...');
    });
  });
});
