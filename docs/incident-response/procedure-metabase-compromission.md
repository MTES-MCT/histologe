# 🛠️ Procédure – Compromission et reconstruction d'une instance Metabase

## 🧩 Contexte de l'incident

Une activité suspecte a été détectée sur l'instance Metabase : utilisation de comptes non légitimes, exécutions de requêtes inhabituelles ou modifications de ressources.

🎯 **Objectif :** :

- sécuriser immédiatement l'instance ;
- analyser l'étendue de l'incident ;
- restaurer l'ancienne base Metabase dans un environnement local isolé ;
- installer une nouvelle instance saine en cas de compromission avérée ou suspectée.
- migrer les collections, questions et tableaux de bord contrôlés ;
- renforcer la sécurité avant la remise en service.


---

## 🚨 Réactions immédiates

### 1. Avertir les personnes concernées

- [ ] Informer l'équipe technique et l'équipe produit.
- [ ] Identifier la date et l'heure estimées du début de l'incident.
- [ ] Conserver les éléments nécessaires à l'analyse : logs, sauvegardes et captures.

### 2. Stopper la propagation

- [ ] Stopper l'instance Metabase ou en restreindre l'accès.
- [ ] Révoquer les sessions, tokens API et comptes suspects.
- [ ] Changer les mots de passe administrateurs.
- [ ] Renouveler les secrets potentiellement exposés.
- [ ] Vérifier les variables d'environnement et les accès aux bases de données.
- [ ] Ne pas continuer à exploiter directement une base Metabase potentiellement compromise.

### 3. Vérifier les traces disponibles

- [ ] Consulter les logs de l'application et de la base PostgreSQL.
- [ ] Consulter l'activité et les événements Scalingo.
- [ ] Vérifier les utilisateurs Metabase et leurs groupes.
- [ ] Vérifier les créations et modifications récentes de questions et de tableaux de bord.
- [ ] Vérifier les exécutions de requêtes associées aux comptes suspects.

---

## 🗃️ Restauration de l'ancienne base Metabase

La base PostgreSQL Metabase contient sa configuration interne : utilisateurs, collections, questions, tableaux de bord, permissions et historique d'exécution. 
Elle est distincte de la base MySQL métier interrogée par Metabase.

- [ ] Télécharger la dernière sauvegarde PostgreSQL disponible sur scalingo
- [ ] Conserver une copie originale non modifiée.
- [ ] Décompresser l'archive sous le repertoire data du projet
- [ ] Renommer l'archive : `dump.pgsql`
- [ ] Identifier le format du fichier : `file data/dump.pgsql` 
```
dump.pgsql: PostgreSQL custom database dump - v1.14-0
```
- [ ] Examiner le contenu du dump sans le restaurer : `make metabase-db-restore-list`
- [ ] Restaurer la base dans l'instance PostgreSQL locale : `make metabase-db-restore`

---

## 🔍 Analyse de l'ancienne instance
- [ ] Construire l'instance metabase si nécéssaire `make tools-build`
- [ ] Démarrer Metabase localement sur la base restaurée `make tools-run`
- [ ] Vérifier que l'interface est accessible. `http://localhost:3007`
- [ ] Recenser les utilisateurs et leurs identifiants.
- [ ] Identifier les comptes suspects ou supprimés.
- [ ] Recenser les collections, questions, modèles et tableaux de bord actifs. `make metabase-db-report`
- [ ] Examiner les ressources créées ou modifiées pendant la période de l'incident.
```
make metabase-incident-report  METABASE_INCIDENT_DATE_FROM="2025-12-25 00:00:00"  METABASE_INCIDENT_DATE_TO="2026-01-01 00:00:00"
```

### Identifier les utilisateurs

>Créer les utilisateurs manuellement sur l'intance cible
 
- [ ] Examiner les exécutions de requêtes associées aux comptes suspects.

```
make metabase-db
```

```sql
SELECT
  id,
  email,
  first_name,
  last_name,
  is_superuser,
  is_active,
  date_joined,
  last_login
FROM core_user
ORDER BY id;
```

#### Identifier les clés API

> Créer les clés manuellement sur l'intance cible

```sql
SELECT * FROM api_key;
```

### Vue résumé par utilisateur
```sql 
SELECT
    qe.executor_id,
    u.email, 
    COUNT(*) AS nombre_executions,
    MIN(qe.started_at) AS premiere_execution,
    MAX(qe.started_at) AS derniere_execution
FROM query_execution qe
LEFT JOIN core_user u ON u.id = qe.executor_id
GROUP BY qe.executor_id, u.email
ORDER BY nombre_executions DESC;
```

- [ ] Identifier les ressources archivées ou placées dans la corbeille. (ne pas les importer)

---

## 🆕 Installation d'une nouvelle instance Metabase
- [ ] Déployer une nouvelle instance Metabase v0.63.x.
- [ ] Créer une nouvelle base PostgreSQL dédiée à Metabase.
- [ ] Configurer les comptes administrateurs légitimes.
- [ ] Recréer les utilisateurs nécessaires dans un ordre contrôlé.

