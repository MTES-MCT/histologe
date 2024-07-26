<?php

namespace App\Form;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use App\Validator as AppAssert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de choisir un territoire.'),
                ],
            ])
            ->add('partnerType', EnumType::class, [
                'class' => PartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'disabled' => !$options['create'],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner le type de partenaire concerné par cette règle',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de choisir un partnerType.'),
                    new AppAssert\ValidPartnerType(),
                ],
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
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de choisir un profil déclarant.'),
                    new AppAssert\ValidProfileDeclarant(),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
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
                'required' => true,
                'disabled' => true,
                'data' => 'ACTIVE',
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de choisir un status.'),
                    new Assert\Choice(
                        choices: [AutoAffectationRule::STATUS_ACTIVE, AutoAffectationRule::STATUS_ARCHIVED],
                        message: 'Choisissez une option valide: ACTIVE or ARCHIVED'
                    ),
                ],
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
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner le type de parc.'),
                    new Assert\Choice(
                        choices: ['all', 'prive', 'public', 'non_renseigne'],
                        message: 'Choisissez une option valide: all, non_renseigne, prive ou public'
                    ),
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
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner le profil d\'allocataire.'),
                    new Assert\Choice(
                        choices: ['all', 'non', 'oui', 'caf', 'msa'],
                        message: 'Choisissez une option valide: all, non, oui, caf ou msa'
                    ),
                ],
            ])
            ->add('inseeToInclude', TextType::class, [
                'label' => 'Code insee à inclure',
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => true,
                'help' => 'Valeurs possibles : "all", "partner_list" ou une liste de codes insee séparés par des virgules.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner les code insee des communes concernées.'),
                    new Assert\Length(max: 255),
                    new AppAssert\InseeToInclude(),
                ],
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
                'constraints' => [
                    new AppAssert\InseeToExclude(),
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
                $pattern = '/(\s*,*\s*)*,+(\s*,*\s*)*/';

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
