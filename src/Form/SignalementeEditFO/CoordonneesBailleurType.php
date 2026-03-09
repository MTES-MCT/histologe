<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Enum\MoyenContact;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CoordonneesBailleurType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Signalement $signalement */
        $signalement = $builder->getData();
        $adresseCompleteProprio = mb_trim($signalement->getAdresseProprio().' '.$signalement->getCodePostalProprio().' '.$signalement->getVilleProprio());

        $isProprioAverti = null;
        $isProprioAvertiChoices = [
            'Oui' => true,
            'Non' => false,
        ];
        if (null === $signalement->getIsProprioAverti()) {
            $isProprioAverti = 'nsp';
            // On ajoute le choix "Ne sais pas" uniquement si la valeur est nulle, pour éviter de proposer ce choix si l'utilisateur a déjà renseigné une valeur
            $isProprioAvertiChoices['Je ne sais pas'] = 'nsp';
        } else {
            $isProprioAverti = $signalement->getIsProprioAverti();
        }
        $infoProcedureBailDate = $signalement->getProprioAvertiAt()
            ? $signalement->getProprioAvertiAt()->format('m/Y')
            : $signalement->getInformationProcedure()?->getInfoProcedureBailDate();

        if ($options['extended']) {
            $builder
                ->add('nomProprio', TextType::class, [
                    'label' => 'Nom',
                    'disabled' => $signalement->getIsLogementSocial() ? true : false,
                    'constraints' => $signalement->getIsLogementSocial() ? [] : [
                        new Assert\NotBlank(),
                    ],
                    'required' => false,
                ])
                ->add('prenomProprio', TextType::class, [
                    'label' => 'Prénom',
                    'required' => false,
                ])
                ->add('adresseCompleteProprio', null, [
                    'label' => false,
                    'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparaît pas, cliquez sur saisir une adresse manuellement.',
                    'mapped' => false,
                    'data' => $adresseCompleteProprio,
                    'attr' => [
                        'autocomplete' => 'off',
                        'data-fr-adresse-autocomplete' => 'true',
                        'data-autocomplete-query-selector' => '#form-usager-complete-dossier .fr-address-proprio-group',
                        'data-suffix' => 'proprio',
                    ],
                ])
                ->add('adresseProprio', null, [
                    'label' => 'Numéro et voie',
                    'attr' => [
                        'class' => 'manual-address manual-address-input',
                        'data-autocomplete-addresse-proprio' => 'true',
                    ],
                ])
                ->add('codePostalProprio', null, [
                    'label' => 'Code postal',
                    'required' => false,
                    'attr' => [
                        'class' => 'manual-address',
                        'data-autocomplete-codepostal-proprio' => 'true',
                    ],
                ])
                ->add('villeProprio', null, [
                    'label' => 'Ville',
                    'required' => false,
                    'attr' => [
                        'class' => 'manual-address',
                        'data-autocomplete-ville-proprio' => 'true',
                    ],
                ])
                ->add('telProprio', TextType::class, [
                    'label' => 'Téléphone principal',
                    'required' => false,
                ])
                ->add('telProprioSecondaire', TextType::class, [
                    'label' => 'Téléphone secondaire',
                    'required' => false,
                ])
                ->add('mailProprio', TextType::class, [
                    'label' => 'Adresse e-mail',
                    'required' => false,
                    'help' => 'Format attendu : nom@domaine.fr',
                ])
                ->add('isProprioAverti', ChoiceType::class, [
                    'label' => 'Est-ce que le propriétaire est averti ? <span class="text-required">*</span>',
                    'label_html' => true,
                    'choices' => $isProprioAvertiChoices,
                    'expanded' => true,
                    'multiple' => false,
                    'required' => false,
                    'placeholder' => false,
                    'mapped' => false,
                    'data' => $isProprioAverti,
                    'constraints' => [
                        new Assert\NotNull(
                            message: 'Veuillez déterminer si le propriétaire est averti.',
                        ),
                    ],
                ])
                ->add('infoProcedureBailMoyen', EnumType::class, [
                    'label' => 'Moyen de contact utilisé pour avertir le propriétaire',
                    'class' => MoyenContact::class,
                    'choice_label' => function ($choice) {
                        return $choice->label();
                    },
                    'placeholder' => 'Sélectionner un moyen de contact',
                    'required' => false,
                    'mapped' => false,
                    'data' => MoyenContact::tryFrom($signalement->getInformationProcedure()?->getInfoProcedureBailMoyen()),
                ])
                ->add('infoProcedureBailDate', TextType::class, [
                    'label' => 'Date d\'avertissement du propriétaire',
                    'help' => 'Format attendu : MM/YYYY',
                    'required' => false,
                    'mapped' => false,
                    'data' => $infoProcedureBailDate,
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => '/^(0[1-9]|1[0-2])\/\d{4}$/',
                            'message' => 'Le format de la date doit être MM/YYYY.',
                        ]),
                    ],
                ])
                ->add('infoProcedureBailReponse', TextareaType::class, [
                    'label' => 'Réponse du propriétaire',
                    'help' => 'Format attendu : 255 caractères maximum',
                    'required' => false,
                    'mapped' => false,
                    'data' => $signalement->getInformationProcedure()?->getInfoProcedureBailReponse(),
                    'constraints' => [
                        new Assert\Length([
                            'max' => 255,
                            'maxMessage' => 'La réponse du propriétaire ne peut pas dépasser {{ limit }} caractères.',
                        ]),
                    ],
                ])
            ;

            if ($signalement->getIsLogementSocial()) {
                $builder
                    ->add('infoProcedureBailNumero', TextType::class, [
                        'label' => 'Numéro de réclamation fourni par le bailleur',
                        'help' => 'Format attendu : 30 caractères maximum',
                        'required' => false,
                        'mapped' => false,
                        'data' => $signalement->getInformationProcedure()?->getInfoProcedureBailNumero(),
                        'constraints' => [
                            new Assert\Length([
                                'max' => 30,
                                'maxMessage' => 'Le numéro de réclamation ne peut pas dépasser {{ limit }} caractères.',
                            ]),
                        ],
                    ]);
            }
        } else {
            $builder
                ->add('mailProprio', TextType::class, [
                    'label' => 'Afin de fluidifier les échanges, merci de renseigner votre adresse e-mail.',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ]);
        }

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
            'extended' => false,
        ]);
    }
}
