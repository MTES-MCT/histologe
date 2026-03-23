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
    const containerCaisseAllocation = document
      .querySelector('#usager_situation_foyer_caisseAllocation')
      .closest('.fr-fieldset__element');
    const containerNumAllocataire = document
      .querySelector('#usager_situation_foyer_numAllocataire')
      .closest('.fr-fieldset__element');
    const containerTypeAllocation = document
      .querySelector('#usager_situation_foyer_typeAllocation')
      .closest('.fr-fieldset__element');
    const containerMontantAllocation = document
      .querySelector('#usager_situation_foyer_montantAllocation')
      .closest('.fr-fieldset__element');
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

const fieldsetAccompagnementTravailleurSocial = document?.querySelector(
  '#usager_situation_foyer_accompagnementTravailleurSocial'
);
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
    const containerNomStructure = document
      .querySelector('#usager_situation_foyer_accompagnementTravailleurSocialNomStructure')
      .closest('.fr-fieldset__element');
    if (
      document.querySelector('#usager_situation_foyer_accompagnementTravailleurSocial_0').checked
    ) {
      containerNomStructure.classList.remove('fr-hidden');
    } else {
      containerNomStructure.classList.add('fr-hidden');
      document.querySelector(
        '#usager_situation_foyer_accompagnementTravailleurSocialNomStructure'
      ).value = '';
    }
  }
  refreshAccompagnementTravailleurSocial();
}

const fieldsetDpe = document?.querySelector('#informations_generales_dpe');
// le choix de la classe énergétique ne s'affiche que si on a répondu oui au Dpe
if (fieldsetDpe) {
  document
    .querySelectorAll('#informations_generales_dpe input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshClasseEnergetique();
      });
    });

  function refreshClasseEnergetique() {
    const containerClasseEnergetique = document
      .querySelector('#informations_generales_classeEnergetique')
      .closest('.fr-fieldset__element');
    if (document.querySelector('#informations_generales_dpe_0').checked) {
      containerClasseEnergetique.classList.remove('fr-hidden');
    } else {
      containerClasseEnergetique.classList.add('fr-hidden');
      document.querySelector('#informations_generales_classeEnergetique').value = '';
    }
  }
  refreshClasseEnergetique();
}

const fieldsetNbEnfants = document?.querySelector('#informations_generales_nbEnfantsDansLogement');
// le choix de la classe énergétique ne s'affiche que si on a répondu oui au Dpe
if (fieldsetNbEnfants) {
  fieldsetNbEnfants.addEventListener('input', () => {
    refreshEnfantsMoinsSixAns();
  });

  function refreshEnfantsMoinsSixAns() {
    const containerEnfantsMoinsSixAns = document
      .querySelector('#informations_generales_enfantsDansLogementMoinsSixAns')
      .closest('.fr-fieldset__element');
    if (document.querySelector('#informations_generales_nbEnfantsDansLogement').value > 0) {
      containerEnfantsMoinsSixAns.classList.remove('fr-hidden');
    } else {
      containerEnfantsMoinsSixAns.classList.add('fr-hidden');
    }
  }
  refreshEnfantsMoinsSixAns();
}

// Type et composition du logement
const natureLogementSelect = document?.querySelector('#type_composition_natureLogement');
if (natureLogementSelect) {
  const natureAutrePrecisionContainer = document.querySelector('#type_composition_natureAutrePrecision')?.closest('.fr-fieldset__element');
  const etageContainer = document.querySelector('#type_composition_appartementEtage')?.closest('.fr-fieldset__element');

  function refreshNatureAutrePrecision() {
    if (natureAutrePrecisionContainer) {
      if (natureLogementSelect.value === 'appartement') {
        etageContainer.classList.remove('fr-hidden');
      } else {
        etageContainer.classList.add('fr-hidden');
      }
      if (natureLogementSelect.value === 'autre') {
        natureAutrePrecisionContainer.classList.remove('fr-hidden');
      } else {
        natureAutrePrecisionContainer.classList.add('fr-hidden');
      }
    }
  }

  natureLogementSelect.addEventListener('change', () => {
    refreshNatureAutrePrecision();
  });
  refreshNatureAutrePrecision();
}

