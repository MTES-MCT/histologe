<?php

namespace App\Form;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Form\Type\SearchCheckboxEnumType;
use App\Form\Type\TerritoryChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoAffectationRuleType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territory', TerritoryChoiceType::class, [
                'disabled' => !$options['create'],
            ])
            ->add('partnerType', EnumType::class, [
                'class' => PartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'placeholder' => 'Sélectionner le type de partenaire concerné par cette règle',
                'label' => 'Type de partenaire',
                'help' => 'Si type Bailleur Social, le partenaire ne sera affecté au signalement que s\'il est rattaché à la même dénomination officielle de bailleur social.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('profileDeclarant', ChoiceType::class, [
                'choices' => ProfileDeclarant::getListWithGroup(),
                'choice_label' => function ($choice) {
                    return $choice;
                },
                'placeholder' => 'Choisissez un profil de déclarant parmi la liste ci-dessous.',
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Règle active' => 'ACTIVE',
                    'Règle archivée' => 'ARCHIVED',
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
            ])
            ->add('allocataire', ChoiceType::class, [
                'label' => 'Allocataire',
                'choices' => [
                    'Sélectionnez quels profils d\'allocataire sont concernés' => '',
                    'Tous' => 'all',
                    'Tous les allocataires' => 'oui',
                    'Tous les non-allocataires' => 'non',
                    'Allocataires CAF' => 'caf',
                    'Allocataires MSA' => 'msa',
                    'Situation allocataire inconnue' => 'nsp',
                ],
            ])
            ->add('inseeToInclude', TextType::class, [
                'label' => 'Code insee à inclure (facultatif)',
                'required' => false,
                'help' => 'Une liste de codes insee séparés par des virgules.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'empty_data' => '',
            ])
            ->add('inseeToExclude', TextType::class, [
                'label' => 'Codes insee à exclure (facultatif)',
                'required' => false,
                'help' => 'Une liste de codes insee séparés par des virgules.',
            ])
            ->add('partnerToExclude', TextType::class, [
                'label' => 'IDs partenaire à exclure (facultatif)',
                'required' => false,
                'help' => 'Une liste d\'id de partenaires séparés par des virgules.',
            ])
            ->add('proceduresSuspectees', SearchCheckboxEnumType::class, [
                'class' => Qualification::class,
                'choice_filter' => ChoiceList::filter(
                    $this,
                    function ($choice) {
                        return \in_array($choice, Qualification::getProcedureSuspecteeList()) ? $choice : false;
                    },
                    'competence'
                ),
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'label' => 'Procédures suspectées (facultatif)',
                'noselectionlabel' => 'Sélectionner une ou plusieurs procédures',
                'nochoiceslabel' => 'Aucune procédure disponible',
                'help' => 'Choisissez une ou plusieurs procédures parmi la liste ci-dessous.',
                'required' => false,
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
