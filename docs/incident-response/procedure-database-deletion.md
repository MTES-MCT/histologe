# 🛠️ Procédure – Suppression accidentelle de la base de données

## 🧩 Contexte de l’incident

Une base de données (production ou critique) a été supprimée par erreur à la suite d’une action humaine ou d’un script mal configuré.  
Objectif de cette fiche : restaurer rapidement le service et limiter l’impact sur les utilisateurs.

---

## 🚨 Réactions immédiates

1. **Avertir les personnes concernées**
    - [ ] Informer l’équipe produit.

2. **Stopper la propagation**
   - [ ] Vérifier que l'incident est terminé (pas de script encore actif).
   - [ ] Basculer l'application en **mode maintenance**.
   - [ ] Vérifier que l'incident ne provient pas d'une attaque
        - [ ] Sentry
        - [ ] Scalingo - Onglet activité
        - [ ] Scalingo - Onglet logs
        - [ ] Scalingo - Base de données - Onglet logs
   - [ ] Révoquer immédiatement les accès du compte ayant effectué des opérations inappropriées. 😃
   - [ ] Auditer la configuration de la base de données : 
        - Onglet Accès internet via le tableau de bord (La base de données ne doit pas être accessible sur internet)
        - Onglet Configuration via le tableau de bord, les configurations suivantes doivent être activées : 
           - `ERROR_FOR_DIVISION_BY_ZERO`
           - `NO_ENGINE_SUBSTITUTION`
           - `NO_ZERO_DATE`
           - `NO_ZERO_IN_DATE`
           - `ONLY_FULL_GROUP_BY`
           - `STRICT_TRANS_TABLES`
        -  Onglet Utilisateurs : 
           - Supprimer les utilisateurs non légitimes et vérifier les permissions de chaque utilisateur.
        - Onglet bases de données :
           - Supprimer les bases de données inutiles ou obsolètes après validation.


---

## 🗃️ Vérification des sauvegardes

- [ ] Rechercher le **dernier backup disponible** :
   - `Tableau de bord Scalingo > Addons MySQL> Tableau de bord MySQL > Backups`
   - S'assurer qu’il est **complet et lisible**.
- [ ] Télécharger l'archive et préparer l'environnement afin de tester la sauvegarde en locale

    ```shell 
     tar -xvzf nom_sauveragde.tar.gz data/
    ```

    ```shell 
     mv data/nom_sauvegarde.sql data/dump.sql
    ```  
    
    ```shell
     make load-data
    ```
- [ ] Tester le fichier afin de s'assurer qu'il est complet et lisible.
   - Vérifier l'horodatage des derniers enregistrements des tables suivantes : 
      - signalement
      - suivi
      - notification
      - job_event
      - history_entry
- [ ] Naviguer sur la plateforme en locale afin de vérifier son bon fonctionnement.
---

## 🔁 Étapes de restauration

Aprés avoir identifié la base de données cible et vérifier l'existence de sa variable d'environnement `SCALINGO_MYSQL_URL`

1. **Créer un tunnel SSH sur la base cible**
```shell
 scalingo -a [nom-application] db-tunnel SCALINGO_MYSQL_URL
```

2. **Restaurer la base**
```shell
 mysql -h 127.0.0.1 -P 10000 -u <nom_utilisateur> <nom_base_de_donnees> < dump.sql
```

Ensuite, vous serez invité à entrer le mot de passe comme ceci :
```shell
 Enter password
```

- [ ] Confirmer que les tables, index, données sont bien présents
```shell
 -- Lister les tables
 SHOW TABLES; 

 -- Vérifier des colonnes et index pour une table
SHOW COLUMNS FROM signalement;
SHOW INDEX FROM signalement;

 -- Vérifier les données d\'une table
SELECT id, created_at FROM signalement ORDER BY created_at DESC LIMIT 10;
SELECT id, created_at FROM notification ORDER BY created_at DESC LIMIT 10;
SELECT id, created_at FROM job_event ORDER BY created_at DESC LIMIT 10;
SELECT id, created_at FROM suivi ORDER BY created_at DESC LIMIT 10;
SELECT id, created_at FROM history_entry ORDER BY created_at DESC LIMIT 10;

--- Quelques vérifications sur la volumétrie
SELECT 'signalement' AS table_name, COUNT(*) AS count FROM signalement
UNION ALL
SELECT 'partner' AS table_name, COUNT(*) AS count FROM partner
UNION ALL
SELECT 'user' AS table_name, COUNT(*) AS count FROM user;
```

## ⚠️ Attention – Risque de perte de données
Le backup utilisé correspond généralement à la dernière sauvegarde complète effectuée automatiquement par l'hébergeur **backup effectué chaque nuit**.

👉 Par conséquent, toute donnée enregistrée **entre le moment du dernier backup et l’incident** (ex. : soumissions d’usagers, suivis, historiques) **est potentiellement perdue**.

- Estimez la fenêtre de perte en comparant l'heure du dernier `created_at` présent dans les tables restaurées avec l'heure réelle de l’incident.
- Communiquez cette information de manière transparente aux parties concernées.
- Mentionnez-la dans le **postmortem** si la perte de données est avérée.


## ✅ Vérifications Post-Restauration
- [ ] Tests applicatifs (connexion, lecture/écriture)
- [ ] Vérifier que les utilisateurs peuvent à nouveau se connecter

## 📣 Communication
- [ ] Informer les équipes internes de la restauration 
- [ ] Préparer un mail si les utilisateurs ont été affectés

## 🧠 Analyse Post-Incident
- [ ] Consigner l’incident dans un Postmortem (template : [postmortem-template.md](postmortem-template.md))
