# Histologe.beta.gouv.fr

## Pré-requis

Requirements|Release
------------|--------
Docker client|
Version| 20.10.12
API Version | 1.14

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

## Installation

Insérer un jeu de données (export sql) existant dans le repertoire data (voir avec l'équipe).

### Execution
```
cp .env.sample .env
make build
```