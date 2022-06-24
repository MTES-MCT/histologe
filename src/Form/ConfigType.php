<?php

namespace App\Form;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomTerritoire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('logotype', FileType::class, [
                'attr' => [
                    'class' => 'fr-upload',
                    'accept' => 'image/*'
                ],
                'row_attr' => [
                    'class' => 'fr-upload-group fr-mb-5v'
                ],
                'data_class' => null,
                'required' => false
            ])
            ->add('urlTerritoire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('nomDpo', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('mailDpo', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('adresseDpo', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('nomResponsable', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('mailResponsable', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ]
            ])
            ->add('emailReponse', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Courriel de réponse'
            ])
            ->add('telContact', TelType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Téléphone (page contact)',
                'required' => false
            ])
            ->add('trackingCode', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'rows' => 15
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Code de Tracking',
                'required' => false
            ])
            ->add('tagManagerCode', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'rows' => 10
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Code TagManager',
                'required' => false
            ])
            ->add('mailAr', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input editor'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Modèle "Accusé de réception signalement"',
                'required' => false
            ])
            ->add('mailValidation', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input editor'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ], 'label' => 'Modèle "Validation signalement"',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
            'attr' => [
                'id' => 'config-form'
            ]
        ]);
    }
}
