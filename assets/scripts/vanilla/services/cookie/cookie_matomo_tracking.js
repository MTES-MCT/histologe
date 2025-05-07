/**
 * Active le suivi Matomo après que l'utilisateur ait donné son consentement.
 * Cette fonction doit être appelée après que l'utilisateur ait confirmé ses choix de consentement.
 * Elle active le suivi de page et le suivi des liens pour Matomo.
 *
 * Ces cookies Matomo sont utilisés en conjonction avec des dimensions personnalisées créées :
 * - Dimension "Rôle" : Utilisée pour suivre le rôle spécifique de l'utilisateur sur le site.
 * - Dimension "Territoire" : Utilisée pour suivre la localisation géographique de l'utilisateur.
 * - Dimension "Partenaire" : Utilisée pour suivre le partenaires de l'utilisateur.
 *
 * Veuillez noter que les dimensions personnalisées permettent de segmenter et d'analyser les données de suivi
 * en fonction de ces caractéristiques spécifiques.
 *
 */

/* global _paq */

export function enableMatomoTracking () {
  _paq.push(['rememberConsentGiven'])
}

export function disableMatomoTracking () {
  _paq.push(['forgetConsentGiven'])
}
