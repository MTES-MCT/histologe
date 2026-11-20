---
name: audit-securite
description: "Réaliser un audit de sécurité et produire un rapport local"
---
Analyse les risques de sécurité du dépôt.
Crée le rapport dans `tmp/rapport/AAAA-MM-JJ-audit-securite.md` (remplace AAAA-MM-JJ par la date du jour).

## Interdits
- Ne modifie aucun fichier, sauf le rapport `tmp/rapport/AAAA-MM-JJ-audit-securite.md`.
- Ni commit, ni branche, ni pull request.
- Aucun test intrusif : aucune requête vers staging/prod/démo, aucune tentative d'exploitation, aucune écriture en base.
- Ne recopie jamais de secret (clé, token, mot de passe) dans le rapport : masque-le.
- Autorisé : lecture du code, `composer audit`, `npm audit`, `bin/console debug:router`.

## Points prioritaires du projet
- Accès, authentification, saisies utilisateur et les dépendances
- Cloisonnement territoire / partenaire / rôle : `src/Security/Voter/`, `config/packages/security.yaml`, contrôles d'accès dans les contrôleurs et repositories (IDOR).
- API : authentification JWT, rate limiting, exposition de données.
- Accès usager front (code de suivi), formulaires publics.
- Fichiers : upload, téléchargement, `FileVoter`, stockage S3.
- Exports PDF/CSV, sorties Twig `|raw`, HTML Sanitizer, CSP.
- Interconnexions (Esabora, API Gouv), messages Messenger.
- Non-régression des incidents documentés dans `docs/postmortems/`.

## Rapport (en français)
Structure : synthèse, portée et limites, failles confirmées, risques à vérifier, dépendances.
Pour chaque élément : gravité, confiance, fichiers et lignes, préconditions, impact, correction recommandée.
- Gravité : Critique (exploitable sans authentification ou fuite massive), Élevée, Moyenne, Faible.
- Confiance : Haute (chemin vérifié), Moyenne, Basse.