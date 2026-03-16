# Guide d'ajout de données dans l'entité Signalement

Ce guide détaille la procédure complète pour ajouter un nouveau champ dans l'entité `Signalement` et le rendre disponible dans tous les contextes de l'application.

## Vue d'ensemble

Quand une donnée est ajoutée dans l'entité `Signalement`, elle doit idéalement être ajoutée dans les contextes suivants :

1. ✅ **Formulaire de création de signalement pour les usagers** (Vue.js + PHP)
2. ✅ **Formulaire de création de signalement pour les pro** (PHP + JS Vanilla)
3. ✅ **Formulaire de création de signalement pour les services de secours** (PHP + JS Vanilla)
4. ✅ **Formulaires d'édition pour les pro** (Back-office)
5. ✅ **Formulaires d'édition pour les usagers** (Front-office)
6. ✅ **API** (Création et liste)
7. ⚠️ **Export CSV** (Optionnel selon le besoin métier)
8. ✅ **Export PDF**
9. ✅ **Listener de suivi** (Pour tracer les modifications des usagers)

---

## 1. Formulaire de création pour les usagers

**Technologie** : Vue.js 3 + TypeScript + PHP

**Fichiers de départ** :
- Vue : `assets/scripts/vue/app-front-signalement-form.ts`
- PHP : `src/Service/Signalement/SignalementBuilder.php`

### ⚠️ IMPORTANT
**Ne jamais modifier automatiquement sans demander à l'utilisateur.**

Cette partie nécessite de passer par les fichiers JSON de scénarii et de créer des scénarii spécifiques. C'est une modification délicate qui impacte le parcours utilisateur.

**Toujours afficher une alerte pour rappeler cette étape à l'utilisateur.**

---

## 2. Formulaire de création pour les pro

**Technologie** : PHP (Symfony Forms) + JavaScript Vanilla

### Fichiers à modifier

#### Contrôleur
- `src/Controller/Back/SignalementCreateController.php`

#### Formulaire Symfony
Selon l'onglet concerné :
- Onglet Adresse : `src/Form/SignalementDraftAddressType.php`
- Onglet Coordonnées : `src/Form/SignalementDraftCoordonneesType.php`
- Onglet Composition : `src/Form/SignalementDraftCompositionType.php`
- etc.

#### Template Twig
Selon l'onglet concerné :
- `templates/back/signalement_create/tabs/tab-adresse.html.twig`
- `templates/back/signalement_create/tabs/tab-coordonnees.html.twig`
- etc.

#### JavaScript (si nécessaire)
- `assets/scripts/vanilla/controllers/back_signalement_form.js`

### Points d'attention
- Vérifier les dépendances entre champs (activation conditionnelle)
- S'assurer que les validations sont cohérentes avec les autres contextes
- Tester le comportement du formulaire avec différents profils de déclarant

### Exemple : Ajout des champs Syndic

```php
// Dans SignalementDraftCoordonneesType.php
->add('denominationSyndic', TextType::class, [
    'label' => 'Dénomination',
    'required' => false,
])
->add('nomSyndic', TextType::class, [
    'label' => 'Nom de famille',
    'help' => 'Saisissez le nom du syndic',
    'required' => false,
])
->add('mailSyndic', TextType::class, [
    'label' => 'Adresse e-mail',
    'help' => 'Format attendu : nom@domaine.fr',
    'required' => false,
])
```

```twig
{# Dans tab-coordonnees.html.twig #}
<fieldset class="fr-fieldset">
    <legend>
        <h3 class="fr-h5">Syndic</h3>
    </legend>
    <div class="fr-fieldset__element fr-mb-5v">
        <div class="fr-alert fr-alert--info fr-alert--sm">
            <p>Si le logement dispose d'un syndic, renseignez les coordonnées du syndic.</p>
        </div>
    </div>
    <div class="fr-fieldset__element">
        {{ form_row(formCoordonnees.denominationSyndic) }}
    </div>
    <!-- ... autres champs ... -->
</fieldset>
```

---

## 3. Formulaire de création pour les services de secours

**Technologie** : PHP + JavaScript Vanilla

**Fichier de départ** : `src/Controller/ServiceSecours/ServiceSecoursController.php`

