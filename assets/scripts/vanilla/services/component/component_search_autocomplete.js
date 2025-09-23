document.addEventListener('DOMContentLoaded', initSearchAutocompleteWidgets);

function initSearchAutocompleteWidgets() {
    const containers = document.querySelectorAll('.search-autocomplete-container');

    containers.forEach(container => {
        const input = container.querySelector('input');
        const datalist = container.querySelector('datalist');

        if (!input || !datalist) return;

        const choices = JSON.parse(input.getAttribute('data-autocomplete-choices') || '[]');

        function submitForm() {
            const form = document.getElementById('search-dashboard-averifier-form');
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
    });
}