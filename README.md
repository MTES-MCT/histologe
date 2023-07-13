# Histologe.beta.gouv.fr

Une solution pour détecter et accélérer la prise en charge du “mal logement”.

## Environnement

Cette application est déployé chez Scalingo, hébergé par Outscale.

- Production: [histologe.beta.gouv.fr](https://histologe.beta.gouv.fr)

- Staging: [histologe-staging.incubateur.net](https://histologe-staging.incubateur.net)

## Pré-requis

Requirements|Release
------------|--------
Docker engine (minimum)| [20.10.17](https://www.docker.com/)
Scalingo CLI (minimum) | [1.24](https://doc.scalingo.com/platform/cli/start)
AWS CLI OVH Object storage (optionnel) | [1.25](https://docs.ovh.com/fr/storage/s3/debuter-avec-s3/#utilisation-de-aws-cli)
PHP (optionnel)| [8.1.*](https://www.php.net/)
Composer (optionnel) | [2.4.*](https://getcomposer.org/download/)
Node (optionnel)| [16.*](https://nodejs.org/en/)

## Environnement technique

### Versions des dépendances

Service|Version
-------|-------
Nginx | 1.20.2
PHP | 8.1.x (latest)
MySQL | 5.7.38
Redis | 7.0.x (latest)

### URL(s)

Description| Lien
---------|------------- 
Plateforme histologe| [localhost:8080](http://localhost:8080)
phpMyAdmin | [localhost:8081](http://localhost:8081)
MailCatcher  | [localhost:1080](http://localhost:1080)
Wiremock  | [localhost:1082](http://localhost:1082)
Metabase  | [localhost:3007](http://localhost:3007)

### Hôtes des environnements et ports

Merci de vérifier que ces ports ne soient pas utilisés sur votre poste local

Service| Hostname             |Port number
-------|----------------------|-----------
Nginx| histologe_nginx      | **8080**
php-fpm| histologe_phpfpm     |**9000**
php-worker| histologe_phpworker  |**8089**
MySQL| histologe_mysql      |**3307**
PhpMyAdmin | histologe_phpmyadmin | **8081**
Mailcatcher| histologe_mailer     | **1025** et **1080**
Wiremock| histologe_wiremock   | **8082**
Metabase| histologe_metabase   | **3007**
Redis| histologe_redis      | /

## Installation

### Commandes

Un [Makefile](Makefile) est disponible, qui sert de point d’entrée aux différents outils :

```
$ make help
```

### Lancement

1. Executer la commande

La commande permet d'installer l'environnement de developpement avec un jeu de données

```
$ make build
```

2. Configurer les variables d'environnements du service object storage S3 d'OVH Cloud

> Se rapprocher de l'équipe afin de vous fournir les accès au bucket de dev

```
# .env.local
### object storage S3 ###
S3_ENDPOINT=
S3_KEY=
S3_SECRET=
S3_BUCKET=
S3_URL_BUCKET=
### object storage S3 ###
```

3. Se rendre sur http://localhost:8080

> Pour tous les utilisateurs, le mot de passe est `histologe`

Territoire             | Partenaire                | Email                               | Rôle       
-----------------------|---------------------------|-------------------------------------|----------------------
N/A                    | Admin Histologe           | admin-01@histologe.fr               | ROLE_ADMIN 
Bouches-du-Rhône       | Admin Territoire 13       | admin-territoire-13-01@histologe.fr | ROLE_ADMIN_TERRITORY
Ain                    | Admin Territoire 01       | admin-territoire-01-01@histologe.fr | ROLE_ADMIN_TERRITORY
Bouches-du-Rhône       | Admin Partenaire 13       | admin-partenaire-13-01@histologe.fr | ROLE_ADMIN_PARTNER
Ain                    | Admin Partenaire 01       | admin-partenaire-01-01@histologe.fr | ROLE_ADMIN_PARTNER
Bouches-du-Rhône       | Utilisateur Partenaire 13 | user-13-01@histologe.fr             | ROLE_USER_PARTNER
Ain                    | Utilisateur Partenaire 01 | user-01-01@histologe.fr             | ROLE_USER_PARTNER

> Pour les mails générique partenaire, la nomenclature est la suivante: partenaire-[zip]-[index]

4. Vous pouvez ajouter vos e-mails:

> En tant qu'administrateur

```
$ php bin/console app:add-user ROLE_ADMIN john.doe.1@histologe.fr John Doe
```

> En tant qu'administrateur territoire

```
$ php bin/console app:add-user ROLE_ADMIN_TERRITORY joe.doe.2@histologe.fr John Doe Marseille 13
```

> En tant que partenaire
> 
```
$ php bin/console app:add-user ROLE_USER_PARTNER joe.doe.3@histologe.fr John Doe Marseille 13
```

Une activation de compte sera nécéssaire

## Accès à Metabase

- Copier le fichier de base de données dans le dossier ```metabase-data/metabase.db/```
- Login : ```admin@histologe.fr```
- Mot de passe : ```histologe2023```

## Documentaton projet

[Consulter la documentation](https://github.com/MTES-MCT/histologe/wiki)