Les services de secours ont un formulaire simplifié. Vérifier si le champ doit être ajouté selon les besoins métier.

---

## 4. Formulaires d'édition pour les pro (Back-office)

**Technologie** : PHP + JavaScript Vanilla

### Architecture complète

```
1. Contrôleur → 2. DTO Request → 3. Manager → 4. Templates → 5. Intégration → 6. Affichage conditionnel
```

### Étape 1 : Créer le DTO de requête

**Fichier** : `src/Dto/Request/Signalement/[Nom]Request.php`

```php
<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesSyndicRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Length(max: 255)]
        private readonly ?string $denomination = null,
        #[Assert\Length(max: 255)]
        private readonly ?string $nom = null,
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255)]
        private readonly ?string $mail = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
    ) {
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    // ... autres getters
}
```

### Étape 2 : Ajouter la méthode dans le Manager

**Fichier** : `src/Manager/SignalementManager.php`

```php
use App\Dto\Request\Signalement\CoordonneesSyndicRequest;

public function updateFromCoordonneesSyndicRequest(
    Signalement $signalement,
    CoordonneesSyndicRequest $coordonneesSyndicRequest,
): bool {
    $signalement
        ->setDenominationSyndic($coordonneesSyndicRequest->getDenomination())
        ->setNomSyndic($coordonneesSyndicRequest->getNom())
        ->setMailSyndic($coordonneesSyndicRequest->getMail())
        ->setTelSyndic($coordonneesSyndicRequest->getTelephone())
        ->setTelSyndicSecondaire($coordonneesSyndicRequest->getTelephoneBis());

    $this->save($signalement);

    return $this->suiviManager->addSuiviIfNeeded(
        signalement: $signalement,
        description: 'Les coordonnées du syndic ont été modifiées par ',
    );
}
```

### Étape 3 : Ajouter la route dans le contrôleur

**Fichier** : `src/Controller/Back/SignalementEditController.php`

```php
use App\Dto\Request\Signalement\CoordonneesSyndicRequest;

#[Route('/{uuid:signalement}/edit-coordonnees-syndic', name: 'back_signalement_edit_coordonnees_syndic', methods: 'POST')]
#[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
public function editCoordonneesSyndic(
    Signalement $signalement,
    Request $request,
    SignalementManager $signalementManager,
    SerializerInterface $serializer,
    ValidatorInterface $validator,
): JsonResponse {
    /** @var array<string, mixed> $payload */
    $payload = $request->getPayload()->all();
    $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
    if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_syndic_'.$signalement->getId(), $token)) {
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];
        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }

    /** @var CoordonneesSyndicRequest $coordonneesSyndicRequest */
    $coordonneesSyndicRequest = $serializer->deserialize(
        json_encode($request->getPayload()->all()),
        CoordonneesSyndicRequest::class,
        'json'
    );

    $validationGroups = ['Default'];
    if ($signalement->getProfileDeclarant()) {
        $validationGroups[] = $signalement->getProfileDeclarant()->value;
    }

    $errorMessage = FormHelper::getErrorsFromRequest(
        $validator,
        $coordonneesSyndicRequest,
        $validationGroups
    );

    if (!empty($errorMessage)) {
        $response = ['code' => Response::HTTP_BAD_REQUEST];
        $response = [...$response, ...$errorMessage];
        return $this->json($response, $response['code']);
    }

    $subscriptionCreated = $signalementManager->updateFromCoordonneesSyndicRequest($signalement, $coordonneesSyndicRequest);
    $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du syndic ont bien été modifiées.'];
    if ($subscriptionCreated) {
        $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
    }

    $htmlTargetContents = [
        [
            'target' => '#signalement-information-syndic-container',
            'content' => $this->renderView('back/signalement/view/information/information-syndic.html.twig', ['signalement' => $signalement]),
        ],
    ];

    return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
}
```

### Étape 4 : Créer les templates

#### Template d'affichage
**Fichier** : `templates/back/signalement/view/information/information-syndic.html.twig`

