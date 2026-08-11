# 📝 Postmortem – Contrôle d'accès insuffisant sur l'archivage d'un brouillon

> Une route anonyme permettait d'archiver un brouillon de signalement à partir de l'adresse postale du logement et de 
> l'adresse e-mail du déclarant, sans exiger l'UUID associé au brouillon.

---

## 📅 Date & Heure de l'incident

- **Date de début :** 2026-08-03
- **Date de fin :** 2026-08-07
- **Durée totale :** ~4 jours entre le signalement et la remédiation complète

---

## 👥 Participants

| Rôle                   | Nom                                |
|------------------------|------------------------------------|
| Responsable d'incident | Saidi AHAMADA                      |
| Support sécurité       | Renaud Durand                      |
| Observateurs           | Developpeurs Signal Logement |

---

## 📝 Résumé de l'incident

Une vulnérabilité de contrôle d'accès a été signalée via YesWeHack sur la route anonyme suivante :

`POST /signalement-draft/archive`

Cette route permet d'archiver un brouillon de signalement durant le parcours de dépôt de signalement.

Pour identifier le brouillon à archiver, le service utilisait initialement deux informations :
- l'adresse postale du logement
- l'adresse e-mail du déclarant

Une personne disposant de ces deux données pouvait donc tenter d'archiver un brouillon, sans avoir à fournir l'UUID associé au brouillon.

Le correctif consiste à identifier et autoriser l'archivage uniquement à partir de l'UUID du brouillon.

---

## 💥 Impact

- Nombre d'utilisateurs impactés : Non déterminé
- Fonctionnalités affectées :
    - archivage d'un brouillon de signalement
- Perte de données : Non
- SLA respecté : Oui

Impact identifié :
- archivage non autorisé d'un brouillon

Aucun impact identifié sur :
- l'intégrité des données
- la disponibilité du service
- les comptes utilisateurs.

---

## 🔍 Ligne du temps (Timeline)

| Date       | Événement                                           |
| ---------- |-----------------------------------------------------|
| 2026-08-03 | Signalement reçu via YesWeHack                      |
| 2026-08-04 | Ouverture de la pull request contenant le correctif |
| 2026-08-05 | Prise en compte des retours de revue de code        |
| 2026-08-07 | Déploiement du correctif sur `main`                 |

---

## 🔍 Causes racines (Root Cause Analysis)

- Pourquoi la vulnérabilité existait-elle ?
    - La route d'archivage identifiait le brouillon à partir de l'adresse postale et de l'adresse e-mail.

- Pourquoi l'UUID n'était-il pas utilisé ?
    - Le DTO SignalementDraftRequest et la logique d'archivage existante reposaient sur l'ensemble des données du formulaire dont l'UUID mais qui n'était pas utilisé. L'action d'archivage ne disposait pas d'un DTO dédié exigeant l'UUID du brouillon.

- Pourquoi cela n'a pas été détecté plus tôt ?
    - Le point n'avait pas été identifié lors des précédentes revues de code

---

## 🛠 Résolution

Les actions suivantes ont été réalisées :

- création d'un DTO dédié à l'archivage contenant uniquement l'UUID du brouillon ;
- recherche du brouillon à partir de son UUID ;

La requête attendue est désormais de la forme suivante :

```
{
"uuid": "f03cffa5-d29b-46fe-9dab-e898bde7d53a"
}
```

---

## ✅ Actions préventives (Follow-up / Prevention)

| Action                                                                                              | État        | Responsable   | Échéance |
|-----------------------------------------------------------------------------------------------------|-------------|---------------|----------|
| Vérifier le contrôle d'accès des opérations sensibles exposées par des potentielles routes anonymes | A planifier | Saidi Ahamada | -        |

---

## 📣 Communication

- Message interne : Email/Mattermost
- Rapport YesWeHack
---

## 📌 Annexes

- Lien de la PR : https://github.com/MTES-MCT/histologe/pull/6207

---

## 🧪 REX / Leçons apprises

### Ce que nous avons bien fait

- Analyse rapide du périmètre réel de la vulnérabilité
- réutilisation de l'UUID déjà associé au brouillon
- limitation de la payload aux données strictement nécessaires ;

### Ce qui aurait pu être mieux

- Les données personnelles ne devaient pas être considérées comme une preuve d'autorisation ;

### Ce qu'on change pour la prochaine fois

- Toute route anonyme modifiant une ressource devra exiger une preuve de possession non devinable, telle qu'un UUID aléatoire,
  les données personnelles ne seront plus utilisées seules. 
