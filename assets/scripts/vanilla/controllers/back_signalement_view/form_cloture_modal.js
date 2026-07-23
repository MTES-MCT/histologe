const radioButtons = document.querySelectorAll('input[name="cloture[isVisibleForUsager]"]');
const cloturePublicOui = document.querySelector('#warning_cloture_public_oui');
const cloturePublicNon = document.querySelector('#warning_cloture_public_non');
radioButtons.forEach((radioButton) => {
  radioButton.addEventListener('change', function (event) {
    const value = event.target.value;
    if (value === '1') {
      cloturePublicOui?.classList.remove('fr-hidden');
      cloturePublicNon?.classList.add('fr-hidden');
    } else if (value === '0') {
      cloturePublicOui?.classList.add('fr-hidden');
      cloturePublicNon?.classList.remove('fr-hidden');
    }
  });
});

//cloture affectation v2
const motifClotureSelect = document.querySelector('#close_affectation_motifCloture');
const travauxContainer = document.querySelector(
  '#close_affectation_travauxMiseEnConformite_container'
);

function updateTravauxVisibility() {
  if (!motifClotureSelect || !travauxContainer) return;
  const selectedOption = motifClotureSelect.options[motifClotureSelect.selectedIndex];
  const needTravaux = selectedOption?.hasAttribute('data-need-travaux-precisions');
  travauxContainer.classList.toggle('fr-hidden', !needTravaux);
}

motifClotureSelect?.addEventListener('change', updateTravauxVisibility);
updateTravauxVisibility();