```twig
<div class="fr-grid-row">
    <div class="fr-col-12 fr-col-md-8">
        <h4 class="fr-h6">Informations sur le syndic</h4>
    </div>
    <div class="fr-col-12 fr-col-md-4 fr-text--right">
        {% if is_granted('SIGN_EDIT_ACTIVE', signalement) %}
            <button href="#" data-fr-opened="false" aria-controls="fr-modal-edit-coordonnees-syndic" class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-edit-line">
                Modifier
            </button>
        {% endif %}
    </div>
    <div class="fr-col-12 fr-col-md-12">
        <strong>Dénomination :</strong> {{ signalement.denominationSyndic }}
    </div>
    <div class="fr-col-12 fr-col-md-6">
        <strong>Nom :</strong> {{ signalement.nomSyndic }}
    </div>
    <div class="fr-col-12">
        <strong>Courriel :</strong>
        {% if signalement.mailSyndic %}
            <a href="mailto:{{ signalement.mailSyndic }}">{{ signalement.mailSyndic }}</a>
        {% endif %}
    </div>
    <div class="fr-col-12 fr-col-md-6">
        <strong>Tél. :</strong>
        {% if signalement.telSyndic %}
            <a href="tel:{{ signalement.telSyndicDecoded }}">{{ signalement.telSyndicDecoded|phone }}</a>
        {% endif %}
    </div>
</div>
```

#### Template modal d'édition
**Fichier** : `templates/back/signalement/view/edit-modals/edit-coordonnees-syndic.html.twig`

```twig
<dialog aria-labelledby="fr-modal-title-modal-edit-coordonnees-syndic" id="fr-modal-edit-coordonnees-syndic" class="fr-modal">
    <div class="fr-container fr-container--fluid fr-container-md">
        <div class="fr-grid-row fr-grid-row--center">
            <div class="fr-col-12 fr-col-md-8 fr-col-lg-6">
                <div class="fr-modal__body">
                    <form method="POST" id="form-edit-coordonnees-syndic" enctype="application/json" action="{{ path('back_signalement_edit_coordonnees_syndic',{uuid:signalement.uuid}) }}">
                        <div class="fr-modal__header">
                            <button type="button" class="fr-btn--close fr-btn" aria-controls="fr-modal-edit-coordonnees-syndic">Fermer</button>
                        </div>
                        <div class="fr-modal__content">
                            <h1 id="fr-modal-title-modal-edit-coordonnees-syndic" class="fr-modal__title">
                                Modifier les coordonnées du syndic
                            </h1>

                            <div class="fr-input-group">
                                <label class="fr-label" for="coordonneesSyndicDenomination">Dénomination</label>
                                <input class="fr-input" type="text" id="coordonneesSyndicDenomination" name="denomination" value="{{ signalement.denominationSyndic }}" maxlength="255" autocomplete="off">
                            </div>

                            <!-- ... autres champs ... -->

                            <input type="hidden" name="_token" value="{{ csrf_token('signalement_edit_coordonnees_syndic_'~signalement.id) }}">
                        </div>

                        <div class="fr-modal__footer">
                            <ul class="fr-btns-group fr-btns-group--right fr-btns-group--inline-reverse fr-btns-group--inline-lg fr-btns-group--icon-left">
                                <li>
                                    <button class="fr-btn fr-icon-check-line" type="submit">Valider</button>
                                </li>
                                <li>
                                    <button type="button" class="fr-btn fr-btn--secondary fr-icon-close-line" aria-controls="fr-modal-edit-coordonnees-syndic">Annuler</button>
                                </li>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</dialog>
```

### Étape 5 : Intégrer dans les vues

#### Inclure la section d'affichage
**Fichier** : `templates/back/signalement/view/tabs/tab-situation.html.twig`

```twig
{% if signalement|display_signalement_info_syndic %}
    <div id="signalement-information-syndic-container">
        {% include 'back/signalement/view/information/information-syndic.html.twig' %}
    </div>
    <hr class="fr-mt-3w fr-hr">
{% endif %}
```

#### Inclure le modal
**Fichier** : `templates/back/signalement/view.html.twig`

```twig
<div data-ajax-form>
    {% include 'back/signalement/view/edit-modals/edit-coordonnees-syndic.html.twig' %}
</div>
```

### Étape 6 : Affichage conditionnel (si nécessaire)

