import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour la gestion des modales des agents/utilisateurs d'un partenaire.
 * 
 * Avantages par rapport aux `document.addEventListener('click')` globaux :
 * 1. Cycle de vie ciblé : ce contrôleur ne s'instancie que si `data-controller="partner-user-modal"` est présent sur la page.
 * 2. Zéro écouteur global fantôme : aucun listener ne tourne sur les autres pages du Back-Office.
 * 3. Délégation d'événements native : Stimulus gère l'attachement via `data-action` sans surcoût.
 * 4. Scope isolé via les targets : manipulation propre du DOM sans requêtes globales `document.querySelector`.
 */
export default class extends Controller {
  static targets = [
    // Modale de transfert
    'transferUsername',
    'transferUserId',
    // Modale de suppression utilisateur
    'deleteUsername',
    'deleteUserEmail',
    'deleteUserId',
  ];

  /**
   * Prépare la modale de transfert d'un utilisateur vers un autre partenaire.
   * Déclenché par : data-action="click->partner-user-modal#prepareTransfer"
   */
  prepareTransfer(event) {
    const button = event.currentTarget;
    const username = button.dataset.username;
    const userId = button.dataset.userid;

    if (this.hasTransferUsernameTarget) {
      this.transferUsernameTarget.textContent = username;
    }
    if (this.hasTransferUserIdTarget) {
      this.transferUserIdTarget.value = userId;
    }
  }

  /**
   * Prépare la modale de suppression d'un utilisateur d'un partenaire.
   * Déclenché par : data-action="click->partner-user-modal#prepareDelete"
   */
  prepareDelete(event) {
    const button = event.currentTarget;
    const username = button.dataset.username;
    const userEmail = button.dataset.useremail;
    const userId = button.dataset.userid;

    // Mise à jour de tous les éléments affichant le nom d'utilisateur dans la modale
    this.deleteUsernameTargets.forEach((el) => {
      el.textContent = username;
    });

    // Mise à jour de tous les éléments affichant l'email dans la modale
    this.deleteUserEmailTargets.forEach((el) => {
      el.textContent = userEmail;
    });

    // Mise à jour de l'input caché contenant l'ID de l'utilisateur à supprimer
    if (this.hasDeleteUserIdTarget) {
      this.deleteUserIdTarget.value = userId;
    }
  }
}
