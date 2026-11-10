# Environnement PostgreSQL (expérimental)

Dans le cadre de l'étude de migration vers PostgreSQL, un environnement PostgreSQL + Adminer est disponible en local aux côtés de MySQL :

- **PostgreSQL 18.6 (PostGIS)** : port local `5433` (accessible depuis les conteneurs sur `signal_logement_postgres:5432`)
- **Adminer** : accessible sur [http://localhost:8083](http://localhost:8083) (sélectionner le moteur *PostgreSQL*, serveur *signal_logement_postgres*, utilisateur *signal_logement*, mot de passe *signal_logement*, base *signal_logement_db*)

## Lancer l'environnement Docker avec PostgreSQL

Pour démarrer l'environnement minimal avec PostgreSQL et Adminer (sans démarrer MySQL ni phpMyAdmin) :

```bash
$ make run-pg
# ou make run-postgres
```

Pour démarrer l'environnement avec MySQL par défaut :
- `$ make run-min` : environnement minimal avec MySQL et phpMyAdmin (sans PostgreSQL)
- `$ make run` : environnement complet avec MySQL, phpMyAdmin, workers et ClamAV

## Basculer l'application sur PostgreSQL

Pour faire fonctionner l'application sur PostgreSQL, ajouter ou modifier la variable `DATABASE_URL` dans `.env.local` :

```dotenv
DATABASE_URL="postgresql://signal_logement:signal_logement@signal_logement_postgres:5432/signal_logement_db?serverVersion=18.6&charset=utf8"
```

Pour revenir sur MySQL (moteur par défaut) :
```dotenv
DATABASE_URL="mysql://signal_logement:signal_logement@signal_logement_mysql:3306/signal_logement_db?serverVersion=8.4.8&charset=utf8mb4"
```

## Réinitialiser la base PostgreSQL

Pour réinitialiser le schéma et recharger les fixtures sur PostgreSQL sans toucher à la base MySQL :

```bash
$ make create-db-postgres
```
