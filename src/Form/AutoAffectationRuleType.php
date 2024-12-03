<?php

namespace App\Form;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoAffectationRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'query_builder' => function (TerritoryRepository $tr) {
                    return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
                },
                'disabled' => !$options['create'],
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Territoire',
            ])
            ->add('partnerType', EnumType::class, [
                'class' => PartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner le type de partenaire concerné par cette règle',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'label' => 'Type de partenaire',
            ])
            ->add('profileDeclarant', ChoiceType::class, [
                'choices' => ProfileDeclarant::getListWithGroup(),
                'choice_label' => function ($choice) {
                    return $choice;
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Choisissez un profil de déclarant parmi la liste ci-dessous.',
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'fr-select',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Règle active' => 'ACTIVE',
                    'Règle archivée' => 'ARCHIVED',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'attr' => [
                    'class' => 'fr-select',
                ],
                'disabled' => true,
                'data' => 'ACTIVE',
            ])
            ->add('parc', ChoiceType::class, [
                'label' => 'Parc de logements concerné',
                'choices' => [
                    'Sélectionnez un type de parc' => '',
                    'Tous les logements' => 'all',
                    'Parc privé' => 'prive',
                    'Parc public' => 'public',
                    'Parc non renseigné' => 'non_renseigne',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'attr' => [
                    'class' => 'fr-select',
                ],
            ])
            ->add('allocataire', ChoiceType::class, [
                'label' => 'Allocataire',
                'choices' => [
                    'Sélectionnez quels profils d\'allocataire sont concernés' => '',
                    'Tous' => 'all',
                    'Tous les allocataires' => 'oui',
                    'Tous les  non-allocataires' => 'non',
                    'Allocataires CAF' => 'caf',
                    'Allocataires MSA' => 'msa',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'attr' => [
                    'class' => 'fr-select',
                ],
            ])
            ->add('inseeToInclude', TextType::class, [
                'label' => 'Périmètre géographique à inclure',
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => true,
                'help' => 'Valeurs possibles : "all", "partner_list" (codes insee, et zones) ou une liste de codes insee séparés par des virgules.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'empty_data' => '',
            ])
            ->add('inseeToExclude', TextType::class, [
                'label' => 'Codes insee à exclure (facultatif)',
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'help' => 'Une liste de codes insee séparés par des virgules.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('partnerToExclude', TextType::class, [
                'label' => 'IDs partenaire à exclure (facultatif)',
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'help' => 'Une liste d\'id de partenaires séparés par des virgules.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
        ;
        $builder->get('inseeToExclude')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return null !== $tagsAsArray ? implode(',', $tagsAsArray) : null;
            },
            function ($tagsAsString) {
                // transform the string back to an array
                $pattern = '/\s*,\s*/';

                return null !== $tagsAsString ? preg_split($pattern, $tagsAsString, -1, \PREG_SPLIT_NO_EMPTY) : [];
            }
        ));
        $builder->get('partnerToExclude')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return null !== $tagsAsArray ? implode(',', $tagsAsArray) : null;
            },
            function ($tagsAsString) {
                // transform the string back to an array
                $pattern = '/\s*,\s*/';

                return null !== $tagsAsString ? preg_split($pattern, $tagsAsString, -1, \PREG_SPLIT_NO_EMPTY) : [];
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutoAffectationRule::class,
            'allow_extra_fields' => true,
            'territory' => null,
            'route' => null,
            'create' => true,
            'constraints' => [
            ],
        ]);
    }
}
