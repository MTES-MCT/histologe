const reponseInjonctionBailleurDescription = document?.querySelector('#reponse_injonction_bailleur_description');
const reponseInjonctionBailleurEngagementTravaux = document?.querySelector('#reponse_injonction_bailleur_engagement_travaux');

if (reponseInjonctionBailleurDescription) {
  const descriptionContainer = reponseInjonctionBailleurDescription.parentElement;
  const reponseInjonctionBailleurRadios = document?.querySelectorAll(
    'input[name="reponse_injonction_bailleur[reponse]"]'
  );
  const checkedRadio = document?.querySelector(
    'input[name="reponse_injonction_bailleur[reponse]"]:checked'
  );

  toggleBailleurDescription(checkedRadio?.value);

  reponseInjonctionBailleurRadios.forEach((radio) => {
    radio.addEventListener('change', (event) => {
      toggleBailleurDescription(event.target.value);
    });
  });

  function toggleBailleurDescription(value) {
    if (value) {
      descriptionContainer.classList.remove('fr-hidden');
      const label = descriptionContainer.querySelector('.fr-label');
      var textNode = '';
      switch (value) {
      case 'REPONSE_OUI':
        textNode = document.createTextNode(label.getAttribute('data-label-oui'));
        reponseInjonctionBailleurEngagementTravaux.classList.remove('fr-hidden');
        break;
      case 'REPONSE_OUI_AVEC_AIDE':
        textNode = document.createTextNode(label.getAttribute('data-label-oui-avec-aide'));
        reponseInjonctionBailleurEngagementTravaux.classList.remove('fr-hidden');
        break;
      case 'REPONSE_NON':
        textNode = document.createTextNode(label.getAttribute('data-label-non'));
        reponseInjonctionBailleurEngagementTravaux.classList.add('fr-hidden');
        break;
      }
      label.replaceChild(textNode, label.firstChild);

    } else {
      reponseInjonctionBailleurEngagementTravaux.classList.add('fr-hidden');
      descriptionContainer.classList.add('fr-hidden');
    }
  }
}

const stopProcedureBtn = document.querySelector('#stop-procedure-btn');
const stopProcedureForm = document.querySelector('#stop-procedure-form-container');

if (stopProcedureBtn && stopProcedureForm) {
  stopProcedureBtn.addEventListener('click', () => {
    stopProcedureForm.classList.toggle('fr-hidden');
    stopProcedureBtn.classList.toggle('fr-hidden');
  });
  const stopProcedureError = stopProcedureForm.querySelector(
    'form div#stop_procedure div.fr-input-group.fr-input-group--error'
  );
  if (stopProcedureError) {
    stopProcedureForm.classList.remove('fr-hidden');
    stopProcedureBtn.classList.add('fr-hidden');
  }
}
