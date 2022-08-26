# Histologe.beta.gouv.fr

## Pré-requis

Requirements|Release
------------|--------
Docker client|
Version (minimum) | 20.10.17
API Version (minimum) | 1.41

## Versions des dépendances
Service|Version
-------|-------
Nginx | 1.20.2
PHP | 8.0.x (latest)
MySQL | 5.7.38

## Clone du projet

### HTTP
```bash
git clone https://github.com/MTES-MCT/histologe
```

### SSH
```
git clone git@github.com:MTES-MCT/histologe.git
```

[Vérification des clés SSH existantes](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/checking-for-existing-ssh-keys)

[Génération d'une nouvelle clé SSH](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent)

## Environnement

Description| Lien
---------|------------- 
Plateforme histologe| [localhost:8080](http://localhost:8080)
phpMyAdmin | [localhost:8081](http://localhost:8081)
MailCatcher  | [localhost:1080](http://localhost:1080)

### Hôtes des environnements et ports

Merci de vérifier que ces ports ne soient pas utilisés sur votre poste local

Service|Hostname|Port number
-------|--------|-----------
Nginx|  histologe_nginx| **8080**
PHP-FPM| histologe_phpfpm|**9000**
MySQL| histologe_mysql|**3307**
PhpMyAdmin |   histologe_phpmyadmin | **8081**
Mailcatcher|   histologe_mailer| **1025** et **1080**

## Installation

### Commandes

Un [Makefile](Makefile) est disponible, qui sert de point d’entrée aux différents outils :

```
$ make help

build                          Install local environement
run                            Start containers
down                           Shutdown containers
sh                             Log to phpfpm container
mysql                          Log to mysql container
logs                           Show container logs
composer                       Install composer dependencies
create-db                      Create database
drop-db                        Drop database
load-data                      Load database from dump
load-migrations                Play migrations
load-fixtures                  Load database from fixtures
create-db-test                 Create test database
test                           Run all tests
test-coverage                  Generate phpunit coverage report in html
e2e                            Run E2E tests
stan                           Run PHPStan
cs-check                       Check source code with PHP-CS-Fixer
cs-fix                         Fix source code with PHP-CS-Fixer
```

### Lancement
```
make build
```

#### Compilation des fichiers Vue.js en cours de développement
```
npm install
npm run watch
```

### Accès

- En local sur http://localhost:8080

Pour tous les utilisateurs, le mot de passe est `histologe`

Territoire             | Partenaire         | Email                            | Rôle       
-----------------------|--------------------|----------------------------------|----------------------
N/A                    | Admin Histologe    | admin-01@histologe.fr            | ROLE_ADMIN 
Bouches-du-Rhône       | Admin Histologe 13 | admin-territoire-13@histologe.fr | ROLE_ADMIN_TERRITORY
Ain                    | Admin Histologe 01 | admin-territoire-01@histologe.fr | ROLE_ADMIN_TERRITORY
Bouches-du-Rhône       | Partenaire 13      | partenaire-13-01@histologe.fr    | ROLE_USER_PARTNER
Ain                    | Partenaire 01      | partenaire-01-01@histologe.fr    | ROLE_USER_PARTNER

