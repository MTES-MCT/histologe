# 📝 Postmortem – Exposition d'une clé de signature URL

> Exposition d'une clé `SIGNATURE_KEY` dans le dépôt public GitHub du projet Signal Logement

---

## 📅 Date & Heure de l'incident

- **Date de début :** 2026-05-09
- **Date de fin :** 2026-05-11
- **Durée totale :** ~2 jours entre le signalement et la remédiation complète

---

## 👥 Participants

| Rôle                   | Nom                                          |
|------------------------|----------------------------------------------|
| Responsable d'incident | Saidi AHAMADA                                |
| Support sécurité       | Renaud Durand                                |
| Observateurs           | Developpeurs Signal Logement, Tristan Robert |

---

## 📝 Résumé de l'incident

Une vulnérabilité a été signalée via YesWeHack concernant l'exposition d'une variable d'environnement `SIGNATURE_KEY` dans le dépôt GitHub public du projet.

Cette clé est utilisée par le bundle `tilleuls/url-signer-bundle` afin de générer et valider des URLs signées temporaires permettant l'accès à des documents uploadés.

L'exposition de cette clé pouvait permettre à un attaquant de générer des URLs signées valides à condition :
- de connaître les routes et paramètres concernées
- et de disposer des UUID associés aux documents

La clé n'est pas utilisée pour l'authentification ou les réinitialisations de mot de passe.

---

## 💥 Impact

- Nombre d'utilisateurs impactés : Non déterminé
- Fonctionnalités affectées :
    - accès aux documents via URLs signées temporaires
- Perte de données : Non
- SLA respecté : Oui

Impact identifié :
- impact limité à la confidentialité des documents concernés.

Aucun impact identifié sur :
- l'intégrité des données
- la disponibilité du service
- les comptes utilisateurs.

---

## 🔍 Ligne du temps (Timeline)

| Date       | Événement                                  |
|------------|--------------------------------------------|
| 2026-05-09 | Signalement reçu via YesWeHack             |
| 2026-05-11 | Confirmation de l'usage réel de la clé     |
| 2026-05-11 | Génération d'une nouvelle clé de signature |
| 2026-05-11 | Déploiement de la nouvelle clé             |
| 2026-05-11 | Suppression de l'ancienne valeur du `.env` |
| 2026-05-11 | Déploiement sur la branche `main`          |

---

## 🔍 Causes racines (Root Cause Analysis)

- Pourquoi la vulnérabilité existait ?
    - Une valeur réelle de `SIGNATURE_KEY` était présente dans le fichier `.env` versionné dans le dépôt public.

- Pourquoi cette clé est présente dans le dépôt ?
    - La variable d'environnement `SIGNATURE_KEY` est présente afin de fournir un modèle de configuration applicative. Une valeur réelle avait toutefois été renseignée à la place d'une valeur neutre.

- Pourquoi cela n'a pas été détecté plus tôt ?
    - Le point n'avait pas été identifié lors des précédentes revues de code et n'avait pas été signalé par GitGuardian lors de la [création de la pull request concernée](https://github.com/MTES-MCT/histologe/pull/3891/checks?check_run_id=40105374831).

- Pourquoi les outils automatisés ne l'ont pas remonté ?
    - Le sujet est en cours d'investigation.

---

## 🛠 Résolution

Les actions suivantes ont été réalisées :

- Génération d'une nouvelle clé de signature à l'aide d'OpenSSL
- Déploiement de la nouvelle clé sur les environnements de production, staging et demo qui invalide automatiquement les URLs signées générées avec l'ancienne clé
- Suppression de l'ancienne valeur du fichier `.env`

---

## ✅ Actions préventives (Follow-up / Prevention)

| Action                                         | État     | Responsable                  | Échéance |
|------------------------------------------------|----------|------------------------------|----------|
| Renforcement des contrôles CI liés aux secrets | En cours | Tristan Robert/Renaud Durand | -        |

---

## 📣 Communication

- Message interne : Email/Mattermost
- Rapport YesWeHack
---

## 📌 Annexes

- Lien de la PR : https://github.com/MTES-MCT/histologe/pull/5838

---

## 🧪 REX / Leçons apprises

### Ce que nous avons bien fait

- Analyse rapide du périmètre réel de la vulnérabilité
- Rotation immédiate de la clé exposée et invalidation des anciennes URLs signées
- Qualification précise de l'impact réel

### Ce qui aurait pu être mieux

- Détection plus précoce des secrets exposés avant la campagne YesWeHack
- Vérification systématique des fichiers `.env` versionnés
- Contrôles automatisés plus stricts sur les secrets

### Ce qu'on change pour la prochaine fois

- Renforcement des contrôles CI/CD autour des secrets
