<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function __construct(private PartnerRepository $partnerRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        $territory = $user->getTerritory();

        $builder
            ->add('email', EmailType::class, [
                'disabled' => !$options['can_edit_email'],
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6',
                ], 'attr' => [
                    'class' => 'fr-input',
                ], 'label' => 'Adresse email',
                'required' => true,
            ])
            ->add('nom', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6',
                ], 'attr' => [
                    'class' => 'fr-input',
                ], 'label' => 'Nom',
                'required' => true,
            ])
            ->add('prenom', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6',
                ], 'attr' => [
                    'class' => 'fr-input',
                ], 'label' => 'Prénom',
                'required' => true,
            ]);

        $builder
        ->add('territory', EntityType::class, [
            'class' => Territory::class,
            'query_builder' => function (TerritoryRepository $tr) {
                return $tr->createQueryBuilder('t')->where('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'data' => !empty($territory) ? $territory : null,
            'choice_label' => 'name',
            'placeholder' => 'Aucun territoire',
            'attr' => [
                'class' => 'fr-select',
            ],
            'row_attr' => [
                'class' => 'fr-input-group',
            ],
            'label' => 'Territoire',
            'required' => false,
        ]);

        $formModifier = function (FormInterface $form, Territory $territory = null) {
            $partners = null === $territory ?
            $this->partnerRepository->findAllWithoutTerritory()
            : $this->partnerRepository->findAllList($territory);

            $form->add('partner', EntityType::class, [
                'class' => Partner::class,
                'choices' => $partners,
                'choice_label' => 'nom',
                'placeholder' => 'Aucun partenaire',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Partenaire',
                'required' => false,
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();

                $formModifier($event->getForm(), $data->getTerritory());
            }
        );

        $builder->get('territory')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $territory = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $territory);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'can_edit_email' => false,
            'attr' => [
                'id' => 'account_user',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ],
        ]);
    }
}