### Recréation d'une nouvelle base métier MySQL
- [ ] Scalingo crée de nouveaux identifiants de connexion

### 🆕 Installation d'une nouvelle instance Metabase
- [ ] Configurer la connexion à la base MySQL métier.

### Chiffrement des paramètres de connexion
Configurer `MB_ENCRYPTION_SECRET_KEY` afin de chiffrer les informations de connexion stockées dans la base applicative Metabase.

Documentation officielle :
[Encrypting database connection details at rest](https://www.metabase.com/docs/latest/databases/encrypting-details-at-rest)

Sur une nouvelle instance, Metabase chiffre automatiquement les nouvelles informations enregistrées.
Sur une base Metabase existante, effectuer d'abord une sauvegarde, arrêter Metabase puis exécuter une seule fois la commande enable-encryption

```
MB_ENCRYPTION_SECRET_KEY="<clé>" \
java --add-opens java.base/java.nio=ALL-UNNAMED \
-jar metabase.jar enable-encryption`
```

### Protection de l'intégrité des sessions

`MB_SESSION_SECRET_KEY`

Cette clé signe les sessions stockées dans la base applicative. Elle empêche une personne disposant uniquement d'un accès à cette base de fabriquer une session Metabase valide.
- [ ] Générer une clé aléatoire. `openssl rand -base64 32`
- [ ] Ajouter `MB_SESSION_SECRET_KEY` aux variables d'environnement.
- [ ] Utiliser une valeur différente de MB_ENCRYPTION_SECRET_KEY.
  L'ajout ou la modification de `MB_SESSION_SECRET_KEY` invalide toutes les sessions actives. Les utilisateurs devront se reconnecter.

---

## 🔁 Migration des ressources

La migration est réalisée avec :

[Finverity/metabase-migration-toolkit](https://github.com/Finverity/metabase-migration-toolkit)

L'outil utilise l'API Metabase pour exporter les ressources en JSON, puis les importer dans la nouvelle instance.

Il remappe notamment les identifiants des collections, bases, tables, champs, questions et tableaux de bord.

### Contributions au projet

Pour bénéficier de l'ensemble des correctifs, il faut partir de la branche associée à la [pull request #1 – Remappage de `source-field`](https://github.com/sfinx13/metabase-migration-toolkit/pull/1).

Cette branche comprend également le correctif de la [pull request #83 – Prise en charge de `joins[].conditions`](https://github.com/Finverity/metabase-migration-toolkit/pull/83).

Elle apporte donc :

- le remappage des champs utilisés dans `joins[].conditions` avec Metabase v0.63 ;
- le remappage des identifiants présents dans `source-field` pour les filtres reposant sur des relations implicites entre les tables.


### Préparation

- [ ] Configurer les URL et les moyens d'authentification des instances source et cible. (Voir [README](https://github.com/Finverity/metabase-migration-toolkit/blob/main/README.md))
- [ ] Préparer le fichier `db_map.json`.
- [ ] Exporter une collection à la fois.
- [ ] Commencer par les collections contenant des questions utilisées comme sources par d'autres cartes.
- [ ] Examiner le fichier `manifest.json`.
- [ ] Vérifier les descriptions vides et les doublons de noms.
- [ ] Lancer un import de contrôle avec l'option `--dry-run`.
- [ ] Examiner les logs et le rapport d'import avant de poursuivre.
- [ ] Réaliser l'import définitif.
- [ ] Importer ensuite les collections dépendantes.

### Vérifications avant export
Ces vérifications permettent d'identifier deux causes fréquentes d'échec ou de comportement imprévisible pendant l'import.
- Noms en doublon dans une même collection : l'outil identifie les ressources cibles à partir de leur nom et de leur collection. Si deux questions portent le même nom dans la même collection, il ne peut pas déterminer de façon fiable laquelle doit être créée ou mise à jour. L'import peut être bloqué, ignorer une carte ou écraser la mauvaise ressource selon la stratégie de conflit.

   - Pour les questions, modèles et métriques non archivés :
      ``` 
      SELECT collection_id, name, COUNT(*) AS nombre, ARRAY_AGG(id ORDER BY id) AS ids
      FROM report_card
      WHERE archived = FALSE
      GROUP BY collection_id, name
      HAVING COUNT(*) > 1
      ORDER BY collection_id, name; 
      ```

  - Pour les tableaux de bord non archivés :
      ``` 
      SELECT collection_id, name, COUNT(*) AS nombre, ARRAY_AGG(id ORDER BY id) AS ids
      FROM report_dashboard
      WHERE archived = FALSE
      GROUP BY collection_id, name
      HAVING COUNT(*) > 1
      ORDER BY collection_id, name;
      ```

La vérification se fait par collection, car deux ressources peuvent légitimement avoir le même nom lorsqu'elles appartiennent à des collections différentes.


### Exemple d'export d'une collection

L'option `--root-collections` correspond à l'identifiant de la collection source. Dans cet exemple, la collection d'un utilisateur porte l'identifiant `8`.

```shell
metabase-export \
  --export-dir ./metabase_export_collection_8 \
  --include-dashboards \
  --root-collections "8" \
  --log-level INFO
 ```
L'export produit notamment :
- un fichier manifest.json ;
- l'arborescence des collections ;
- les questions exportées au format JSON ;
- les tableaux de bord exportés au format JSON.


### Vérification après export

Descriptions vides `("")` : l'API Metabase attend généralement une description renseignée ou la valeur null. 

Selon la ressource et la version de Metabase, une chaîne vide peut provoquer une erreur de validation. 

Il faut alors la remplacer par null avant l'import ou éditer une description

`rg -n '"description"\s*:\s*""' ./metabase_export_collection_8`

``` 
BEGIN;

UPDATE report_card
SET description = NULL
WHERE description IS NOT NULL
AND BTRIM(description) = '';

UPDATE report_dashboard
SET description = NULL
WHERE description IS NOT NULL
AND BTRIM(description) = '';

UPDATE collection
SET description = NULL
WHERE description IS NOT NULL
AND BTRIM(description) = '';

COMMIT;
```

Après la correction 
- rejouer la requête de vérification. Elle ne doit retourner aucune ligne. 
- faire ensuite un nouvel export afin que les fichiers JSON contiennent bien "description": null.


### Simulation de l'import
Exécuter d'abord l'import avec l'option `--dry-run`. Cette commande vérifie le contenu de l'export et prépare le plan d'import sans créer ni modifier de ressources sur l'instance cible.
``` 
metabase-import \
--export-dir ./metabase_export_collection_8 \
--db-map ./db_map.json \
--metabase-version v63 \
--conflict overwrite \
--dry-run \
--log-level DEBUG \
2>&1 | tee import-collection-8.log
```

- [ ] Vérifier l'absence d'erreurs dans import-collection-8.log. `rg -n -i 'error|failed|failure|exception|traceback|retryerror' import-collection-8.log`
- [ ] Vérifier les conflits et les doublons détectés.
- [ ] Vérifier que les collections ciblées correspondent au résultat attendu.

### Import définitif
Après validation de la simulation, retirer l'option `--dry-run` :
``` 
metabase-import \
--export-dir ./metabase_export_collection_8 \
--db-map ./db_map.json \
--metabase-version v63 \
--conflict overwrite \
--log-level DEBUG \
2>&1 | tee import-collection-8.log
```

Exécuter la requête .`docker/metabase/resources-report.sql` sur l'instance cible afin de comparer les résultats et de vérifier que toutes les ressources ont bien été importées.

---

## 🔐 Protection de l'instance
L'accès à Metabase est protégé avec :
[oauth2-proxy/oauth2-proxy](https://github.com/oauth2-proxy/oauth2-proxy)

[Documentation : Ajouter l'authentification OAuth2 (oauth2-proxy) sur Metabase Scalingo](https://pad.numerique.gouv.fr/s/EHDNjZ2g2)

[Documentation: Proxy d'authentification] https://doc.incubateur.net/communaute/gerer-son-produit/aide-a-la-mise-en-application-des-standards/securite/securite-proxy-dauthentification

- [ ] Activer oauth2-proxy devant l'application avec github comme fournisseur d'identité [oauth2-proxy/oauth2-proxy](https://github.com/oauth2-proxy/oauth2-proxy)
- [ ] Vérifier que l'administration et les API privées exigent une authentification.
  - `curl -I https://signal-logement-metabase.osc-fr1.scalingo.io/api/user/current`
  - `curl -I https://signal-logement-metabase.osc-fr1.scalingo.io/admin/settings`
  - `curl -I https://signal-logement-metabase.osc-fr1.scalingo.io`
- [ ] Configurer `OAUTH2_PROXY_SKIP_AUTH_ROUTES` qui permet d'exclure certaines routes de l'authentification. [Ajouter l'authentification OAuth2 (oauth2-proxy) sur Metabase Scalingo](https://pad.numerique.gouv.fr/s/EHDNjZ2g2)
- [ ] Autoriser uniquement les routes nécessaires au fonctionnement des tableaux de bord publics. [Ajouter l'authentification OAuth2 (oauth2-proxy) sur Metabase Scalingo](https://pad.numerique.gouv.fr/s/EHDNjZ2g2)
- [ ] Tester les routes publiques en navigation privée.


## ⚙️ Paramétrage
- [ ] **Intégration** : configurer les paramètres d’intégration avec la clé dédiée.
- [ ] **Personnes et autorisations** : vérifier les groupes et les permissions attribuées aux utilisateurs.
- [ ] **Performance** : configurer la mise en cache de la base de données avec les mêmes paramètres que sur l’instance source.
- [ ] **Base de données** : lancer une synchronisation du schéma.
- [ ] **Base de données** : réanalyser les valeurs des champs.