Si la section ne doit s'afficher que sous certaines conditions :

#### Ajouter le filtre Twig
**Fichier** : `src/Twig/AppExtension.php`

```php
public function getFilters(): array
{
    return [
        // ... autres filtres
        new TwigFilter('display_signalement_info_syndic', [$this, 'isDisplaySignalementInfoSyndic']),
    ];
}

public function isDisplaySignalementInfoSyndic(Signalement $signalement): bool
{
    return ProfileDeclarant::BAILLEUR_OCCUPANT !== $signalement->getProfileDeclarant()
        && ProfileOccupant::BAILLEUR_OCCUPANT !== $signalement->getProfileOccupant()
        && $signalement->hasInfosSyndic()
    ;
}
```

#### Ajouter la méthode helper dans l'entité
**Fichier** : `src/Entity/Signalement.php`

```php
public function hasInfosSyndic(): bool
{
    return $this->nomSyndic
        || $this->denominationSyndic
        || $this->telSyndic
        || $this->telSyndicSecondaire
        || $this->mailSyndic;
}
```

### Sections disponibles

| Section | DTO Request | Méthode Manager | Templates |
|---------|------------|-----------------|-----------|
| Coordonnées tiers | `CoordonneesTiersRequest` | `updateFromCoordonneesTiersRequest` | `information-tiers.html.twig` + `edit-coordonnees-tiers.html.twig` |
| Coordonnées bailleur | `CoordonneesBailleurRequest` | `updateFromCoordonneesBailleurRequest` | `information-bailleur.html.twig` + `edit-coordonnees-bailleur.html.twig` |
| Coordonnées agence | `CoordonneesAgenceRequest` | `updateFromCoordonneesAgenceRequest` | `information-agence.html.twig` + `edit-coordonnees-agence.html.twig` |
| Coordonnées syndic | `CoordonneesSyndicRequest` | `updateFromCoordonneesSyndicRequest` | `information-syndic.html.twig` + `edit-coordonnees-syndic.html.twig` |
| Procédure et démarches | `ProcedureDemarchesRequest` | `updateFromProcedureDemarchesRequest` | `information-procedure.html.twig` + `edit-procedure-demarches.html.twig` |
| Composition logement | `CompositionLogementRequest` | `updateFromCompositionLogementRequest` | `information-composition.html.twig` + `edit-composition-logement.html.twig` |
| Informations logement | `InformationsLogementRequest` | `updateFromInformationsLogementRequest` | `information-logement.html.twig` + `edit-informations-logement.html.twig` |

---

## 5. Formulaires d'édition pour les usagers (Front-office)

**Technologie** : PHP + JavaScript Vanilla

### Architecture complète

```
1. Formulaire → 2. Contrôleur → 3. Template affichage → 4. Template édition → 5. Listener de suivi
```

### Étape 1 : Créer le formulaire Symfony

**Fichier** : `src/Form/SignalementeEditFO/CoordonneesSyndicType.php`

```php
<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordonneesSyndicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('denominationSyndic', null, [
                'label' => 'Dénomination',
                'required' => false,
            ])
            ->add('nomSyndic', null, [
                'label' => 'Nom de famille',
                'help' => 'Saisissez le nom du syndic',
                'required' => false,
            ])
            ->add('mailSyndic', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telSyndic', null, [
                'label' => 'Numéro de téléphone du syndic',
                'help' => 'Format attendu : 0639987654',
                'required' => false,
            ])
            ->add('telSyndicSecondaire', null, [
                'label' => 'Numéro de téléphone secondaire du syndic',
                'help' => 'Format attendu : 0639987654',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'fr-btn--primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}
```

### Étape 2 : Ajouter la route dans le contrôleur

**Fichier** : `src/Controller/SignalementEditController.php`

