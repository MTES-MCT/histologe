<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementType extends AbstractType
{
    public const LINK_CHOICES = [
        'Proche' => 'PROCHE',
        'Professionnel' => 'PROFESSIONNEL',
        'Tuteur / Tutrice' => 'TUTEUR',
        'Voisin / Voisine' => 'VOISIN',
        'Autre' => 'AUTRE',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('details', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'minlength' => 10,
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Décrivez le ou les problème(s) rencontré(s)',
                'help' => 'Proposer une rapide description du ou des problème(s) en 10 caractères minimum.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('isProprioAverti', ChoiceType::class, [
                'choice_attr' => [
                    'class' => 'fr-radio',
                ],
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                    'Ne sais pas' => '',
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Avez-vous informé le propriétaire ou gestionnaire de ces nuisances ?',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('nbAdultes', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select',
                ],
                'choices' => [1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice) {
                        return $value.' Adulte';
                    } elseif ('4+' === $choice) {
                        return 'Plus de 4 Adultes';
                    }

                    return $value.' Adultes';
                },
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ], 'label' => "Nombre d'adultes (personnes majeures occupant le logement)",
                'placeholder' => '--- Selectionnez ---',
            ])
            ->add('nbEnfantsM6', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select',
                ],
                'choices' => ['0', 1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice) {
                        return $value.' Enfant';
                    } elseif ('4+' === $choice) {
                        return 'Plus de 4 Enfants';
                    }

                    return $value.' Enfants';
                },
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ], 'label' => "Nombre d'enfants de moins de 6 ans",
                'required' => false,
                'placeholder' => '--- Selectionnez ---',
            ])
            ->add('nbEnfantsP6', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select',
                ],
                'choices' => ['0', 1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice) {
                        return $value.' Enfant';
                    } elseif ('4+' === $choice) {
                        return 'Plus de 4 Enfants';
                    }

                    return $value.' Enfants';
                },
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ], 'label' => "Nombre d'enfants de plus de 6 ans",
                'required' => false,
                'placeholder' => '--- Selectionnez ---',
            ])
            ->add('natureLogement', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select',
                ],
                'choices' => [
                    'Maison' => 'maison',
                    'Appartement' => 'appartement',
                    'Autre' => 'autre',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'label' => 'Quelle est la nature du logement ?',
                'placeholder' => '--- Selectionnez ---',
            ])
            ->add('superficie', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '^[0-9]*$',
                    'maxlength' => '25',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-form-group fr-col-2',
                ],
                'label' => 'Quelle est la superficie du logement (en m²) ?',
                'required' => false,
            ])
            ->add('isAllocataire', ChoiceType::class, [
                'choices' => [
                    'CAF' => 'CAF',
                    'MSA' => 'MSA',
                    'Non' => 0,
                    'Ne sais pas' => '',
                ],
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    if ('Ne sais pas' === $key || 'Non' === $key) {
                        $attr['data-fr-toggle-hide'] = 'signalement-num-alloc-bloc';
                    } else {
                        $attr['data-fr-toggle-show'] = 'signalement-num-alloc-bloc';
                    }

                    return $attr;
                },
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Recevez-vous une allocation logement de la CAF ou de la MSA ?',
                'help' => "Le cas échéant, merci de renseigner votre numéro d'allocataire.",
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
                'placeholder' => false,
            ])
            ->add('numAllocataire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '25',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-form-group fr-col-2',
                ],
                'label' => "Numéro d'allocataire",
                'help' => "Merci de renseigner votre numéro d'allocataire tel qu'il apparait sur vos documents.",
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
            ])
            ->add('isLogementSocial', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                    'Ne sais pas' => '',
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Le logement est-il un logement social ?',
                'help' => 'Cette information nous aide à optimiser le temps de traitement de votre signalement.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('isPreavisDepart', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                    'Ne sais pas' => '',
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Avez-vous déposé un préavis de départ pour ce logement ?',
                'help' => 'Cette information nous aide à optimiser le temps de traitement de votre signalement.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('isRelogement', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0,
                    'Ne sais pas' => '',
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Avez-vous fait une demande de relogement ou de logement social ?',
                'help' => 'Cette information nous aide à optimiser le temps de traitement de votre signalement.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('nomOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '50',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "Nom de l'occupant",
            ])
            ->add('prenomOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '50',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "Prénom de l'occupant",
            ])
            ->add('telOccupant', TelType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '[0-9]{10}',
                    'minlength' => '10',
                    'maxlength' => '15',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "N° téléphone de l'occupant",
                'required' => false,
            ])
            ->add('telOccupantBis', TelType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '[0-9]{10}',
                    'minlength' => '10',
                    'maxlength' => '15',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "N° téléphone secondaire de l'occupant",
                'required' => false,
            ])
            ->add('mailOccupant', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '50',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "Courriel de l'occupant",
                'required' => false,
            ])
            ->add('adresseOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'data-fr-adresse-autocomplete' => 'true',
                    'maxlength' => '100',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Adresse du logement',
                'help' => "Commencez à entrer votre adresse et cliquez sur l'une des suggestions. Si vous ne trouvez pas votre adresse, entrez-la manuellement.",
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('cpOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '[0-9]{5}',
                    'maxlength' => '5',
                    'minlength' => '5',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Code postal du logement',
            ])
            ->add('villeOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '100',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Ville du logement',
            ])
            ->add('etageOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Etage',
                'required' => false,
            ])
            ->add('escalierOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Escalier',
                'required' => false,
            ])
            ->add('numAppartOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "N° d'appartement",
                'required' => false,
            ])
            ->add('adresseAutreOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Autre (ex: Residence, lieu-dit,...)',
                'required' => false,
            ])
            ->add('nomProprio', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label required',
                ],
                'label' => 'Nom ou raison sociale du propriétaire',
                'required' => true,
            ])
            ->add('adresseProprio', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                    'data-fr-adresse-autocomplete' => 'true',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Adresse du propriétaire',
                'help' => "Commencez à entrer votre adresse et cliquez sur l'une des suggestions. Si vous ne trouvez pas votre adresse, entrez-la manuellement.",
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
            ])
            ->add('telProprio', TelType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-mt-2w',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '[0-9]{10}',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'N° de téléphone du propriétaire',
                'required' => false,
            ])
            ->add('mailProprio', EmailType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Courriel du propriétaire',
                'required' => false,
            ])
            ->add('isNotOccupant', ChoiceType::class, [
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    'Oui' === $key ?
                        $attr = [
                            'data-fr-toggle-hide' => 'signalement-pas-occupant|signalement-consentement-tiers-bloc',
                            'data-fr-toggle-show' => 'signalement-occupant|signalement-infos-proprio|signalement-consentement-tiers-bloc',
                            'data-fr-toggle-unrequire' => 'signalement_telOccupantBis|signalement-consentement-tiers|signalement_adresseProprio|signalement_telProprio|signalement_mailProprio|signalement_etageOccupant|signalement_escalierOccupant|signalement_numAppartOccupant|signalement_adresseAutreOccupant|signalement_lienDeclarantOccupant_0',
                            'data-fr-toggle-require' => 'signalement_mailOccupant|signalement_telOccupant',
                        ]
                        :
                        $attr = [
                            'data-fr-toggle-show' => 'signalement-consentement-tiers-bloc|signalement-occupant|signalement-pas-occupant|signalement-infos-proprio',
                            'data-fr-toggle-unrequire' => 'signalement_telOccupantBis|signalement_adresseProprio|signalement_telProprio|signalement_mailProprio|signalement_nomProprio|signalement_structureDeclarant|signalement_mailOccupant|signalement_telOccupant|signalement_etageOccupant|signalement_escalierOccupant|signalement_numAppartOccupant|signalement_adresseAutreOccupant',
                        ];

                    return $attr;
                },
                'choices' => [
                    'Oui' => 0,
                    'Non' => 1,
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "Êtes-vous l'occupant du logement ?",
                'help' => "Si vous déposez ce signalement pour le compte de quelqu'un d'autre, merci de nous le faire savoir.",
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
            ])
            ->add('nomDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Nom déclarant',
                'required' => false,
            ])
            ->add('prenomDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Prénom déclarant',
                'required' => false,
            ])
            ->add('telDeclarant', TelType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => '[0-9]{10}',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'N° de téléphone déclarant',
                'required' => false,
            ])
            ->add('mailDeclarant', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input fr-fi-mail-line fr-input-wrap',
                    'maxlength' => '200',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Courriel déclarant',
                'required' => false,
            ])
            ->add('lienDeclarantOccupant', ChoiceType::class, [
                'choices' => self::LINK_CHOICES,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => "Lien avec l'occupant",
                'required' => false,
                'placeholder' => false,
            ])
            ->add('structureDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'maxlength' => '50',
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'row_attr' => [
                    'class' => 'fr-form-group',
                ],
                'label' => 'Structure déclarant',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'allow_file_upload' => true,
            'allow_extra_fields' => true,
            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => true,
            ],
        ]);
    }
}
