# Signal Logement - Guide pour Claude Code

## Vue d'ensemble du projet

**Signal Logement** (anciennement Histologe) est une solution permettant de détecter et accélérer la prise en charge du "mal-logement". C'est un projet de beta.gouv.fr.

### Stack technique

**Backend (PHP/Symfony)**
- **Framework** : Symfony 7.4
- **PHP** : 8.4
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Templating** : Twig
- **Architecture** : MVC avec services, repositories, entities

**Frontend**
- **Framework JS** : Vue.js 3 + TypeScript (pour les interfaces interactives)
- **JavaScript Vanilla** : Pour certaines fonctionnalités simples
- **CSS** : DSFR (Système de Design de l'État)
- **Build** : Webpack Encore

**Infrastructure & DevOps**
- **Hébergement** : Scalingo (PaaS sur Outscale)
- **Conteneurisation** : Docker (développement local)
- **Message queue** : Symfony Messenger
- **Cache** : Redis 7.0
- **Monitoring** : Sentry, Matomo

### Structure du projet

```
/
├── assets/                    # Ressources frontend
│   ├── scripts/
│   │   ├── vanilla/          # JavaScript vanilla
│   │   └── vue/              # Applications Vue.js + TypeScript
│   └── styles/               # SCSS/CSS
├── config/                    # Configuration Symfony
├── migrations/                # Migrations Doctrine
├── public/                    # Point d'entrée web
├── src/
│   ├── Command/              # Commandes Symfony CLI
│   ├── Controller/           # Contrôleurs (API, Back, Front)
│   ├── Entity/               # Entités Doctrine
│   ├── Form/                 # Formulaires Symfony
│   ├── Repository/           # Repositories Doctrine
│   ├── Service/              # Logique métier
│   │   ├── Signalement/      # Gestion des signalements
│   │   ├── Interconnection/  # Interconnexions externes
│   │   ├── Mailer/           # Emails
│   │   ├── Import/           # Import de données
│   │   └── ...
│   ├── Security/             # Authentification/Autorisation
│   ├── Messenger/            # Messages asynchrones
│   └── EventSubscriber/      # Event listeners
├── templates/                 # Templates Twig
│   ├── back/                 # Back-office
│   ├── front/                # Front-office
│   └── emails/               # Templates d'emails
├── tests/                     # Tests PHPUnit
├── tools/                     # Outils de qualité de code
└── vendor/                    # Dépendances PHP
```

## Concepts métier principaux

### Signalement
- Entité centrale : déclaration d'un problème de logement par un usager
- Workflow : brouillon → validation → affectation → traitement → clôture
- Types de désordres : insalubrité, danger, non-décence

**TODO :**
- [ ] Documenter les statuts de signalement et leurs transitions
- [ ] Lister les critères d'auto-affectation
- [ ] Décrire la logique de scoring/criticité

### Partenaires
- Organismes en charge du traitement des signalements (CAF, ARS, DDT, communes, etc.)
- Organisation par territoire (département, commune)
- Rôles : admin, agent

**TODO :**
- [ ] Lister les types de partenaires et leurs responsabilités
- [ ] Documenter les règles d'affectation par type de partenaire

### Territoires
- Découpage géographique pour l'affectation
- Configuration des partenaires et règles métier par territoire

**TODO :**
- [ ] Expliquer la hiérarchie territoire/commune
- [ ] Documenter les Feature Flags spécifiques aux territoires

### Suivi
- Historique des actions sur un signalement
- Visites, arrêtés, courriers, etc.

**TODO :**
- [ ] Lister les types de suivi disponibles
- [ ] Documenter le workflow des visites (planification, confirmation, rapport)

## Ajouts de données

### Signalement
Quand une donnée est ajoutée dans l'entité signaleement, idéalement, il faut l'ajouter dans différents contextes.

#### Formulaire de création de signalement pour les usagers
Technologie : PHP / Vue

Fichier de départ :
- Vue : app-front-signalement-form.ts
- PHP : src/Service/Signalement/SignalementBuilder.php

Instructions : Ne le fais jamais automatiquement. Il faut passer par les fichiers json et créer des scénarii spécifiques. Affiche une alerte pour y faire penser.

#### Formulaire de création de signalement pour les pro
Technologie : PHP / Javascript Vanilla.

Fichier de départ :
- PHP : src/Controller/Back/SignalementCreateController.php
- Javascript : assets/scripts/vanilla/controllers/back_signalement_form.js

Attention : penser à vérifier qu'il faut activer ou non les champs selon les éléments sélectionnés.

#### Formulaire de création de signalement pour les services de secours
Technologie : PHP / Javascript Vanilla.

Fichier de départ : src/Controller/ServiceSecours/ServiceSecoursController.php

#### Formulaires d'édition pour les pro
Technologie : PHP / Javascript Vanilla.

Fichiers de départ :
- pour l'accès à la page : src/Controller/Back/SignalementController.php
- pour les différentes éditions : src/Controller/Back/SignalementEditController.php

#### Formulaires d'édition pour les usagers
Technologie : PHP / Javascript Vanilla.

Fichiers de départ :
- pour l'accès à la page : src/Controller/SignalementEditController.php
- pour l'affichage des informations : templates/front/suivi_signalement_dossier.html.twig

**Note** : Les usagers ne peuvent actuellement pas éditer leurs coordonnées de déclarant/tiers. Seul l'affichage est disponible. Si une nouvelle donnée concerne les coordonnées du déclarant, il faut l'ajouter dans la section "Coordonnées du tiers déclarant" du template d'affichage.

#### API
Technologie : PHP

Fichiers de départ :
- pour la création : src/Controller/Api/SignalementCreateController.php
- pour la liste des informations : src/Controller/Api/SignalementListController.php

#### Export CSV
Technologie : PHP

Fichiers à modifier :
- src/Factory/SignalementExportFactory.php
- src/Dto/SignalementExport.php
- src/Repository/Query/SignalementList/ExportIterableQuery.php
- src/Service/Signalement/Export/SignalementExportHeader.php
- src/Service/Signalement/Export/SignalementExportSelectableColumns.php

#### Export PDF
Technologie : Twig

Fichier de template : templates/pdf/signalement.html.twig

## Conventions de code

### PHP
- **PSR-12** : Standard de code respecté via PHP-CS-Fixer
- **PHPStan niveau 6** : Analyse statique stricte
- **Namespaces** : `App\` (src/)
- **Services** : Injection de dépendances via le container Symfony
- **Repositories** : Utilisation de Query Builders pour les requêtes complexes

**TODO :**
- [ ] Documenter les patterns de nommage des services (Manager vs Service vs Handler)
- [ ] Ajouter les règles de gestion des exceptions métier
- [ ] Préciser la convention pour les méthodes de Repository (find*, get*)

### JavaScript/TypeScript
- **ESLint** : Linting avec configs séparées (vanilla / Vue)
- **Prettier** : Formatage automatique
- **Vue** : Composition API (TypeScript)
- **Naming** : camelCase pour JS, kebab-case pour les composants Vue

**TODO :**
- [ ] Documenter la structure des stores Vue (si pattern state management)
- [ ] Préciser quand utiliser Vue vs Vanilla JS

### Base de données
- **Migrations** : Toute modification de schéma via migrations Doctrine
- **Naming** : snake_case pour les tables/colonnes
- **Relations** : Doctrine Associations (OneToMany, ManyToOne, etc.)

**TODO :**
- [ ] Lister les index importants pour les performances
- [ ] Documenter les tables principales et leurs relations

## Commandes utiles

### Développement local
```bash
make build          # Installation complète (Docker + dépendances + fixtures)
make start          # Démarrer les conteneurs
make stop           # Arrêter les conteneurs
make reset          # Reset DB + fixtures
```

### Qualité de code
```bash
composer cs-fix     # Fix PHP CS
composer cs-check   # Vérifier PHP CS
composer stan       # Analyse PHPStan
npm run es-vue-fix  # Fix ESLint Vue/TS
npm run es-js-fix   # Fix ESLint JS vanilla
```

### Tests
```bash
make test           # Lancer les tests PHPUnit
```

### Assets
```bash
npm run dev         # Build développement
npm run watch       # Watch mode
npm run build       # Build production
```

## Accès et authentification

### Environnement local
- URL : http://localhost:8080
- Mot de passe pour tous les utilisateurs de test : `signallogement`
- Voir [README.md](../README.md) pour la liste des comptes de test

### Rôles
- `ROLE_ADMIN` : Super admin national
- `ROLE_ADMIN_TERRITORY` : Admin d'un territoire
- `ROLE_ADMIN_PARTNER` : Admin d'un partenaire
- `ROLE_USER_PARTNER` : Agent d'un partenaire

## APIs et interconnexions

### API REST
- Documentation : `/api/doc` (Nelmio API Doc)
- Authentification : JWT (lcobucci/jwt)
- Rate limiting : Symfony RateLimiter

### Interconnexions externes
- **Esabora** : Synchronisation avec SIShab
- **OILHI** : API Zapier/Airtable
- **API Gouv** : Adresse, Entreprise, etc.

## Tests et qualité

### Tests
- PHPUnit pour tests unitaires et fonctionnels
- Fixtures : DataFixtures + Faker
- Base de test : `APP_ENV=test`

### Outils de qualité
- **GrumPHP** : Git hooks (pre-commit)
- **PHP-CS-Fixer** : Formatage
- **PHPStan** : Analyse statique

## Points d'attention

### Sécurité
- Fichier [docs/SECURITY.md](../docs/SECURITY.md) : veille sécurité
- CSP (Content Security Policy) activable
- 2FA via Scheb 2FA Bundle
- Sanitization : HTML Sanitizer (Symfony)

### Performance
- Cache : Redis pour sessions et cache Doctrine
- Queue asynchrone : Messenger pour emails et tâches lourdes
- Preloading opcache en production

### Données sensibles
- `.env` : Ne jamais commiter `.env.local`
- Secrets : Utiliser les variables d'environnement
- S3 OVH : Bucket pour fichiers uploads (voir README)

## Documentation complémentaire

- [README.md](../README.md) : Installation et démarrage
- [docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md) : Architecture technique détaillée
- [docs/API.md](../docs/API.md) : Documentation API
- [docs/EXPLOITATION.md](../docs/EXPLOITATION.md) : Guide d'exploitation
- [docs/CONTRIBUTING.md](../docs/CONTRIBUTING.md) : Guide de contribution
- [Wiki GitHub](https://github.com/MTES-MCT/histologe/wiki) : Documentation projet

## Environnements

- **Production** : https://signal-logement.beta.gouv.fr
- **Staging** : https://histologe-staging.incubateur.net
- **Demo** : https://histologe-demo.osc-fr1.scalingo.io
- **Local** : http://localhost:8080

## Pièges courants et bonnes pratiques

**TODO :**
- [ ] Lister les erreurs fréquentes (ex: oublier de flush() Doctrine)
- [ ] Documenter les problèmes de cache à éviter
- [ ] Ajouter les patterns de debug recommandés
- [ ] Expliquer comment gérer les Feature Flags
- [ ] Documenter les limitations connues (performance, données)

## Workflow de développement

**TODO :**
- [ ] Décrire le processus de création d'une nouvelle fonctionnalité
- [ ] Documenter la stratégie de branches Git (feature/, hotfix/, etc.)
- [ ] Préciser les revues de code attendues
- [ ] Ajouter le process de déploiement (Staging → Prod)

## Dépendances externes critiques

**TODO :**
- [ ] Documenter les APIs externes et leurs limitations (rate limits, etc.)
- [ ] Lister les services tiers critiques (S3, Brevo, etc.)
- [ ] Ajouter les procédures en cas d'indisponibilité

## Contact et support

Pour toute question ou contribution, consulter le fichier [CONTRIBUTING.md](../docs/CONTRIBUTING.md).

**TODO :**
- [ ] Ajouter les contacts clés de l'équipe
- [ ] Lien vers Slack/Mattermost si applicable