```php
use App\Form\SignalementeEditFO\CoordonneesSyndicType;

#[Route('/{code}/completer/syndic', name: 'front_suivi_signalement_complete_syndic', methods: ['GET', 'POST'])]
public function suiviSignalementCompleteSyndic(
    string $code,
    SignalementRepository $signalementRepository,
    Request $request,
): Response {
    $signalement = $signalementRepository->findOneByCodeForPublic($code);
    $this->denyAccessUnlessGranted(SignalementFoVoter::SIGN_USAGER_COMPLETE, $signalement);

    /** @var SignalementUser $signalementUser */
    $signalementUser = $this->getUser();

    if ($redirect = $this->cguTiersChecker->redirectIfTiersNeedsToAcceptCgu($signalement, $signalementUser->getEmail())) {
        return $redirect;
    }

    $form = $this->createForm(CoordonneesSyndicType::class, $signalement);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $this->saveChangesAndCreateSuivi($signalement, $signalementUser);
        $this->addFlash('success', ['title' => 'Dossier complété', 'message' => 'Les coordonnées du syndic ont bien été mises à jour.']);

        return $this->redirectToRoute('front_suivi_signalement_dossier', ['code' => $signalement->getCodeSuivi()]);
    }

    return $this->render('front/edit-signalement/coordonnees-syndic.html.twig', [
        'signalement' => $signalement,
        'form' => $form,
    ]);
}
```

### Étape 3 : Ajouter l'affichage dans le template

**Fichier** : `templates/front/suivi_signalement_dossier.html.twig`

```twig
{% if signalement.hasInfosSyndic %}
    <div class="signalement-group-data-card">
        <div class="fr-display-flex-row fr-justify-content-space-between fr-align-items-center">
            <h3 class="title-blue-france fr-h5">Coordonnées du syndic</h3>
            {% if (is_granted('SIGN_USAGER_COMPLETE', signalement)) %}
                <a href="{{ path('front_suivi_signalement_complete_syndic', {'code': signalement.codeSuivi}) }}"
                    class="fr-btn fr-btn--sm fr-icon-edit-line fr-btn--icon-left fr-btn--tertiary fr-hidden fr-unhidden-md"
                    >Compléter les informations</a>
            {% endif %}
        </div>
        {% if signalement.denominationSyndic %}
            {{ signalement.denominationSyndic }}<br>
        {% endif %}
        {% if signalement.nomSyndic %}
            {{ signalement.nomSyndic }}<br>
        {% endif %}
        {% if signalement.mailSyndic %}
            {{ signalement.mailSyndic }}<br>
        {% endif %}
        {% if signalement.telSyndic %}
            {{ signalement.telSyndicDecoded|phone }}<br>
        {% endif %}
        {% if signalement.telSyndicSecondaire %}
            {{ signalement.telSyndicSecondaireDecoded|phone }}
        {% endif %}
        {% if (is_granted('SIGN_USAGER_COMPLETE', signalement)) %}
            <a href="{{ path('front_suivi_signalement_complete_syndic', {'code': signalement.codeSuivi}) }}"
                class="fr-btn fr-btn--sm fr-icon-edit-line fr-btn--icon-left fr-btn--tertiary fr-hidden-md"
                >Compléter les informations</a>
        {% endif %}
    </div>
{% elseif is_granted('SIGN_USAGER_COMPLETE', signalement) %}
    <div class="fr-callout fr-icon-information-line fr-callout--blue-cumulus fr-mt-3w">
        <p class="fr-callout__title">Syndic de copropriété</p>
        <p>
            Si votre logement fait partie d'une copropriété, vous pouvez renseigner les coordonnées du syndic.
            <br>
            <a href="{{ path('front_suivi_signalement_complete_syndic', {code: signalement.codeSuivi}) }}"
                class="fr-btn fr-btn--secondary fr-btn--icon-left fr-icon-add-line fr-mt-3v">
                Ajouter les coordonnées
            </a>
        </p>
    </div>
{% endif %}
```

### Étape 4 : Créer le template d'édition

