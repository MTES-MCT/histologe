function histoCreateMiniatureImage(target) {
  histoDeleteMiniatureImage();
  const url = target.getAttribute('data-file-path');
  const modalFileEditType = document.querySelector('#fr-modal-edit-file-miniature');
  const newDiv =
    '<div id="fr-modal-edit-file-miniature-image" class="fr-col-6 fr-col-offset-3 fr-col-md-2 fr-col-offset-md-5 fr-px-5w fr-py-8w fr-rounded fr-border fr-text--center" style="background: url(\'' +
    url +
    '\') no-repeat center center/cover;"></div>';
  modalFileEditType.insertAdjacentHTML('afterbegin', newDiv);
}
function histoDeleteMiniatureImage() {
  const parentElement = document.querySelector('#fr-modal-edit-file-miniature');
  const addedDiv = document.querySelector('#fr-modal-edit-file-miniature-image');
  if (addedDiv) {
    parentElement.removeChild(addedDiv);
  }
}

btnSignalementFileEditAddEventListeners();

export function btnSignalementFileEditAddEventListeners() {
  document.querySelectorAll('.btn-signalement-file-edit').forEach((swbtn) => {
    swbtn.addEventListener('click', (evt) => {
      evt.preventDefault();
      const target = evt.target;

      if (target.getAttribute('data-type') === 'photo') {
        histoCreateMiniatureImage(target);
        document.querySelector('#fileDescription').value = target.getAttribute('data-description');
        document.querySelector('#fr-modal-edit-file-description').classList.remove('fr-hidden');
      } else {
        histoDeleteMiniatureImage();
        document.querySelector('#fr-modal-edit-file-description').classList.add('fr-hidden');
      }
      document.querySelector('.fr-modal-file-edit-filename').textContent =
        target.getAttribute('data-filename');
      document.querySelector('.fr-modal-file-edit-infos').textContent =
        'Ajouté le ' +
        target.getAttribute('data-createdAt') +
        ' par ' +
        target.getAttribute('data-partner-name') +
        target.getAttribute('data-user-name');
      document.querySelector('#file-edit-fileid').value = target.getAttribute('data-file-id');

      const selectedDocumentType = target.getAttribute('data-documentType');
      const selectedDesordreSlug = target.getAttribute('data-desordreSlug');
      const typeSelectBox = document.querySelector('#document-type-select');
      const fileFilter = target.getAttribute('data-file-filter');
      const selectTypeSituationToClone = document.querySelector('#select-type-situation-to-clone');
      const selectTypeProcedureToClone = document.querySelector('#select-type-procedure-to-clone');

      if (fileFilter === 'situation') {
        typeSelectBox.innerHTML = selectTypeSituationToClone.innerHTML;
        typeSelectBox.parentElement.classList.remove('fr-hidden');
      } else if (fileFilter === 'procédure') {
        typeSelectBox.innerHTML = selectTypeProcedureToClone.innerHTML;
        typeSelectBox.parentElement.classList.remove('fr-hidden');
      } else {
        typeSelectBox.parentElement.classList.add('fr-hidden');
      }

      if (
        selectedDesordreSlug &&
        typeSelectBox.querySelector('option[value="' + selectedDesordreSlug + '"]')
      ) {
        typeSelectBox.value = selectedDesordreSlug;
      } else if (
        selectedDocumentType &&
        typeSelectBox.querySelector('option[value="' + selectedDocumentType + '"]')
      ) {
        typeSelectBox.value = selectedDocumentType;
      }
    });
  });
}