const fieldsetPieceUnique = document?.querySelector('#type_composition_pieceUnique');
if (fieldsetPieceUnique) {
  document
    .querySelectorAll('#type_composition_pieceUnique input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshNbPieces();
      });
    });

  function refreshNbPieces() {
    const nbPiecesContainer = document.querySelector('#type_composition_nbPieces')?.closest('.fr-fieldset__element');
    if (nbPiecesContainer) {
      const pieceUniquePlusieursPiecesChecked = document.querySelector('#type_composition_pieceUnique input[value="plusieurs_pieces"]:checked');
      if (pieceUniquePlusieursPiecesChecked) {
        nbPiecesContainer.classList.remove('fr-hidden');
      } else {
        nbPiecesContainer.classList.add('fr-hidden');
        nbPiecesContainer.querySelector('input').value = '1';
      }
    }
  }
  refreshNbPieces();
}

const fieldsetCuisine = document?.querySelector('#type_composition_cuisine');
if (fieldsetCuisine) {
  document
    .querySelectorAll('#type_composition_cuisine input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshCuisineCollective();
        refreshWcCuisine();
      });
    });

  refreshCuisineCollective();
}

const fieldsetSalleDeBain = document?.querySelector('#type_composition_salleDeBain');
if (fieldsetSalleDeBain) {
  document
    .querySelectorAll('#type_composition_salleDeBain input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshSalleDeBainCollective();
      });
    });

  refreshSalleDeBainCollective();
}

const fieldsetWc = document?.querySelector('#type_composition_wc');
if (fieldsetWc) {
  document
    .querySelectorAll('#type_composition_wc input[type="radio"]')
    .forEach((element) => {
      element.addEventListener('change', () => {
        refreshWcCollective();
        refreshWcCuisine();
      });
    });

  refreshWcCollective();
}

// Gérer l'affichage du champ "wcCuisine" (visible uniquement si cuisine ET wc sont à 'oui')
const fieldsetWcCuisine = document?.querySelector('#type_composition_cuisine, #type_composition_wc');
if (fieldsetWcCuisine) {
  refreshWcCuisine();
}

function refreshCuisineCollective() {
  const containerCuisineCollective = document.querySelector('#type_composition_cuisineCollective')?.closest('.fr-fieldset__element');
  if (containerCuisineCollective) {
    const cuisineNonChecked = document.querySelector('#type_composition_cuisine input[value="non"]:checked');
    if (cuisineNonChecked) {
      containerCuisineCollective.classList.remove('fr-hidden');
    } else {
      document.querySelector('#type_composition_cuisineCollective input[type="radio"]').checked = false;
      containerCuisineCollective.classList.add('fr-hidden');
    }
  }
}
function refreshSalleDeBainCollective() {
  const containerSalleDeBainCollective = document.querySelector('#type_composition_salleDeBainCollective')?.closest('.fr-fieldset__element');
  if (containerSalleDeBainCollective) {
    const salleDeBainNonChecked = document.querySelector('#type_composition_salleDeBain input[value="non"]:checked');
    if (salleDeBainNonChecked) {
      containerSalleDeBainCollective.classList.remove('fr-hidden');
    } else {
      document.querySelector('#type_composition_salleDeBainCollective input[type="radio"]').checked = false;
      containerSalleDeBainCollective.classList.add('fr-hidden');
    }
  }
}
function refreshWcCollective() {
  const containerWcCollective = document.querySelector('#type_composition_wcCollective')?.closest('.fr-fieldset__element');
  if (containerWcCollective) {
    const wcNonChecked = document.querySelector('#type_composition_wc input[value="non"]:checked');
    if (wcNonChecked) {
      containerWcCollective.classList.remove('fr-hidden');
    } else {
      document.querySelector('#type_composition_wcCollective input[type="radio"]').checked = false;
      containerWcCollective.classList.add('fr-hidden');
    }
  }
}
function refreshWcCuisine() {
  const containerWcCuisine = document.querySelector('#type_composition_wcCuisine')?.closest('.fr-fieldset__element');
  if (containerWcCuisine) {
    const cuisineOuiChecked = document.querySelector('#type_composition_cuisine input[value="oui"]:checked');
    const wcOuiChecked = document.querySelector('#type_composition_wc input[value="oui"]:checked');

    if (cuisineOuiChecked && wcOuiChecked) {
      containerWcCuisine.classList.remove('fr-hidden');
    } else {
      containerWcCuisine.classList.add('fr-hidden');
      document.querySelector('#type_composition_wcCuisine input[type="radio"]').checked = false;
    }
  }
}
