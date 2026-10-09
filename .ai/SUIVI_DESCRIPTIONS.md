# Descriptions des suivis automatiques

Pas de texte en dur dans le PHP : les textes des suivis automatiques sont dans `templates/suivi/` et passent par `SuiviDescriptionHelper`.

| Texte | Map du helper | En base | Méthode |
|---|---|---|---|
| Fixe | `DISPLAY_TEMPLATES` | `''` | `getDisplayDescription()` (appelée par `SuiviTransformerService` à l'affichage) |
| Avec variables | `CREATION_TEMPLATES` | texte rendu | `buildDescriptionForCreation($category, $params)` à la création |

Les messages saisis par un utilisateur ne sont pas concernés.

**Pourquoi deux maps ?** Un template de `DISPLAY_TEMPLATES` remplace la description en base à chaque affichage, sans les variables d'origine. Un template avec variables s'y afficherait vide en prod et lèverait une exception en test (`strict_variables`).

## Texte fixe

1. Créer `templates/suivi/<categorie_snake_case>.html.twig`. Pour un texte différent selon le destinataire, ajouter un template suffixé, par exemple `_usager`.
2. Le déclarer dans `DISPLAY_TEMPLATES` (clé `SuiviRecipient::DEFAULT`, et `USAGER` si besoin).
3. Créer le suivi avec `description: ''`.
4. Catégorie existante : écrire une migration (voir plus bas), puis mettre à jour les fixtures et les tests.

Points d'attention :
- **Un suivi juste créé n'a pas de transformer** (il est injecté au `postLoad`) : `getDescription()` renvoie `''`. Vérifier que les emails et notifications envoyés à la création n'en dépendent pas.
- **Besoin du texte ailleurs** (par exemple `com_cloture`) : appeler `getDisplayDescription()` plutôt que de recopier le texte.

## Texte avec variables

1. Créer le template avec ses variables. Utiliser `|raw` uniquement pour du HTML déjà nettoyé (`HtmlCleaner`).
2. Le déclarer dans `CREATION_TEMPLATES`.
3. À la création : `description: $suiviDescriptionHelper->buildDescriptionForCreation(SuiviCategory::X, ['var' => $value])`. Pas de `renderView()` direct.

## Migration (texte fixe, catégorie existante)

Vider les descriptions existantes. Modèle : `migrations/Version20261009100808.php`.
- Mettre en dur dans la migration le texte historique utilisé par `down()`. Ne pas appeler le helper : c'est un service, et ses textes vont évoluer.
- Passer les valeurs en paramètres SQL (`:description`), jamais par concaténation, à cause des apostrophes.

## Dette restante

- **Textes encore dans la constante `SPECIFIC_DESCRIPTIONS`**, à migrer vers `DISPLAY_TEMPLATES` : `ASK_FEEDBACK_SENT`, `SIGNALEMENT_IS_ACTIVE`, `AFFECTATION_IS_ACCEPTED`, `INTERVENTION_IS_REQUIRED`, `INJONCTION_BAILLEUR_LOGIN_BAILLEUR`. La méthode statique est encore utilisée par `Version20260629085411`.
- **`SignalementConfirmEditController`** fait encore un `renderView()` direct.
- **Constantes de texte restantes** :
  - dans le helper : `DESCRIPTION_MOTIF_CLOTURE_PARTNER`, `DESCRIPTION_TRAVAUX_MISE_EN_CONFORMITE` ;
  - dans `RemindInjonctionSignalementCommand` : `CLOSE_INJONCTION_SUIVI`.
