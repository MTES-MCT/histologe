<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecours;
use App\Dto\ServiceSecours\FormServiceSecoursStep4;
use App\Form\Type\PhoneType;
use App\Validator\TelephoneFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ServiceSecoursStep4Type extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('isBailleurAverti', ChoiceType::class, [
            'label' => 'Bailleur averti <span class="text-required">*</span>',
            'label_html' => true,
            'required' => false,
            'placeholder' => false,
            'expanded' => true,
            'choices' => [
                'Oui' => 'oui',
                'Non' => 'non',
                'Indeterminé' => 'indetermine',
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event): void {
            $form = $event->getForm();
            $rootData = $form->getRoot()->getData();

            if ($rootData instanceof FormServiceSecours) {
                if ('oui' === $rootData->step2->isLogementSocial) {
                    $form->add('denominationProprio', null, [
                        'label' => 'Dénomination du bailleur',
                        'help' => 'Format attendu : Tappez le nom du bailleur et sélectionnez-le dans la liste.',
                        'attr' => [
                            'data-autocomplete-bailleur-url' => $this->urlGenerator->generate('app_bailleur', ['inseecode' => $rootData->step2->inseeOccupant]),
                        ],
                    ]);
                } else {
                    $form->add('nomProprio', null, ['label' => 'Nom du bailleur']);
                    $form->add('prenomProprio', null, ['label' => 'Prénom du bailleur']);
                }
            }
        });
        $builder->add('mailProprio', TextType::class, [
            'label' => 'Adresse e-mail',
            'help' => 'Format attendu : nom@domaine.fr',
        ]);
        $builder->add('telProprio', PhoneType::class, [
            'label' => 'Téléphone',
            'constraints' => [
                new TelephoneFormat([
                    'message' => 'Le numéro de téléphone n\'est pas valide.',
                ]),
            ],
        ]);
        $builder->add('denominationSyndic', null, ['label' => 'Dénomination du syndic']);
        $builder->add('nomSyndic', null, ['label' => 'Nom du ou de la représentante']);
        $builder->add('mailSyndic', TextType::class, [
            'label' => 'Adresse e-mail',
            'help' => 'Format attendu : nom@domaine.fr',
            'required' => false,
        ]);
        $builder->add('telSyndic', PhoneType::class, [
            'label' => 'Téléphone',
            'constraints' => [
                new TelephoneFormat([
                    'message' => 'Le numéro de téléphone n\'est pas valide.',
                ]),
            ],
        ]);
        $builder->add('telSyndicSecondaire', PhoneType::class, [
            'label' => 'Téléphone secondaire',
            'required' => false,
            'constraints' => [
                new TelephoneFormat([
                    'message' => 'Le numéro de téléphone n\'est pas valide.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep4::class,
        ]);
    }
}
