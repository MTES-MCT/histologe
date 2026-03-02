const fieldsetAllocataire = document?.querySelector('#usager_situation_foyer_allocataire');
if (fieldsetAllocataire) {
  document
    .querySelectorAll('#usager_situation_foyer_allocataire input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshCaisseAllocation();
      });
    });

  function refreshCaisseAllocation() {
    const containerCaisseAllocation = document.querySelector('#usager_situation_foyer_caisseAllocation').closest('.fr-fieldset__element');
    const containerNumAllocataire = document.querySelector('#usager_situation_foyer_numAllocataire').closest('.fr-fieldset__element');
    const containerTypeAllocation = document.querySelector('#usager_situation_foyer_typeAllocation').closest('.fr-fieldset__element');
    const containerMontantAllocation = document.querySelector('#usager_situation_foyer_montantAllocation').closest('.fr-fieldset__element');
    if (document.querySelector('#usager_situation_foyer_allocataire_0').checked) {
      containerCaisseAllocation.classList.remove('fr-hidden');
      containerNumAllocataire.classList.remove('fr-hidden');
      containerTypeAllocation.classList.remove('fr-hidden');
      containerMontantAllocation.classList.remove('fr-hidden');
    } else {
      containerCaisseAllocation.classList.add('fr-hidden');
      containerNumAllocataire.classList.add('fr-hidden');
      containerTypeAllocation.classList.add('fr-hidden');
      containerMontantAllocation.classList.add('fr-hidden');
    }
  }
  refreshCaisseAllocation();
}

const fieldsetAccompagnementTravailleurSocial = document?.querySelector('#usager_situation_foyer_accompagnementTravailleurSocial');
// le nom de la structure d'accompagnement (usager_situation_foyer_accompagnementTravailleurSocialNomStructure) s'affiche que si on a répondu oui
if (fieldsetAccompagnementTravailleurSocial) {
  document
    .querySelectorAll('#usager_situation_foyer_accompagnementTravailleurSocial input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshAccompagnementTravailleurSocial();
      });
    });

  function refreshAccompagnementTravailleurSocial() {
    const containerNomStructure = document.querySelector('#usager_situation_foyer_accompagnementTravailleurSocialNomStructure').closest('.fr-fieldset__element');
    if (document.querySelector('#usager_situation_foyer_accompagnementTravailleurSocial_0').checked) {
      containerNomStructure.classList.remove('fr-hidden');
    } else {
      containerNomStructure.classList.add('fr-hidden');
      document.querySelector('#usager_situation_foyer_accompagnementTravailleurSocialNomStructure').value = '';
    }
  }
  refreshAccompagnementTravailleurSocial();
}