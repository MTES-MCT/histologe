<?php

namespace App\Form;

use App\Form\Type\TerritoryChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SuiviSummariesType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('territory', TerritoryChoiceType::class)

            ->add('count', NumberType::class, [
                'data' => 300,
                'label' => 'Nombre de signalements',
                'required' => true,
            ])

            ->add('prompt', TextareaType::class, [
                'data' => 'Tu es un analyste de haute qualité. Ton travail est de résumer en français en quelques mots uniquement, le contenu du texte suivant pour que n\'importe qui puisse savoir l\'essence de son propos.',
                'attr' => [
                    'rows' => 5,
                ],
                'label' => 'Prompt',
                'required' => true,
            ])
            ->add('promptRole', ChoiceType::class, [
                'choices' => [
                    'System' => 'system',
                    'User' => 'user',
                ],
                'label' => 'Rôle du prompt',
            ])
            ->add('temperature', NumberType::class, [
                'data' => 0.7,
                'label' => 'Température (0.0 - 2.0)',
                'required' => true,
                'constraints' => [
                    new Assert\Range([
                        'min' => 0,
                        'max' => 2,
                    ]),
                ],
            ])

            // Commented languages are listed in Albert doc, but don't work when used
            ->add('model', ChoiceType::class, [
                'choices' => [
                    'albert-large' => 'albert-large',
                    'albert-small' => 'albert-small',
                    'neuralmagic/Meta-Llama-3.1-70B-Instruct-FP8' => 'neuralmagic/Meta-Llama-3.1-70B-Instruct-FP8',
                ],
                'placeholder' => 'Choisissez un modèle de langage',
                'multiple' => false,
                'expanded' => false,
                'label' => 'Modèle de langage',
            ])

            ->add('querySignalement', ChoiceType::class, [
                'choices' => [
                    'reponse-usager',
                    'dernier-suivi-20-jours',
                ],
                'choice_label' => function ($choice) {
                    switch ($choice) {
                        case 'reponse-usager':
                            return 'Relancés automatiquement, dernier suivi de type usager';
                        case 'dernier-suivi-20-jours':
                            return 'Dernier suivi partenaire, sans autre suivi depuis +20 jours';
                        default:
                            return '';
                    }
                },
                'placeholder' => 'Choisissez un type de signalements à extraire',
                'multiple' => false,
                'expanded' => false,
                'label' => 'Type de signalements',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Exporter le fichier',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
    }
}
