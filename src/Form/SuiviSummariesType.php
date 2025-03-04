<?php

namespace App\Form;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SuiviSummariesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'query_builder' => function (TerritoryRepository $tr) {
                    return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
                },
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'placeholder' => 'Choisissez un territoire',
                'label' => 'Territoire',
                'required' => true,
            ])

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
                    'meta-llama/Meta-Llama-3.1-8B-Instruct' => 'meta-llama/Meta-Llama-3.1-8B-Instruct',
                    // 'mistralai/Mixtral-8x7B-Instruct-v0.1' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
                    'AgentPublic/llama3-instruct-8b' => 'AgentPublic/llama3-instruct-8b',
                    // 'BAAI/bge-m3' => 'BAAI/bge-m3',
                    'AgentPublic/llama3-instruct-guillaumetell' => 'AgentPublic/llama3-instruct-guillaumetell',
                    // 'intfloat/multilingual-e5-large' => 'intfloat/multilingual-e5-large',
                    'google/gemma-2-9b-it' => 'google/gemma-2-9b-it',
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
