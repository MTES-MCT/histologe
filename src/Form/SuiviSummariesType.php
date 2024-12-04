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
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Territoire',
                'required' => true,
            ])

            ->add('count', NumberType::class, [
                'data' => 300,
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label' => 'Nombre de signalements',
                'required' => true,
            ])

            ->add('prompt', TextareaType::class, [
                'data' => 'Tu es un analyste de haute qualité. Ton travail est de résumer en français en quelques mots uniquement, le contenu d\'un email pour que n\'importe qui puisse savoir l\'essence de son propos.',
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label' => 'Prompt',
                'required' => true,
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
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Choisissez un type de signalements à extraire',
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'fr-select',
                ],
                'label' => 'Type de signalements',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Exporter le fichier',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
    }
}
