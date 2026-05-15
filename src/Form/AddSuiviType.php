<?php

namespace App\Form;

use App\Entity\File;
use App\Entity\Suivi;
use App\Form\Type\SearchCheckboxType;
use App\Service\Files\FileListService;
use App\Validator\EmailFormatValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class AddSuiviType extends AbstractType
{
    public function __construct(
        private readonly FileListService $fileListService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $suivi = $builder->getData();
        $signalement = $suivi->getSignalement();
        $isUsagersNotNotifiable = EmailFormatValidator::isInvalidEmail($signalement->getMailDeclarant()) && EmailFormatValidator::isInvalidEmail($signalement->getMailOccupant());
        $isLogementVacant = $signalement->getIsLogementVacant();
        $isBailleurNotNotifiable = EmailFormatValidator::isInvalidEmail($signalement->getMailProprio());

        $builder->add('isVisibleForUsager', CheckboxType::class, [
            'label' => 'Usager (occupant, tiers déclarant)',
            'row_attr' => [
                'class' => $isUsagersNotNotifiable ? 'fr-hidden' : '',
            ],
            'required' => false,
            'disabled' => $isUsagersNotNotifiable || $isLogementVacant,
        ]);
        if ($signalement->getReferenceInjonction()) {
            $builder->add('isVisibleForBailleur', CheckboxType::class, [
                'label' => 'Bailleur',
                'row_attr' => [
                    'class' => $isBailleurNotNotifiable ? 'fr-hidden' : '',
                ],
                'required' => false,
                'disabled' => $isBailleurNotNotifiable,
            ]);
        }
        $builder->add('isVisibleForPartners', CheckboxType::class, [
            'label' => 'Partenaires affectés au dossier',
            'required' => false,
            'mapped' => false,
            'disabled' => true,
            'data' => true,
        ]);

        $builder->add('description', null, [
            'label' => 'Votre message',
            'help' => 'Décrivez la ou les action(s) menée(s). 10 caractères minimum <span class="fr-text-default--error">*</span>',
            'help_html' => true,
            'attr' => [
                'class' => 'editor',
            ],
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length([
                    'min' => 16, // on compte 16 pour une limite de 10 car le message est emglobé par <p></p> par l'éditeur de texte
                    'minMessage' => 'Le contenu du suivi doit contenir au moins 10 caractères.',
                ]),
            ],
        ]);
        $builder->add('files', SearchCheckboxType::class, [
            'class' => File::class,
            'choice_label' => 'title',
            'label' => 'Fichiers',
            'noselectionlabel' => 'Sélectionnez un ou plusieurs fichiers à joindre au suivi',
            'nochoiceslabel' => 'Aucun fichier disponible',
            'mapped' => false,
            'choices' => $this->fileListService->getFileChoicesForSignalement($signalement),
            'data' => $suivi->getSuiviFiles()->map(static fn ($suiviFile) => $suiviFile->getFile())->toArray(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Suivi::class,
        ]);
    }
}