**Fichier** : `templates/front/edit-signalement/coordonnees-syndic.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Compléter les informations du syndic #{{ signalement.reference }}{% endblock %}

{% block body %}

<main class="fr-container fr-pb-5w" id="content">
    <nav role="navigation" class="fr-breadcrumb fr-mb-1w fr-mt-2w" aria-label="Voir le fil d'Ariane">
        <button class="fr-breadcrumb__button" aria-expanded="false" aria-controls="breadcrumb-1">Voir le fil d'Ariane</button>
        <div class="fr-collapse" id="breadcrumb-1">
            <ol class="fr-breadcrumb__list">
                <li><a class="fr-breadcrumb__link" href="{{ path('front_suivi_signalement', {code: signalement.codeSuivi}) }}">Votre dossier #{{ signalement.reference }}</a></li>
                <li><a class="fr-breadcrumb__link" href="{{ path('front_suivi_signalement_dossier', {code: signalement.codeSuivi}) }}">Détails du dossier</a></li>
                <li><a class="fr-breadcrumb__link" aria-current="page">Compléter les informations du syndic</a></li>
            </ol>
        </div>
    </nav>
    {{ form_start(form, {'attr': {'id': 'form-usager-complete-dossier'}}) }}
    <div class="fr-grid-row fr-mt-3w">
        <div class="fr-col-12">
            <h1 class="title-blue-france">Compléter les informations</h1>
            <div class="fr-mb-3w">
                Complétez votre dossier en remplissant les champs ci-dessous. Un signalement complet permet une meilleure prise en charge du dossier !
                <br>
                Toutes les informations sont facultatives
            </div>
            {{ form_errors(form) }}
            <fieldset class="fr-fieldset">
                <legend class="fr-fieldset__legend">
                    <h3 class="fr-h5">Coordonnées du syndic</h3>
                </legend>
                <div class="fr-fieldset__element">
                    {{ form_row(form.denominationSyndic) }}
                </div>
                <div class="fr-fieldset__element">
                    {{ form_row(form.nomSyndic) }}
                </div>
                <div class="fr-fieldset__element">
                    {{ form_row(form.mailSyndic) }}
                </div>
                <div class="fr-fieldset__element">
                    {{ form_row(form.telSyndic) }}
                </div>
                <div class="fr-fieldset__element">
                    {{ form_row(form.telSyndicSecondaire) }}
                </div>
            </fieldset>
        </div>
        <div class="fr-col-6">
            <a href="{{ path('front_suivi_signalement_dossier', {code: signalement.codeSuivi}) }}" class="fr-btn fr-btn--secondary">Annuler</a>
        </div>
        <div class="fr-col-6 fr-text--right">
            {{ form_row(form.save) }}
        </div>
    </div>
    {{ form_end(form) }}
</main>
{% endblock %}
```

### Étape 5 : Configurer le listener de suivi

**Fichier** : `src/EventListener/SignalementUpdatedListener.php`

Ce listener détecte automatiquement les modifications effectuées par les usagers (rôle `ROLE_USAGER`) et crée un suivi détaillé.

```php
public const string EDIT_COORDONNEES_SYNDIC = 'coordonnees_syndic';

public const array EDIT_SECTIONS = [
    // ... autres sections
    self::EDIT_COORDONNEES_SYNDIC => [
        'label' => 'Les coordonnées du syndic',
        'fields' => [
            'denominationSyndic' => 'Dénomination',
            'nomSyndic' => 'Nom',
            'mailSyndic' => 'E-mail',
            'telSyndic' => 'Téléphone',
            'telSyndicSecondaire' => 'Téléphone secondaire',
        ],
    ],
];
```

### Fonctionnement du listener

Le listener :
1. Détecte automatiquement les modifications de l'entité `Signalement`
2. Compare les valeurs anciennes et nouvelles pour chaque champ déclaré
3. Crée un suivi détaillé uniquement pour les modifications des usagers
4. Liste les champs modifiés avec leurs nouvelles valeurs

### Sections disponibles

| Section | FormType | Route | Template |
|---------|----------|-------|----------|
| Adresse logement | `AdresseLogementType` | `front_suivi_signalement_complete_adresse_logement` | `edit-signalement/adresse-logement.html.twig` |
| Coordonnées bailleur | `CoordonneesBailleurType` | `front_suivi_signalement_complete_bailleur` | `edit-signalement/coordonnees-bailleur.html.twig` |
| Coordonnées agence | `CoordonneesAgenceType` | `front_suivi_signalement_complete_agence` | `edit-signalement/coordonnees-agence.html.twig` |
| Coordonnées syndic | `CoordonneesSyndicType` | `front_suivi_signalement_complete_syndic` | `edit-signalement/coordonnees-syndic.html.twig` |
| Informations assurance | `ProcedureAssuranceType` | `front_suivi_signalement_complete_assurance` | `edit-signalement/procedure-assurance.html.twig` |
| Situation foyer | `UsagerSituationFoyerType` | `front_suivi_signalement_complete_situation_foyer` | `edit-signalement/situation-foyer.html.twig` |

