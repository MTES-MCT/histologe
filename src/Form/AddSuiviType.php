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
        $isNotNotifiable = EmailFormatValidator::isInvalidEmail($signalement->getMailDeclarant()) && EmailFormatValidator::isInvalidEmail($signalement->getMailOccupant());
        $isLogementVacant = $signalement->getIsLogementVacant();

        $builder->add('isVisibleForUsager', CheckboxType::class, [
            'label' => 'Usager (occupant, tiers déclarant)',
            'row_attr' => [
                'class' => $isNotNotifiable ? 'fr-hidden' : '',
            ],
            'required' => false,
            'disabled' => $isNotNotifiable || $isLogementVacant,
        ]);
        if ($signalement->getReferenceInjonction()) {
            $builder->add('isVisibleForBailleur', CheckboxType::class, [
                'label' => 'Bailleur',
                'required' => false,
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
                    'min' => 10,
                    'minMessage' => 'Le contenu du suivi doit contenir au moins {{ limit }} caractères.',
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
