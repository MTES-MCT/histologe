# Dossier d'architecture technique

## Introduction et Contexte

### Objectif du document
Ce document décrit l'environnement technique dans lequel est exploitée la plateforme Signal Logement.

### Contexte du projet
Signal Logement est un système d'information qui permet de détecter et accélérer la prise en charge du mal-logement.

Cette plateforme fournit 
- un guichet unique de signalement à des usagers en situation de mal-logement,
- un back-office pour permettre aux agents de communiquer plus facilement et résoudre plus rapidement les signalements qu'ils reçoivent.

## Description de l'Architecture Technique

### Architecture globale
![Schéma de l'architecture Signal Logement](/docs/assets/archi-histologe.jpg "Architecture Signal Logement")

### Diagrammes de déploiement

#### Diagramme de commit
![Diagramme de commit](/docs/assets/diagramme-commit.svg "Diagramme de commit")

#### Diagramme de déploiement
![Diagramme de déploiement](/docs/assets/diagramme-deploiement.svg "Diagramme de déploiement")

#### Diagramme de CI
![Diagramme de CI](/docs/assets/diagramme-ci.svg "Diagramme de CI")

## Composants Techniques

### Serveurs
Une instance Scalingo composée de trois conteneurs permet d'exploiter la plateforme :
- une webapp
- un worker
- un conteneur gérant les tâches asynchrones

Voir aussi la partie Stockage.

### Réseau
L'ensemble du système communique avec le protocole HTTPS.

Les DNS sont gérés via le service Alwaysdata, qui redirige vers Scalingo.

### Stockage
Les documents sont stockés sur une infrastructure cloud OVH de type "Object Storage".

### Base de données
Le système de gestion utilisé est MySQL.

Une seule base de données gère l'ensemble de la plateforme.

La base de données est sauvegardée automatiquement, chaque jour, par l'hébergeur.
Elle est sauvegardée de manière déconnectée sur un disque dur externe, chaque semaine.

#### Schéma des relations de la base utilisateurs

![BDD Utilisateurs](/docs/assets/schema-bdd-user.png "BDD Utilisateurs")

#### Schéma des relations des affectations de signalements

![BDD Affectations](/docs/assets/schema-bdd-affectation.png "BDD Affectations")

#### Schéma des relations des désordres de signalements (v1)

![BDD Désordres v1](/docs/assets/schema-bdd-signalement-1-desordres.png "BDD Désordres v1")

#### Schéma des relations des désordres de signalements (v2)

![BDD Désordres v2](/docs/assets/schema-bdd-signalement-2-desordres.png "BDD Désordres v2")

#### Schéma des relations autres des signalements

![BDD Autres relations](/docs/assets/schema-bdd-signalement-autres.png "BDD Autres relations")

#### Schéma des relations des notifications

![BDD Notifications](/docs/assets/schema-bdd-notification.png "BDD Notifications")

### Base de données de sessions
Redis est le système utilisé pour la gestion des sessions et du cache du SI.

Il permet notamment l'utilisation de plusieurs conteneurs Scalingo.

### Middleware

#### Brevo
Les communications par e-mails sont envoyés à travers l'API de Brevo.

#### Matomo
L'analyse des visites et parcours sur le site est faite grâce à ce service.

#### DGS - Esabora
Nous communiquons avec 2 types d'instances Esabora (SI-SH et SCHS) pour leur faire parvenir des dossiers.

#### Zapier
Nous envoyons des requêtes vers Zapier pour communiquer avec OILHI, une autre start-up d'Etat.

#### Sentry
Sentry, hébergée par les services nationaux, nous permet :
- de retrouver les erreurs listées par le service
- d'analyser la performance du SI

#### BAN
Nous faisons des appels à la Base d'Adresses Nationale pour faciliter la saisie et l'édition de signalements.

#### Open Street Map
Nous géolocalisons les signalements pour faciliter le travail des agents à travers une cartographie ou une simple carte.

#### Koumoul Open Data
Nous indique les informations de DPE des logements.

## Sécurité

### Mesures de sécurité
L'ensemble du site utilise le protocole HTTPS.

Notre hébergeur [Scalingo](https://scalingo.com/fr/certification-iso-27001) est certifié ISO 27001.

Les accès à la plateforme sont individuels. Les droits d'accès sont progressifs selon les trois profils : Administrateur, Responsable territoire et Agent.

Les accès aux services tiers sont individualisés aussi dès que possible : chaque personne de l'équipe possède son accès, pour permettre une traçabilité des actions.

TODO : décrire les systèmes de détection d'intrusion (Voir : https://doc.scalingo.com/platform/app/datadog ; https://doc.scalingo.com/platform/getting-started/getting-started-with-modsecurity#enabling-modsecurity ; https://en.wikipedia.org/wiki/OSSEC ; https://fr.wikipedia.org/wiki/Snort)

### Conformité
Voir notre [politique de confidentialité](https://signal-logement.beta.gouv.fr/politique-de-confidentialite).

## Environnements

### Développement, Test, Production
#### Environnement local
- Plateforme Signal Logement| [localhost:8080](http://localhost:8080)
- phpMyAdmin | [localhost:8081](http://localhost:8081)
- MailCatcher  | [localhost:1080](http://localhost:1080)
- Wiremock  | [localhost:1082](http://localhost:1082)
- Metabase  | [localhost:3007](http://localhost:3007)
- Matomo  | [localhost:1083](http://localhost:1083)

#### Test et démo
- Staging : [histologe-staging.incubateur.net](https://histologe-staging.incubateur.net)
- Demo : [histologe-demo.osc-fr1.scalingo.io](https://histologe-demo.osc-fr1.scalingo.io)

#### Production
[signal-logement.beta.gouv.fr](https://signal-logement.beta.gouv.fr)

### Gestion des versions
Nous utilisons Git pour la gestion de versions du code source. Chaque commit doit contenir l'identifiant d'un ticket correspondant à la fonctionnalité développée ou à la correction effectuée.

Chaque fonctionnalité fait l'objet d'une branche particulière, puis d'une `Pull Request` relue par au moins un pair.

Lorsqu'une PR est validée et fusionnée, elle rejoint la branche `develop` qui déploie automatiquement sur l'environnement `Staging`.

Nous utilisons le gestionnaire de tags de Git afin de versionner notre code et les fonctionnalités qui sont déployées.

Lorsque la branche `develop` fusionne avec la branche `main`, le déploiement se fait automatiquement sur l'environnement de `Production`.

Un tunnel d'intégration continue fait des vérifications de qualité de code (SonarCloud) et de tests automatisés. Il est exécuté à chaque commit sur une PR et à chaque fusion de branche. Le déploiement n'est fait qu'une fois ces vérifications validées.

Au niveau des bases de données, nous utilisons le système de migrations prévu par Symfony qui permet de faire des retours en arrière en cas de besoin.

Le déploiement consiste à la copie des fichiers, à l'installation des composants nécessaires, à la transformation de la base de données via des migrations.

## Performance et Scalabilité

### Performance
Le suivi des performances est fait en temps réel grâce à [Dashlord](http://dashlord.mte.incubateur.net/url/histologe-beta-gouv-fr/) et au tableau de bord Scalingo.

Nous assurons une disponibilité supérieure à 99,95 % et un temps de réponse inférieur à 400ms.

### Scalabilité
L'utilisation de conteneurs au sein de Scalingo permet de faciliter la diminution ou l'augmentation des capacités de traitement au besoin.

Nous utilisons Reddit pour le partage d'informations entre les différents conteneurs.

## Continuité et Reprise d'Activité

Voir le document de [Procédure de gestion des incidents de sécurité](https://github.com/MTES-MCT/histologe/wiki/Gestion-des-incidents-de-s%C3%A9curit%C3%A9)

## Gestion et Maintenance

### Outils de gestion
Logiciels et outils utilisés pour la surveillance, la gestion, et la maintenance du système.

#### Sentry
Reçoit les erreurs générées lors de l'utilisation de la plateforme par les usagers.

#### Analyse du code à chaque commit
PhpStan, php cs-fix, es-lint, php-unit

#### SonarCloud
Analyse la qualité du code lors de l'envoi de chaque commit sur GitHub.

### Procédures de maintenance
Il est possible d'activer un mode maintenance sur la plateforme.

Il peut être activé sur une fonctionnalité cruciale est mise en ligne ou si un problème a été détecté est en cours d'investigation.
