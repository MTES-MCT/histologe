# 📝 Postmortem – Prise de contrôle d'un sous-domaine via un CNAME résiduel

> Le retrait en avril 2025 du domaine personnalisé `beta.signal-logement.beta.gouv.fr` sur scalingo sans avoir supprimé l'enregistrement DNS correspondant nous a exposait à une prise de console DNS du sous-domaine.

## 📅 Date & Heure de l'incident

- **Date de début vulnérabilité :** 2025-04-01.
- - **Date de début signalement :** 2025-11-11.
- **Date de fin :** 2026-11-12.
- **Début de l'exposition :**correspondant au retrait du domaine personnalisé de l'application Scalingo.
- **Durée totale :** ~1 jour entre le signalement et la remédiation complète


## 👥 Participants

| Rôle | Intervenant |
|---|---|
| Suivi auprès de l'hébergeur | Saidi Ahamada |
| Support sécurité       | Renaud Durand                      |
| Analyse du fonctionnement de la plateforme | Équipe InfoSec Scalingo |

## 📝 Résumé de l'incident

Un chercheur du programme de bug bounty a signalé la prise de contrôle du sous-domaine `beta.signal-logement.beta.gouv.fr`.

Ce domaine avait été retiré des domaines personnalisés de notre application Scalingo. L'enregistrement DNS suivant était cependant resté présent :

    beta.signal-logement.beta.gouv.fr. IN CNAME histologe-sites-faciles.osc-fr1.scalingo.io.

Le chercheur a ajouté notre sous-domaine comme domaine personnalisé de sa propre application Scalingo. Il a ainsi pu afficher une page de démonstration sous notre sous-domaine, sans modifier notre DNS ni accéder à notre compte.

Scalingo a confirmé que son routage sélectionne l'application à partir du nom de domaine demandé, dès lors que celui-ci pointe vers son infrastructure. La cible du CNAME ne réserve donc pas le domaine à l'application initiale.

## 💥 Impact

- Nombre d'utilisateurs impactés : Aucun
- Fonctionnalités affectées :
    - sous-domaine rendu accessible par un tiers `beta.signal-logement.beta.gouv.fr`
- Perte de données : Non
- SLA respecté : Oui

Impact identifié :
- Prise de contrôle qui a permis de publier du contenu sous le sous-domaine beta de Signal Logement. La démonstration fournie montre une page d'exemple Django.

Aucun impact identifié sur :
- l'intégrité des données
- la disponibilité du service
- les comptes utilisateurs.

## 🔍 Ligne du temps (Timeline)

| Date | Événement |
|---|---|
| 2025-04-01 | Retrait du domaine personnalisé de notre application Scalingo, sans suppression du CNAME |
| 2026-11-11 | Signalement reçu via YesWeHack|
| 2026-11-12 | Suppression de l'enregistrement DNS confirmée dans le rapport |
| 2026-11-16 | Sollicitation du support Scalingo pour comprendre le mécanisme |
| 2026-11-17 | Scalingo confirme le mécanisme, l'absence de rattachement du domaine et l'absence de résolution DNS |

## 🔍 Causes racines (Root Cause Analysis)

- Pourquoi la vulnérabilité existait-elle ?

  - Le retrait du domaine personnalisé dans Scalingo n'a pas été accompagné de la suppression de l'enregistrement DNS (oubli).
  - Le CNAME continuait à diriger les requêtes vers l'infrastructure Scalingo.
  - Le fonctionnement confirmé par Scalingo permettait à un autre compte de rattacher ce domaine à son application sans modification de notre zone DNS.

## 🛠 Résolution

- Suppression du CNAME de `beta.signal-logement.beta.gouv.fr`.
- Vérification par Scalingo que le domaine n'était plus rattaché à une application.
- Vérification par Scalingo que le nom ne se résolvait plus côté DNS.

## ✅ Actions préventives (Follow-up / Prevention)

| Action | État | Responsable |
|---|---|---|
| Supprimer le CNAME concerné | Terminé | Équipe Signal Logement |
| Vérifier les autres enregistrements DNS pointant vers Scalingo et leur rattachement à une application | Terminé | Équipe Signal Logement|
| Ajouter le nettoyage DNS à la procédure de suppression d'une application ou d'un domaine personnalisé | À réaliser | Équipe Signal Logement|
| Demander à Scalingo si une vérification de propriété par TXT est proposée ou prévue | À suivre | Saidi Ahamada |

## 📣 Communication

- Message interne : Email/Tchap
- Rapport YesWeHack

## 📌 Annexes

- Lien de la PR : https://github.com/MTES-MCT/histologe/pull/6724
- [Documentation Scalingo sur le routage](https://doc.scalingo.com/platform/networking/public/routing).
- [Documentation Scalingo sur les domaines personnalisés](https://doc.scalingo.com/platform/networking/public/domains/custom).

## 🧪 REX / Leçons apprises

### Ce que nous avons bien fait

- La suppression du CNAME a été rapidement supprimé suite au signalement.
- L'échange avec Scalingo a permis de confirmer le mécanisme.

### Ce qui aurait pu être mieux

- RAS ;

### Ce qui doit être amélioré

- Le retrait d'un domaine personnalisé doit inclure le nettoyage de sa configuration DNS.
- Mettre en place une revue périodique des enregistrements DNS pour vérifier leur utilité et leur rattachement aux services utilisés.
- Suivre auprès de Scalingo la possibilité d’ajouter une vérification de propriété des domaines.
