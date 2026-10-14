# Consignes du projet

## Environnement de développement

L'application fonctionne avec Docker.

Toutes les commandes PHP, Symfony, Composer et PHPUnit doivent être
exécutées dans les conteneurs Docker appropriés.

Ne jamais exécuter directement une commande PHP sur la machine hôte.

Privilégier les commandes du Makefile lorsqu'elles existent.

## Tests

Après une modification, exécuter uniquement les tests pertinents
pour le code modifié.

Les tests doivent toujours être exécutés via Docker.

## PHPStan

Ne jamais lancer PHPStan automatiquement.

PHPStan ne doit être exécuté que si l'utilisateur le demande explicitement.

## Assets front-end

Ne jamais exécuter automatiquement via Docker :

`npm run build`

Après une modification nécessitant une recompilation des assets front-end,
indiquer à l'utilisateur qu'il doit lancer :

`npm run watch`

Ne pas lancer `npm run watch` à la place de l'utilisateur.
