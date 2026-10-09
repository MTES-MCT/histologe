# Descriptions des suivis automatiques

Ce guide explique comment gérer le texte (`description`) des suivis créés automatiquement par l'application (crons, actions bailleur, actions admin…).

**Règle générale : pas de texte en dur dans le code PHP.** Les textes sont dans des templates Twig du dossier `templates/suivi/`, et passent tous par le service `App\Service\Signalement\Suivi\SuiviDescriptionHelper`.

## Choisir le bon cas

| Le texte contient-il des variables (date, motif, nom, délai…) ? | Cas à appliquer |
|---|---|
| **Non**, texte fixe | [Cas 1 : texte calculé à l'affichage](#cas-1--texte-fixe-calculé-à-laffichage) |
| **Oui** | [Cas 2 : texte généré à la création puis enregistré](#cas-2--texte-avec-variables-généré-à-la-création) |

Les messages saisis par un utilisateur (commentaires, messages usager / bailleur / partenaire) ne sont pas concernés : ils sont enregistrés tels quels, après nettoyage (`HtmlCleaner`).

---

## Cas 1 : texte fixe, calculé à l'affichage

Rien n'est enregistré en base (`description = ''`). Le texte est rendu à chaque affichage.

### Fonctionnement

1. `Suivi::getDescription()` délègue à `SuiviTransformerService::transformDescription()`.
2. `SuiviTransformerService` appelle `SuiviDescriptionHelper::getDescription($category, $recipient)`.
3. Le helper cherche un template dans `SPECIFIC_TEMPLATES` : d'abord pour le destinataire demandé, sinon pour `SuiviRecipient::DEFAULT`.
4. Si un template existe, son rendu remplace la description enregistrée.

Le `SuiviTransformerService` est injecté dans l'entité par `SuiviTransformerListener` au `postLoad` Doctrine. **Un suivi qui vient d'être créé, donc pas encore rechargé depuis la base, n'a pas de transformer** : `getDescription()` renvoie alors la valeur brute, c'est-à-dire `''`.

### Étapes

1. **Créer le template** `templates/suivi/<categorie_en_snake_case>.html.twig`, par exemple `injonction_bailleur_expiree.html.twig`. Il contient uniquement le texte.
2. **Si le texte diffère selon le destinataire**, créer un template par destinataire en ajoutant le suffixe du destinataire, par exemple `injonction_bailleur_demande_cloture_par_bailleur_usager.html.twig`.
3. **Déclarer le template dans `SuiviDescriptionHelper::SPECIFIC_TEMPLATES`** :
   ```php
   SuiviCategory::MA_CATEGORIE->value => [
       SuiviRecipient::USAGER->value => 'suivi/ma_categorie_usager.html.twig', // optionnel
       SuiviRecipient::DEFAULT->value => 'suivi/ma_categorie.html.twig',
   ],
   ```
4. **Créer le suivi avec une description vide** :
   ```php
   $this->suiviManager->createSuivi(
       signalement: $signalement,
       description: '',
       category: SuiviCategory::MA_CATEGORIE,
   );
   ```
5. **Si la catégorie existe déjà en production**, écrire une migration qui vide les descriptions existantes (voir [Migration](#migration-pour-une-catégorie-existante)).
6. **Mettre à jour les fixtures** (`src/DataFixtures/Files/Suivi.yml`) et les tests qui vérifiaient le texte passé à `createSuivi()` : ils doivent maintenant vérifier `''`.

### Points d'attention

- **Lire le texte ailleurs que dans l'affichage** : par exemple pour remplir `com_cloture` lors d'une clôture. Appeler `SuiviDescriptionHelper::getDescription()` plutôt que de recopier le texte. Exemple : `RemindInjonctionSignalementCommand::closeSignalementsWithoutSuiviTravaux()`.
- **Emails et notifications** : vérifier qu'ils n'utilisent pas `$suivi->getDescription()` au moment de la création, car la valeur serait `''` (voir plus haut). Aujourd'hui, les emails de nouveau suivi n'incluent pas la description.
- **Recherche** : la recherche `LIKE` sur `suivi.description` (`SuiviRepository`) ne trouve plus ces suivis par leur texte. C'est accepté.

---

## Cas 2 : texte avec variables, généré à la création

Le texte est rendu **une seule fois**, à la création du suivi, puis enregistré en base. Les valeurs des variables sont figées au moment de l'action.

### Étapes

1. **Créer le template** `templates/suivi/<categorie_en_snake_case>.html.twig` avec ses variables :
   ```twig
   Relance envoyée à l'usager pour lui demander de confirmer la réalisation des travaux déclarée par le bailleur il y a {{ threshold }}.
   ```
2. **Déclarer le template dans `SuiviDescriptionHelper::STORED_DESCRIPTION_TEMPLATES`** :
   ```php
   SuiviCategory::MA_CATEGORIE->value => 'suivi/ma_categorie.html.twig',
   ```
3. **Générer la description avec le helper** au moment de la création :
   ```php
   $this->suiviManager->createSuivi(
       signalement: $signalement,
       description: $this->suiviDescriptionHelper->buildStoredDescription(
           SuiviCategory::MA_CATEGORIE,
           ['threshold' => $threshold]
       ),
       category: SuiviCategory::MA_CATEGORIE,
   );
   ```
   Dans un contrôleur, injecter `SuiviDescriptionHelper` en argument de l'action. Ne pas utiliser `$this->renderView()` directement : on garde un seul point d'entrée.

`buildStoredDescription()` lève une `\LogicException` si la catégorie n'est pas déclarée dans `STORED_DESCRIPTION_TEMPLATES`.

### Points d'attention

- **Échappement** : Twig échappe les variables. N'utiliser `|raw` que pour du HTML déjà nettoyé, par exemple `HtmlCleaner::cleanFrontEndEntry()`. `SuiviManager::createSuivi()` repasse de toute façon la description au `HtmlSanitizer`.
- **Données existantes** : pas de migration nécessaire, les descriptions déjà enregistrées restent valides.

---

## Migration pour une catégorie existante

Pour passer une catégorie existante au **cas 1**, il faut vider les descriptions déjà en base. Modèle : `migrations/Version20261009100808.php`.

```php
final class VersionXXXXXXXXXXXXXX extends AbstractMigration
{
    private const array DESCRIPTIONS = [
        'MA_CATEGORIE' => 'Texte historique exact, pour le rollback.',
    ];

    public function up(Schema $schema): void
    {
        foreach (array_keys(self::DESCRIPTIONS) as $category) {
            $this->addSql('UPDATE suivi SET description = \'\' WHERE category = :category', ['category' => $category]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::DESCRIPTIONS as $category => $description) {
            $this->addSql(
                'UPDATE suivi SET description = :description WHERE category = :category',
                ['description' => $description, 'category' => $category]
            );
        }
    }
}
```

Deux règles :
- **Mettre le texte historique en dur dans la migration.** Ne pas appeler `SuiviDescriptionHelper` : c'est un service (Twig) qu'on ne peut pas utiliser dans une migration, et ses textes changeront avec le temps alors que la migration doit rester figée.
- **Passer les valeurs en paramètres SQL** (`:description`), jamais par concaténation : les textes contiennent souvent des apostrophes.

---

## Références

- Service : `src/Service/Signalement/Suivi/SuiviDescriptionHelper.php`
- Rendu à l'affichage : `src/Service/Signalement/Suivi/SuiviTransformerService.php`
- Destinataires : `src/Service/Signalement/Suivi/SuiviRecipient.php`
- Templates : `templates/suivi/`
- Exemples :
  - cas 1 : `InjonctionBailleurService::handleResponse()`, `ResetInjonctionNoResponseCommand`
  - cas 2 : `RemindInjonctionSignalementCommand::remindUsagerForCloture()`, `SignalementInjonctionController::adminCancelInjonctionProcedure()`

## Dette restante

D'autres catégories n'utilisent pas encore les templates :
- **`SPECIFIC_DESCRIPTIONS`** : `ASK_FEEDBACK_SENT`, `SIGNALEMENT_IS_ACTIVE`, `AFFECTATION_IS_ACCEPTED`, `INTERVENTION_IS_REQUIRED` et `INJONCTION_BAILLEUR_LOGIN_BAILLEUR` ont encore leur texte dans cette constante PHP. À migrer vers `SPECIFIC_TEMPLATES` à l'occasion. Attention : la méthode statique `getSpecificDescriptionForCategoryAndRecipient()` est encore appelée par la migration `Version20260629085411`.
- **Appels directs à `renderView()`** : par exemple `front_signalement_confirm_edit_email_occupant.html.twig` dans `SignalementConfirmEditController`. À faire passer par `buildStoredDescription()`.
- **Constantes de texte** : il en reste dans le helper (`DESCRIPTION_MOTIF_CLOTURE_PARTNER`, `DESCRIPTION_TRAVAUX_MISE_EN_CONFORMITE`) et dans `RemindInjonctionSignalementCommand` (`CLOSE_INJONCTION_SUIVI`).
