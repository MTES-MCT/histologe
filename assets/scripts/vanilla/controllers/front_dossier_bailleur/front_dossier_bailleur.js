const reponseInjonctionBailleurDescription = document?.querySelector('#reponse_injonction_bailleur_description');
if (reponseInjonctionBailleurDescription) {
  const descriptionContainer = reponseInjonctionBailleurDescription.parentElement;
  const reponseInjonctionBailleurRadios = document?.querySelectorAll('input[name="reponse_injonction_bailleur[reponse]"]');
  const checkedRadio = document?.querySelector('input[name="reponse_injonction_bailleur[reponse]"]:checked');

  toggleBailleurDescription(checkedRadio?.value);

  reponseInjonctionBailleurRadios.forEach(radio => {
    radio.addEventListener('change', (event) => {
      toggleBailleurDescription(event.target.value);
    });
  });

  function toggleBailleurDescription(value) {
    if (value === '2' || value === '3') {
      descriptionContainer.classList.remove('fr-hidden');
    } else {
      descriptionContainer.classList.add('fr-hidden');
    }
  }

}