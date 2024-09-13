const refusAffectationButtons = document.querySelectorAll('button[name="signalement-affectation-response[deny]"]');
refusAffectationButtons.forEach(button => {
    button.addEventListener('click', function(event) {
        if (!confirm('Êtes-vous certain de vouloir refuser ce signalement ?')) {
            event.preventDefault();
        }
    });
});
const refusValidationButtons = document.querySelectorAll('button[name="signalement-validation-response[deny]"]');
refusValidationButtons.forEach(button => {
    button.addEventListener('click', function(event) {
        if (!confirm('Êtes-vous certain de vouloir refuser ce signalement ?')) {
            event.preventDefault();
        }
    });
});
const cancelRefusButton = document.querySelectorAll('button[name="signalement-affectation-response[accept]]"]');
cancelRefusButton.forEach(button => {
    button.addEventListener('click', function(event) {
        if (!confirm('Êtes-vous certain de vouloir accepter ce signalement ?')) {
            event.preventDefault();
        }
    });
});
const validationButton = document.querySelectorAll('button[name="signalement-validation-response[accept]"]');
validationButton.forEach(button => {
    button.addEventListener('click', function(event) {
        if (!confirm('Êtes-vous certain de vouloir valider ce signalement ?')) {
            event.preventDefault();
        }
    });
});