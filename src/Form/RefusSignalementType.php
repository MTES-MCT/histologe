<?php

namespace App\Form;

use App\Dto\RefusSignalement;
use App\Entity\Enum\MotifRefus;
use App\Entity\File;
use App\Form\Type\SearchCheckboxType;
use App\Service\FileListService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RefusSignalementType extends AbstractType
{
    public function __construct(
        private readonly FileListService $fileListService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $signalement = $builder->getData()->getSignalement();
        $builder
            ->add('motifRefus', EnumType::class, [
                'class' => MotifRefus::class,
                'choices' => [
                    MotifRefus::HORS_PDLHI,
                    MotifRefus::DOUBLON,
                    MotifRefus::AUTRE,
                ],
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'label' => 'Veuillez sélectionner le motif de refus <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'placeholder' => 'Sélectionner un motif',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Message à l\'usager <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'help' => 'Expliquez à l\'usager la raison du refus de son signalement <em>(10 caractères minimum)</em>',
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
                'noselectionlabel' => 'Sélectionner une ou plusieurs fichiers à joindre au message',
                'nochoiceslabel' => 'Aucun fichiers disponible',
                'choices' => $this->fileListService->getFileChoicesForSignalement($signalement),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RefusSignalement::class,
        ]);
    }
}
