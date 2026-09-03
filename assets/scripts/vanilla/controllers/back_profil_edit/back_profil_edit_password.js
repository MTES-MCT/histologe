const modalEditPassword = document?.querySelector('#fr-modal-profil-edit-password');
/** @type {HTMLFormElement|null} */
const formEditPassword = document?.querySelector('#profil_edit_password_form');

function clearErrors() {
  const divErrorElements = document?.querySelectorAll(
    '#fr-modal-profil-edit-password .fr-input-group--error'
  );
  divErrorElements?.forEach((divErrorElement) => {
    divErrorElement
      .querySelectorAll('.fr-error-text:not(#password-match-error)')
      .forEach((pErrorElement) => {
        pErrorElement.remove();
      });
    divErrorElement.classList.remove('fr-input-group--error');
  });

  const pwdMatchError = document?.querySelector(
    '#fr-modal-profil-edit-password #password-match-error'
  );
  if (pwdMatchError) {
    pwdMatchError.classList.add('fr-hidden');
  }

  const messageElements = document?.querySelectorAll(
    '#fr-modal-profil-edit-password .message-password'
  );
  messageElements?.forEach((el) => {
    el.classList.remove('fr-message--valid', 'fr-message--error');
    el.classList.add('fr-message--info');
  });
}

function resetPasswordForm() {
  if (formEditPassword) {
    formEditPassword.reset();
  }
  clearErrors();
}

modalEditPassword?.addEventListener('dsfr.conceal', () => {
  resetPasswordForm();
});

modalEditPassword?.addEventListener('close', () => {
  resetPasswordForm();
});
