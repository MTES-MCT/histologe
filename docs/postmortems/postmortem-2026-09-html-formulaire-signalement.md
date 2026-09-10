# 📝 Postmortem – Affichage HTML d'informations saisies par l'usager

> Lorsqu'un usager saisissait certaines informations, elles étaient affichées dans le résumé de signalement
> avec une directive Vue v-html qui n'échappait pas les caractères HTML. Un utilisateur pouvait donc
> saisir du html (par exemple une iframe) et l'afficher plus tard. Il pouvait donc envoyer automatiquement un
> lien de suivi à une personne tierce, qui se retrouvait sur le résumé de signalement avec du code html non-maitrisé.

---

## 📅 Date & Heure de l'incident

- **Date de début :** 2026-09-03
- **Date de fin :** 2026-09-09
- **Durée totale :** ~6 jours entre le signalement et la remédiation complète

---

## 👥 Participants

| Rôle                   | Nom                                |
|------------------------|------------------------------------|
| Responsable d'incident | Emilien Schneider                  |
| Support sécurité       | Renaud Durand                      |
| Observateurs           | Developpeurs Signal Logement       |

---

## 📝 Résumé de l'incident

Une vulnérabilité de stockage de faille XSS a été signalée via YesWeHack dans le formulaire de signalement disponible à l'URL suivante :

`/signalement/`

Cette route permet aux usagers de créer un signalement.
Les usagers peuvent démarrer un signalement et saisir une adresse e-mail pour terminer le signalement plus tard.

Un faux usager pouvait créer un signalement et saisir l'adresse e-mail d'une personne tierce pour que celle-ci soit notifiée.
Le faux usager pouvait alors remplir certains champs avec du code HTML.

Une fois arrivé à l'écran de résumé de fin de parcours, le HTML était interprété et s'affichait.
Si un code HTML de type `<iframe>` était inséré, un site tiers pouvait être affiché/exécuté.
Un contrôle CSP du site limite les URLS qui peuvent s'afficher à un nombre restreints.

Le faux usager peut ensuite arrêter son signalement et recevoir une notification par e-mail pour finaliser son signalement.

Les champs concernés étaient l'ensemble des champs en saisie libre, non-contrôlé lors de l'étape de saisie,
et qui s'affichent dans le résumé :
 - adresse_logement_adresse
 - adresse_logement_complement_adresse_autre
 - vos_coordonnees_occupant_prenom
 - vos_coordonnees_occupant_nom
 - coordonnees_occupant_prenom
 - coordonnees_occupant_nom
 - vos_coordonnees_tiers_nom_organisme
 - vos_coordonnees_tiers_prenom
 - vos_coordonnees_tiers_nom
 - coordonnees_bailleur_prenom
 - coordonnees_bailleur_nom
 - coordonnees_bailleur_adresse
 - travailleur_social_accompagnement_nom_structure
 - travailleur_social_accompagnement_inviter_referent_comme_tiers
 - travailleur_social_accompagnement_inviter_referent_comme_tiers_nom
 - travailleur_social_accompagnement_inviter_referent_comme_tiers_prenom
 - travailleur_social_accompagnement_nom_referent
 - travailleur_social_accompagnement_prenom_referent
 - info_procedure_bail_reponse
 - info_procedure_bail_numero
 - info_procedure_reponse_assurance

---

## 💥 Impact

- Nombre d'utilisateurs impactés : Aucun
- Fonctionnalités affectées :
    - formulaire de signalement
- Perte de données : Non
- SLA respecté : Oui

Impact identifié :
- Faille XSS limitée aux sites ouverts via la CSP

Aucun impact identifié sur :
- l'intégrité des données
- la disponibilité du service
- les comptes utilisateurs.

---

## 🔍 Ligne du temps (Timeline)

| Date       | Événement                                           |
| ---------- |-----------------------------------------------------|
| 2026-09-03 | Signalement reçu via YesWeHack                      |
| 2026-09-04 | Analyse de la faille et de son périmètre            |
| 2026-09-07 | Ouverture de la pull request contenant le correctif |
| 2026-09-08 | Prise en compte des retours de revue de code        |
| 2026-09-09 | Déploiement du correctif sur `main`                 |

---

## 🔍 Causes racines (Root Cause Analysis)

- Pourquoi la vulnérabilité existait-elle ?
    - La directive v-html affichait sans contrôle les informations saisies par l'usager

---

## 🛠 Résolution

Les actions suivantes ont été réalisées :

- suppression de la directive v-html lorsque elle ne contient pas uniquement du code interne
- correction d'une autre faille similaire dans le formulaire pour la liste des signalements démarrés par un usager

---

## ✅ Actions préventives (Follow-up / Prevention)

| Action                                                                                              | État        | Responsable       | Échéance |
|-----------------------------------------------------------------------------------------------------|-------------|-------------------|----------|
| Audit et correction de l'ensemble du code Vue pour vérifier l'utilisation de la directive v-html    | Terminé     | Emilien Schneider | -        |
| Ne plus utiliser la directive v-html si les variables ne sont pas internes                          | Terminé     | Emilien Schneider | -        |

---

## 📣 Communication

- Message interne : Email/Mattermost
- Rapport YesWeHack
---

## 📌 Annexes

- Lien de la PR : https://github.com/MTES-MCT/histologe/pull/6294

---

## 🧪 REX / Leçons apprises

### Ce que nous avons bien fait

- Analyse rapide du périmètre réel de la vulnérabilité ;

### Ce qui aurait pu être mieux

- RAS ;

### Ce qu'on change pour la prochaine fois

- Ne plus utiliser la directive v-html si les variables ne sont pas internes
