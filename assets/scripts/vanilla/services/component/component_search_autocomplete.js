document.addEventListener('DOMContentLoaded', initSearchAutocompleteWidgets);

function initSearchAutocompleteWidgets() {
    const containers = document.querySelectorAll('.search-autocomplete-container');

    containers.forEach(container => {
        const formName = container.getAttribute('data-form-target-name');
        const form = document.getElementById(formName);
        const input = container.querySelector('input');
        const datalist = container.querySelector('datalist');
        const errorMessage = container.querySelector('.fr-message--error-value-list');

        if (!input || !datalist) return;

        const choices = JSON.parse(input.getAttribute('data-autocomplete-choices') || '[]');

        function submitForm() {
            if (formName == undefined || formName == '') {
              return;
            }
            if (form) {
                form.submit();
            }
        }

        function isValidChoice(value) {
            return choices.includes(value);
        }

        // Détecter la sélection d'un élément dans la datalist
        input.addEventListener('input', function() {
            // Vérifier si la valeur correspond exactement à un choix de la liste
            if (isValidChoice(this.value)) {
                // Petit délai pour s'assurer que la sélection est complète
                setTimeout(() => {
                    submitForm();
                }, 100);
            }
        });

        // Alternative : détecter avec l'événement change
        input.addEventListener('change', function() {
            if (isValidChoice(this.value)) {
                submitForm();
            }
        });

        // Vérifier la valeur lorsque le formulaire est posté
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!isValidChoice(input.value)) {
                    event.preventDefault();
                    errorMessage.classList.remove('fr-hidden');
                } else {
                    errorMessage.classList.add('fr-hidden');
                }
            });
        }
    });
}