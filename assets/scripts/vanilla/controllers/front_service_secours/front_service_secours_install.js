let deferredPrompt = null;
const DISMISS_DURATION_DAYS = 30;
const MILLISECOND_IN_DAY = 1000 * 60 * 60 * 24
const STORAGE_KEY = 'pwa-install-prompt-dismissed';

// Vérifier si l'invite a été rejetée récemment
function isPromptDismissed() {
  const dismissedDate = localStorage.getItem(STORAGE_KEY);
  if (!dismissedDate) return false;

  const now = new Date().getTime();
  const dismissed = new Date(dismissedDate).getTime();
  const daysPassed = (now - dismissed) / MILLISECOND_IN_DAY;

  return daysPassed < DISMISS_DURATION_DAYS;
}

// Marquer l'invite comme rejetée
function markPromptDismissed() {
  localStorage.setItem(STORAGE_KEY, new Date().toISOString());
}

// Créer l'invite personnalisée
function createInstallPrompt(prompt) {
  if (!prompt) return null;
  prompt.classList.remove('fr-hidden');

  const cancelButton = prompt.querySelector('.cancel-button');
  const installButton = prompt.querySelector('.install-button');

  // Événement sur le bouton Annuler
  cancelButton.addEventListener('click', () => {
    markPromptDismissed();
    prompt.classList.add('fr-hidden');
  });

  // Événement sur le bouton Ajouter
  installButton.addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();

      deferredPrompt = null;
      prompt.classList.add('fr-hidden');
    }
  });

  return prompt;
}

// Écouter l'événement beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
  // Empêcher l'invite par défaut
  e.preventDefault();

  // Sauvegarder l'événement
  deferredPrompt = e;

  const prompt = document.getElementById('pwa-install-prompt');
  if (!prompt) return null;

  // Afficher l'invite personnalisée uniquement si elle n'a pas été rejetée récemment
  if (!isPromptDismissed()) {
    createInstallPrompt(prompt);
  } else {
    prompt.classList.add('fr-hidden');
  }
});

// Nettoyer l'événement après installation
window.addEventListener('appinstalled', () => {
  deferredPrompt = null;
});
