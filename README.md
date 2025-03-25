# Histologe.beta.gouv.fr

Une solution pour détecter et accélérer la prise en charge du “mal logement”.

## Environnement

Cette application est déployé chez Scalingo, hébergé par Outscale.

- Production: [histologe.beta.gouv.fr](https://histologe.beta.gouv.fr)

- Staging: [histologe-staging.incubateur.net](https://histologe-staging.incubateur.net)

- Demo: [histologe-demo.osc-fr1.scalingo.io](https://histologe-demo.osc-fr1.scalingo.io)

## Pré-requis

Requirements|Release
------------|--------
Docker engine (minimum)| [25.0.*](https://www.docker.com/)
Scalingo CLI (minimum) | [1.24](https://doc.scalingo.com/platform/cli/start)
AWS CLI OVH Object storage (optionnel) | [1.25](https://docs.ovh.com/fr/storage/s3/debuter-avec-s3/#utilisation-de-aws-cli)
PHP (optionnel)| [8.3.*](https://www.php.net/)
Composer (optionnel) | [2.7.*](https://getcomposer.org/download/)
Node (optionnel)| [18.*](https://nodejs.org/en/)

## Environnement technique
### Principaux outils utilisés
* [Le système de design de l'état français](https://www.systeme-de-design.gouv.fr/)
* [PHP](https://www.php.net/)
* [Symfony](https://symfony.com/)
* [Twig](https://twig.symfony.com/)
* [Vue.js](https://vuejs.org/) / [Typescript](https://www.typescriptlang.org/)

### Versions des dépendances

Service|Version
-------|-------
Nginx | 1.26.x (latest)
PHP | 8.3.x (latest)
Node.js | 18.16.x
MySQL | 8.0.x
Redis | 7.0.x (latest)

### URL(s)

Description| Lien
---------|------------- 
Plateforme histologe| [localhost:8080](http://localhost:8080)
phpMyAdmin | [localhost:8081](http://localhost:8081)
MailCatcher  | [localhost:1080](http://localhost:1080)
Wiremock  | [localhost:1082](http://localhost:1082)
Metabase  | [localhost:3007](http://localhost:3007)
Matomo  | [localhost:1083](http://localhost:1083)

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
Matomo | histologe_matomo     | **1083**
Redis| histologe_redis      | /
ClamAV| histologe_clamav     | /

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
Bouches-du-Rhône       | Resp. Territoire 13       | admin-territoire-13-01@histologe.fr | ROLE_ADMIN_TERRITORY
Ain                    | Resp. Territoire 01       | admin-territoire-01-01@histologe.fr | ROLE_ADMIN_TERRITORY
Bouches-du-Rhône       | Admin. partenaire 13       | admin-partenaire-13-01@histologe.fr | ROLE_ADMIN_PARTNER
Ain                    | Admin. partenaire 01       | admin-partenaire-01-01@histologe.fr | ROLE_ADMIN_PARTNER
Bouches-du-Rhône       | Agent Partenaire 13 | user-13-01@histologe.fr             | ROLE_USER_PARTNER
Ain                    | Agent Partenaire 01 | user-01-01@histologe.fr             | ROLE_USER_PARTNER

> Pour les mails générique partenaire, la nomenclature est la suivante: partenaire-[zip]-[index]

## API

- [Accès à l'API](./docs/API.md)

## Documentation projet

- [Documentation](https://github.com/MTES-MCT/histologe/wiki)
- [Documentation usager](https://documentation.histologe.beta.gouv.fr/)
- [Dossier d'architecture technique](./docs/ARCHITECTURE.md)
- [Document d'exploitation](./docs/EXPLOITATION.md)

## Sécurité

- [Veille sécurité](./docs/SECURITY.md)
- [Signaler une faille](./docs/SECURITY.md#signaler-une-faille)

## Contribuer

[Consulter les instructions de contributions](./docs/CONTRIBUTING.md).