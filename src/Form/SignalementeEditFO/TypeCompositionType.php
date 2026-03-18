<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Enum\EtageType;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TypeCompositionType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();

        $typeCompositionLogement = $signalement->getTypeCompositionLogement();

        $natureAutrePrecision = $typeCompositionLogement?->getTypeLogementNatureAutrePrecision();
        $appartementEtage = EtageType::tryFrom($typeCompositionLogement?->getTypeLogementAppartementEtage());
        $avecFenetres = $typeCompositionLogement?->getTypeLogementAppartementAvecFenetres();
        $pieceUnique = $typeCompositionLogement?->getCompositionLogementPieceUnique();
        $nbPieces = $typeCompositionLogement?->getCompositionLogementNbPieces();
        $pieceAVivre9m = $typeCompositionLogement?->getTypeLogementCommoditesPieceAVivre9m();
        $cuisine = $typeCompositionLogement?->getTypeLogementCommoditesCuisine();
        $cuisineCollective = $typeCompositionLogement?->getTypeLogementCommoditesCuisineCollective();
        $salleDeBain = $typeCompositionLogement?->getTypeLogementCommoditesSalleDeBain();
        $salleDeBainCollective = $typeCompositionLogement?->getTypeLogementCommoditesSalleDeBainCollective();
        $wc = $typeCompositionLogement?->getTypeLogementCommoditesWc();
        $wcCollective = $typeCompositionLogement?->getTypeLogementCommoditesWcCollective();
        $wcCuisine = $typeCompositionLogement?->getTypeLogementCommoditesWcCuisine();

        $builder
            ->add('natureLogement', ChoiceType::class, [
                'label' => 'Nature du logement <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Appartement' => 'appartement',
                    'Maison' => 'maison',
                    'Autre' => 'autre',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Sélectionnez une option',
                'data' => $signalement->getNatureLogement(),
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer la nature du logement.',
                    ),
                ],
            ])
            ->add('natureAutrePrecision', TextType::class, [
                'label' => 'Précision sur la nature (facultatif)',
                'required' => false,
                'mapped' => false,
                'data' => $natureAutrePrecision,
            ])
            ->add('appartementEtage', EnumType::class, [
                'label' => 'Localisation de l\'appartement',
                'class' => EtageType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $appartementEtage,
            ])
            ->add('appartementAvecFenetres', ChoiceType::class, [
                'label' => 'Avec fenêtres <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $avecFenetres,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si le logement a des fenêtres.',
                    ),
                ],
            ])
            ->add('superficie', NumberType::class, [
                'label' => 'Superficie en m² <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : saisir un nombre',
                'required' => false,
                'data' => $signalement->getSuperficie(),
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer la superficie.',
                    ),
                ],
            ])
            ->add('pieceUnique', ChoiceType::class, [
                'label' => 'Une seule ou plusieurs pièces ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Pièce unique' => 'piece_unique',
                    'Plusieurs pièces' => 'plusieurs_pieces',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $pieceUnique,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si le logement est une pièce unique.',
                    ),
                ],
            ])
            ->add('nbPieces', NumberType::class, [
                'label' => 'Nombre de pièces à vivre <span class="text-required">*</span>',
                'label_html' => true,
                'help' => 'Format attendu : saisir un nombre entier',
                'required' => false,
                'mapped' => false,
                'data' => $nbPieces,
                'constraints' => [
                    new Assert\Regex(
                        pattern: '/^\d+$/',
                        message: 'Veuillez saisir un nombre entier.',
                    ),
                ],
            ])
            ->add('pieceAVivre9m', ChoiceType::class, [
                'label' => 'Est-ce qu\'au moins une des pièces à vivre (salon, chambre) fait 9m² ou plus ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Je ne sais pas' => 'nsp',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $pieceAVivre9m,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si une pièce fait 9m² ou plus.',
                    ),
                ],
            ])
            ->add('cuisine', ChoiceType::class, [
                'label' => 'Cuisine ou coin cuisine ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $cuisine,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si le logement a une cuisine.',
                    ),
                ],
            ])
            ->add('cuisineCollective', ChoiceType::class, [
                'label' => 'Accès à une cuisine collective ? (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $cuisineCollective,
            ])
            ->add('salleDeBain', ChoiceType::class, [
                'label' => 'Salle de bain, salle d\'eau avec douche ou baignoire ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $salleDeBain,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si le logement a une salle de bain.',
                    ),
                ],
            ])
            ->add('salleDeBainCollective', ChoiceType::class, [
                'label' => 'Accès à une salle de bain ou des douches collectives ? (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $salleDeBainCollective,
            ])
            ->add('wc', ChoiceType::class, [
                'label' => 'Toilettes (WC) ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $wc,
                'constraints' => [
                    new Assert\NotNull(
                        message: 'Veuillez indiquer si le logement a des toilettes.',
                    ),
                ],
            ])
            ->add('wcCollective', ChoiceType::class, [
                'label' => 'Accès à des toilettes (WC) collectives ? (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $wcCollective,
            ])
            ->add('wcCuisine', ChoiceType::class, [
                'label' => 'Toilettes (WC) et cuisine dans la même pièce ? (facultatif)',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'mapped' => false,
                'data' => $wcCuisine,
            ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'Envoyer',
            'attr' => [
                'class' => 'fr-btn--primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}
