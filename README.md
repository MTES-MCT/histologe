# Histologe.beta.gouv.fr

## Pré-requis

Requirements|Release
------------|--------
Docker client|
Version (minimum) | 20.10.12
API Version (minimum) | 1.14

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

Insérer un jeu de données (export sql) existant dans le repertoire data (voir avec l'équipe).

### Usage
```
make help
```

### Lancement
```
cp .env.sample .env
make build
```

### Accès

- Site internet: http://localhost:8080
- BackOffice: http://localhost:8080/connexion

Pour tous les utilisateurs, le mot de passe est `histologe`

Territoire             | Partenaire         | Email                            | Rôle       
-----------------------|--------------------|----------------------------------|----------------------
N/A                    | Admin Histologe    | admin-01@histologe.fr            | ROLE_ADMIN 
Bouches-du-Rhône       | Admin Histologe 13 | admin-territoire-13@histologe.fr | ROLE_ADMIN_TERRITORY
Ain                    | Admin Histologe 01 | admin-territoire-01@histologe.fr | ROLE_ADMIN_TERRITORY
Bouches-du-Rhône       | Partenaire 13      | partenaire-13-01@histologe.fr    | ROLE_USER_PARTNER
Ain                    | Partenaire 01      | partenaire-01-01@histologe.fr    | ROLE_USER_PARTNER




### Tests

#### Tests e2e
```
npx cypress open
```

