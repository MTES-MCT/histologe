<?php

namespace App\Form;

use App\Entity\File;
use App\Entity\Suivi;
use App\Form\Type\SearchCheckboxType;
use App\Service\FileListService;
use App\Validator\EmailFormatValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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

        $builder->add('isPublic', null, [
            'label' => 'En cochant cette case, le suivi sera envoyé à l\'usager',
            'row_attr' => [
                'class' => $isNotNotifiable ? 'fr-hidden' : 'fr-toggle',
            ],
            'label_attr' => [
                'class' => 'fr-toggle__label',
            ],
            'attr' => [
                'class' => 'fr-toggle__input',
            ],
            'required' => false,
            'disabled' => $isNotNotifiable,
        ]);
        $builder->add('description', null, [
            'label' => false,
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
            'data' => $suivi->getSuiviFiles()->map(fn ($suiviFile) => $suiviFile->getFile())->toArray(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Suivi::class,
        ]);
    }
}
