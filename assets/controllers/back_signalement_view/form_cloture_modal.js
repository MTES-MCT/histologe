const radioButtons = document.querySelectorAll('input[name="cloture[publicSuivi]"]');
const cloturePublicOui = document.querySelector('#warning_cloture_public_oui');
const cloturePublicNon = document.querySelector('#warning_cloture_public_non');
radioButtons.forEach(radioButton => {
    radioButton.addEventListener('change', function(event) {
        const value = event.target.value;
        if (value === '1') {
            cloturePublicOui?.classList.remove('fr-hidden')
            cloturePublicNon?.classList.add('fr-hidden')
        } else if (value === '0') {
            cloturePublicOui?.classList.add('fr-hidden')
            cloturePublicNon?.classList.remove('fr-hidden')
        }
    });
});

