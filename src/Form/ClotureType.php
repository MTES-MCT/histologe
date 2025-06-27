<?php

namespace App\Form;

use App\Dto\SignalementAffectationClose;
use App\Entity\Enum\MotifCloture;
use App\Entity\File;
use App\Form\Type\SearchCheckboxType;
use App\Service\FileListService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClotureType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly FileListService $fileListService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $signalement = $builder->getData()->getSignalement();
        $builder
            ->add('motifCloture', EnumType::class, [
                'class' => MotifCloture::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'label' => 'Motif <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'placeholder' => 'Sélectionnez un motif',
                'help' => 'Choisissez un motif de cloture parmi la liste ci-dessous.',
            ])
            ->add('type', HiddenType::class)
            ->add('description', TextareaType::class, [
                'label' => 'Détails de la clôture <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'help' => 'Précisez le contexte et les raisons de la clôture <em>(10 caractères minimum)</em>',
                'help_html' => true,
                'attr' => [
                    'class' => 'editor',
                ],
                'required' => false,
            ])
            ->add('files', SearchCheckboxType::class, [
                'class' => File::class,
                'choice_label' => 'title',
                'label' => 'Fichiers',
                'noselectionlabel' => 'Sélectionnez un ou plusieurs fichiers à joindre au suivi de clôture',
                'nochoiceslabel' => 'Aucun fichier disponible',
                'choices' => $this->fileListService->getFileChoicesForSignalement($signalement),
            ]);
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $builder->add('isPublic', ChoiceType::class, [
                'label' => 'Notifier l\'usager par e-mail de la clôture du signalement.',
                'expanded' => true,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SignalementAffectationClose::class,
        ]);
    }
}