### Notes importantes

- Les usagers ne peuvent **pas** éditer leurs coordonnées de déclarant/tiers. Seul l'affichage est disponible.
- Chaque modification génère automatiquement un suivi dans l'historique du signalement
- Le suivi liste précisément les champs modifiés et leurs nouvelles valeurs
- Les validations doivent être cohérentes avec celles du back-office

---

## 6. API (Création et liste)

**Technologie** : PHP

### Étape 1 : Ajouter les propriétés dans le DTO de requête

**Fichier** : `src/Dto/Api/Request/SignalementRequest.php`

```php
#[OA\Property(
    description: 'Dénomination du syndic.',
    example: 'Syndic Pro',
)]
#[Assert\Length(max: 255)]
public ?string $denominationSyndic = null;

#[OA\Property(
    description: 'Nom de famille du contact du syndic.',
    example: 'Dupont',
)]
#[Assert\Length(max: 255)]
public ?string $nomSyndic = null;

#[OA\Property(
    description: 'E-mail du contact du syndic.',
    example: 'contact@syndicpro.com',
)]
#[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du contact du syndic n\'est pas valide.')]
#[Assert\Length(max: 255)]
public ?string $mailSyndic = null;

#[OA\Property(
    description: 'Téléphone du contact du syndic.',
    example: '0639988822',
)]
#[AppAssert\TelephoneFormat]
public ?string $telSyndic = null;

#[OA\Property(
    description: 'Téléphone secondaire du contact du syndic.',
    example: '0139988823',
)]
#[AppAssert\TelephoneFormat]
public ?string $telSyndicSecondaire = null;
```

### Étape 2 : Mapper les champs dans le Factory

**Fichier** : `src/Service/Signalement/SignalementApiFactory.php`

```php
public function createFromSignalementRequest(SignalementRequest $request): Signalement
{
    // ... code existant

    $signalement->setDenominationSyndic($request->denominationSyndic);
    $signalement->setNomSyndic($request->nomSyndic);
    $signalement->setMailSyndic($request->mailSyndic);
    $signalement->setTelSyndic($request->telSyndic);
    $signalement->setTelSyndicSecondaire($request->telSyndicSecondaire);

    // ... suite du code
}
```

### Étape 3 : Mettre à jour l'exemple dans le contrôleur (optionnel)

**Fichier** : `src/Controller/Api/SignalementCreateController.php`

Ajouter les champs dans l'exemple OpenAPI si pertinent pour la documentation.

### Étape 4 : Ajouter dans la réponse liste (si nécessaire)

**Fichier** : `src/Controller/Api/SignalementListController.php`

Si le champ doit être retourné dans la liste des signalements.

---

## 7. Export CSV (Optionnel)

**Technologie** : PHP

**⚠️ Attention** : Ne pas ajouter systématiquement tous les champs dans l'export CSV. Vérifier le besoin métier.

### Fichiers à modifier

1. **DTO d'export** : `src/Dto/SignalementExport.php`
   - Ajouter la propriété

2. **Factory** : `src/Factory/SignalementExportFactory.php`
   - Mapper le champ de l'entité vers le DTO

3. **Query** : `src/Repository/Query/SignalementList/ExportIterableQuery.php`
   - Ajouter le champ dans la requête si nécessaire

4. **Headers** : `src/Service/Signalement/Export/SignalementExportHeader.php`
   - Définir le libellé de la colonne

5. **Colonnes sélectionnables** : `src/Service/Signalement/Export/SignalementExportSelectableColumns.php`
   - Ajouter dans les colonnes disponibles à la sélection

---

## 8. Export PDF

**Technologie** : Twig

**Fichier** : `templates/pdf/signalement.html.twig`

### Exemple : Ajout de la section Syndic

