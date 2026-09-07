/**
 * Construit un message visible quand la commune/le code postal saisis par l'usager
 * sont corrigés automatiquement (sélection d'un bâtiment RNB, géocodage BAN...).
 * Retourne une chaîne vide si rien n'a changé, pour ne pas afficher de message inutile.
 *
 * Utilisé à deux endroits qui font tous les deux ce type de correction :
 * - TheSignalementAppForm.vue::handleValidateAddress (géocodage BAN)
 * - SignalementFormAddress.vue::handleSubmitPickLocation (sélection RNB)
 */
export function buildAddressCorrectionMessage (
  oldCodePostal: string,
  oldCommune: string,
  newCodePostal: string,
  newCommune: string
): string {
  if (oldCodePostal === newCodePostal && oldCommune === newCommune) {
    return ''
  }
  return `Adresse mise à jour : ${oldCodePostal} ${oldCommune} → ${newCodePostal} ${newCommune}`
}
