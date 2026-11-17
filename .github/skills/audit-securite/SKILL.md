---
name: audit-securite
description: "Réaliser un audit de sécurité et produire un rapport local"
---
Analyse les risques de sécurité du dépôt, notamment les accès,
l’authentification, les entrées utilisateur et les dépendances.

Ne modifie aucun fichier du dépôt, sauf pour créer un rapport daté dans
src/Controller/Dev/. Ne crée ni commit, ni branche, ni pull request.
N’exécute aucun test intrusif.

Pour chaque faille, indique sa gravité, les fichiers et lignes concernés,
les préconditions, l’impact, le niveau de confiance et une correction
recommandée. Sépare les failles confirmées des risques à vérifier.
Précise la portée et les limites de l’analyse.