```twig
{% if signalement|display_signalement_info_syndic %}
<tr>
    <td>
        <h3 style="margin-bottom: 0">Informations sur le syndic</h3>
        <ul style="list-style:none; margin-left:0px;padding-left:0px;">
            <li>
                <b>Dénomination :</b> {{ signalement.denominationSyndic }}<br>
                <b>Nom :</b> {{ signalement.nomSyndic ?? 'N/R' }}
            </li>
            <li><b>Courriel :</b> {{ signalement.mailSyndic ?? 'N/R' }}</li>
            <li><b>Tel. :</b> {{ signalement.telSyndic ? signalement.telSyndicDecoded|phone : 'N/R' }}</li>
            <li><b>Tel. sec. :</b> {{ signalement.telSyndicSecondaire ? signalement.telSyndicSecondaireDecoded|phone : 'N/R' }}</li>
        </ul>
    </td>
</tr>
{% endif %}
```

### Bonnes pratiques

- Utiliser des conditions pour n'afficher que les sections avec des données
- Utiliser `?? 'N/R'` pour les champs optionnels
- Utiliser les filtres Twig appropriés (`phone`, `date`, etc.)
- Respecter la mise en forme existante (inline CSS pour le PDF)

---

## 9. Listener de suivi automatique

**Fichier** : `src/EventListener/SignalementUpdatedListener.php`

Le listener permet de tracer automatiquement toutes les modifications effectuées par les usagers.

### Configuration

```php
public const string EDIT_COORDONNEES_SYNDIC = 'coordonnees_syndic';

public const array EDIT_SECTIONS = [
    self::EDIT_COORDONNEES_SYNDIC => [
        'label' => 'Les coordonnées du syndic',
        'fields' => [
            'denominationSyndic' => 'Dénomination',
            'nomSyndic' => 'Nom',
            'mailSyndic' => 'E-mail',
            'telSyndic' => 'Téléphone',
            'telSyndicSecondaire' => 'Téléphone secondaire',
        ],
    ],
];
```

### Fonctionnement

1. Le listener s'exécute lors de la mise à jour d'un signalement
2. Il vérifie si l'utilisateur a le rôle `ROLE_USAGER`
3. Pour chaque champ déclaré dans `EDIT_SECTIONS`, il compare l'ancienne et la nouvelle valeur
4. Si des changements sont détectés, il crée un suivi avec :
   - Le libellé de la section modifiée
   - La liste des champs modifiés avec leurs nouvelles valeurs

### Types de champs supportés

- **Champs simples** : string, int, bool, DateTimeImmutable
- **Champs JSON** : propriétés des objets JSON (ex: `informationProcedure.info_procedure_bail_moyen`)

### Exemple de suivi généré

```
Les coordonnées du syndic ont été modifiées par Jean Dupont :
- Dénomination : Syndic Pro
- E-mail : contact@syndicpro.com
- Téléphone : 06 39 98 88 22
```

---

## Checklist complète

Lors de l'ajout d'un nouveau champ dans l'entité Signalement :

- [ ] **Entité** : Ajouter le champ avec getter/setter dans `src/Entity/Signalement.php`
- [ ] **Migration** : Créer la migration Doctrine
- [ ] **Formulaire usagers (Vue.js)** : ⚠️ Demander confirmation avant de modifier
- [ ] **Formulaire pro (création)** : Ajouter dans le FormType et le template
- [ ] **Formulaire services secours** : Vérifier si nécessaire
- [ ] **Formulaire pro (édition)** : DTO + Manager + Contrôleur + Templates + Intégration
- [ ] **Formulaire usagers (édition)** : FormType + Contrôleur + Templates
- [ ] **Listener de suivi** : Ajouter dans `SignalementUpdatedListener.php`
- [ ] **API** : DTO Request + Factory + éventuellement liste
- [ ] **Export CSV** : Si pertinent métier
- [ ] **Export PDF** : Ajouter dans le template
- [ ] **Tests** : Tester dans tous les contextes

---

## Exemple complet : Champs Syndic

Pour une référence complète, voir l'implémentation des champs Syndic :
- `denominationSyndic`
- `nomSyndic`
- `mailSyndic`
- `telSyndic`
- `telSyndicSecondaire`

Ces champs ont été ajoutés dans tous les contextes et peuvent servir de modèle pour de futurs ajouts.
