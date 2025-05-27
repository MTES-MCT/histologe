# 📝 Postmortem – [Nom de l’incident]

> Exemple : Postmortem – Suppression accidentelle de la base de données production

---

## 📅 Date & Heure de l’incident

- **Date de début :** YYYY-MM-DD HH:mm
- **Date de fin :** YYYY-MM-DD HH:mm
- **Durée totale :** XX minutes

---

## 👥 Participants

| Rôle                   | Nom                   |
|------------------------|-----------------------|
| Responsable d’incident | John Doe              |
| Observateurs / Support | Jane Doe, Richard Doe |

---

## 📖 Résumé de l'incident

Un résumé factuel, clair et sans interprétation.
> Exemple :  
À 14h32, un script de déploiement exécuté sur l'environnement de production a supprimé par erreur la base de données principale (`myapp_prod`). L'application a été indisponible pendant 42 minutes. Une restauration a été effectuée à partir du dernier backup quotidien.

---

## 💥 Impact

- Nombre d'utilisateurs impactés : …
- Fonctionnalités affectées : …
- Perte de données : Oui / Non
- SLA respecté : Oui / Non

---

## 🔍 Ligne du temps (Timeline)

| Heure (UTC+X) | Événement                               |
|---------------|-----------------------------------------|
| 14:32         | Déclenchement du script erroné          |
| 14:34         | Alertes de monitoring reçues (Downtime) |
| 14:36         | Mise en maintenance                     |
| 14:42         | Début de la restauration de la base     |
| 15:14         | Restauration terminée                   |
| 15:18         | Application de nouveau disponible       |

---

## 🧠 Causes racines (Root Cause Analysis)

> Utiliser l’approche "5 Why" si possible.

- Pourquoi la base a été supprimée ?
- Pourquoi le script n’a pas demandé de confirmation ?
- Pourquoi ce script de test a été exécuté en production ?
- Pourquoi la vérification d’environnement n’était pas incluse ?
- ......
---

## ✅ Résolution

Résumé des actions correctives qui ont permis de résoudre l'incident.

- Restauration effectuée depuis le backup du jour
- Variables d'environnement mises à jour pour pointer vers la base restaurée
- Application redémarrée

---

## 🛡️ Actions préventives (Follow-up / Prevention)

| Action                                                          | État     | Responsable | Échéance   |
|-----------------------------------------------------------------|----------|-------------|------------|
| Interdire les accès directs à la prod                           | À faire  | @tech       | 2025-06-01 |
| Ajouter une double confirmation sur les suppressions critiques  | Fait     | @tech       | 2025-05-25 |
| Mettre en place un test de restauration automatisé hebdomadaire | En cours | @tech       | 2025-06-10 |

---

## 📣 Communication

- Message interne (Mattermost, email, etc.) : [📎 lien]
- Message aux utilisateurs (email, si applicable) : [📎 lien]

---

## 📌 Annexes

- Logs de l’incident : [📎 lien]
- Screenshots / captures : [📎 lien]
- Référence à la procédure suivie : `incident-response/procedure-database-deletion.md`

---

## 🧪 REX / Leçons apprises

- Ce que nous avons bien fait
- Ce qui aurait pu être mieux
- Ce qu’on change pour la prochaine fois

---
