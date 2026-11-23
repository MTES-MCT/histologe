# 📝 Postmortem – Récupération d'UUID de brouillons de signalement avec adresses e-mail et adresse postale

> Par le biais du formulaire de signalement, une personne mal intentionnée peut tester des couples adresse postale / adresse e-mail puis vérifier la présence ou non d'un signalement ou d'un brouillon de signalement. Si un brouillon de signalement existait, son uuid était retourné. La personne pouvait alors aller l'éditer sans contrôle.

---

## 📅 Date & Heure de l'incident

- **Date de début vulnérabilité :** 2024-05-07.
- **Date de début signalement :** 2026-11-14.
- **Date de fin :** 2026-11-23.
- **Début de l'exposition :** date de mise en ligne de la détection de doublon dans le formulaire de signalement v2.
- **Durée totale :** ~9 jour entre le signalement et la mise en ligne de la dernière correction

---

## 👥 Participants

| Rôle                   | Nom                                |
|------------------------|------------------------------------|
| Responsable d'incident | Emilien Schneider                  |
| Responsable d'incident | Numa Bord                          |
| Support sécurité       | Renaud Durand                      |
| Observateurs           | Developpeurs Signal Logement       |

---

## 📝 Résumé de l'incident

### Récupération d'UUID de brouillon de signalement
Un chercheur du programme de bug bounty a signalé la possibilité de récupérer des identifiants uniques de brouillons de signalement existant, grâce à une route `/signalement-draft/check` chargée de détecter les doublons de signalements.

Un appel de type `POST` sur cette route renvoyait les uuid des brouillons existants.

Avec les UUIDs retournés, cette personne pouvait ensuite :
- se rendre sur le brouillon de signalement pour l'éditer / le soumettre
- récupérer l'ensemble des informations du signalement en cours, au format json, via la route `/signalement-draft/{uuid}/informations`

### Envoi de formulaire de suivi usager depuis un autre usager
Par ailleurs, le chercheur signale un souci annexe dans les pages de suivi de signalement : 

Un usager identifié pouvait accéder et poster des modifications sur d'autres pages de suivi de signalement, à condition d'avoir accès au code de suivi de l'autre signalement.

Aucune route ne donne accès au code de suivi de signalement.

---

## 💥 Impact

- Nombre d'utilisateurs impactés : Aucun
- Fonctionnalités affectées :
    - formulaire de signalement
- Perte de données : Non
- SLA respecté : Oui

Impact identifié :
- Aucun

Aucun impact identifié sur :
- l'intégrité des données
- la disponibilité du service
- les comptes utilisateurs.

---

## 🔍 Ligne du temps (Timeline)

| Date | Événement |
|---|---|
| 2024-02-14 | Mise en ligne du formulaire de signalement v2 |
| 2024-05-07 | Mise en ligne de la détection des doublons |
| 2026-11-14 | Signalement reçu via YesWeHack |
| 2026-11-16 | Correction du code concernant l'accès aux pages de suivi de dossier |
| 2026-11-17 | Validation du code et déploiement de la correction |
| 2026-11-17 | Discussion et accord sur la façon de corriger le fonctionnement de la détection de doublons |
| 2026-11-20 | Correction du code concernant les UUIDs dans le formulaire de signalement |
| 2026-11-23 | Validation du code et déploiement de la correction |

---

## 🔍 Causes racines (Root Cause Analysis)

- Pourquoi la vulnérabilité existait-elle ?

  - Lors du dépôt de signalement des usagers, on détecte d'éventuels brouillons déjà commencés avec le même couple adresse postale + adresse e-mail.
  - Pour faciliter leur parcours, on envoyait le lien pour suivre directement le brouillon déjà entamés.
  - Pour cela, on transmettait l'uuid du brouillon commencé.

---

## 🛠 Résolution

- Correction du contrôle d'accès sur les différentes routes de la page de suivi usager
- Suppression des UUIDs existants en retour de la route `/signalement-draft/check`

---

## ✅ Actions préventives (Follow-up / Prevention)

| Action | État | Responsable |
|---|---|---|
| Automatisation d'audit sécurisé via IA | En cours | Equipe tech |

## 📣 Communication

- Message interne : Email/Tchap
- Rapport YesWeHack

## 📌 Annexes

- Lien de la PR concernant la page de suivi usager : https://github.com/MTES-MCT/histologe/pull/6704
- Lien de la PR concernant les UUID de brouillons : https://github.com/MTES-MCT/histologe/pull/6740

## 🧪 REX / Leçons apprises

- Proscrire les retours de listes d'identifiants sur les routes qui ne sont pas destinées aux back-office

### Ce que nous avons bien fait

- Echanges rapides pour éviter de renvoyer des identifiants
- La correction pour l'accès aux pages de suivi usager a été faite en parallèle de la remontée

### Ce qui aurait pu être mieux

- Le ticket de correction pour les UUID de brouillon a été pris tardivement

### Ce qui doit être amélioré

- Audit automatisé concernant la sécurité plus régulier
