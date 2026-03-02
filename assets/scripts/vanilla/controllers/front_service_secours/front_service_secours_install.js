let deferredPrompt = null;
const DISMISS_DURATION_DAYS = 30;
const STORAGE_KEY = 'pwa-install-prompt-dismissed';

// Vérifier si l'invite a été rejetée récemment
function isPromptDismissed() {
  const dismissedDate = localStorage.getItem(STORAGE_KEY);
  if (!dismissedDate) return false;

  const now = new Date().getTime();
  const dismissed = new Date(dismissedDate).getTime();
  const daysPassed = (now - dismissed) / (1000 * 60 * 60 * 24);

  return daysPassed < DISMISS_DURATION_DAYS;
}

// Marquer l'invite comme rejetée
function markPromptDismissed() {
  localStorage.setItem(STORAGE_KEY, new Date().toISOString());
}

// Créer l'invite personnalisée
function createInstallPrompt() {
  const prompt = document.createElement('div');
  prompt.id = 'pwa-install-prompt';
  prompt.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 20px;
    max-width: 400px;
    width: calc(100% - 40px);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 15px;
  `;

  const message = document.createElement('p');
  message.textContent = 'Ajouter Signal Logement à l\'écran d\'accueil';
  message.style.cssText = `
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    color: #161616;
  `;

  const buttonContainer = document.createElement('div');
  buttonContainer.style.cssText = `
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  `;

  const cancelButton = document.createElement('button');
  cancelButton.textContent = 'Annuler';
  cancelButton.className = 'fr-btn fr-btn--secondary';
  cancelButton.type = 'button';

  const installButton = document.createElement('button');
  installButton.textContent = 'Ajouter';
  installButton.className = 'fr-btn';
  installButton.type = 'button';

  // Événement sur le bouton Annuler
  cancelButton.addEventListener('click', () => {
    markPromptDismissed();
    prompt.remove();
  });

  // Événement sur le bouton Ajouter
  installButton.addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;

      if (outcome === 'accepted') {
        console.log('PWA installée avec succès');
      }

      deferredPrompt = null;
      prompt.remove();
    }
  });

  buttonContainer.appendChild(cancelButton);
  buttonContainer.appendChild(installButton);

  prompt.appendChild(message);
  prompt.appendChild(buttonContainer);

  return prompt;
}

// Écouter l'événement beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
  // Empêcher l'invite par défaut
  e.preventDefault();

  // Sauvegarder l'événement
  deferredPrompt = e;

  // Afficher l'invite personnalisée uniquement si elle n'a pas été rejetée récemment
  if (!isPromptDismissed()) {
    const prompt = createInstallPrompt();
    document.body.appendChild(prompt);
  }
});

// Nettoyer l'événement après installation
window.addEventListener('appinstalled', () => {
  console.log('PWA installée');
  deferredPrompt = null;

  const prompt = document.getElementById('pwa-install-prompt');
  if (prompt) {
    prompt.remove();
  }
});
