<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\TerritoryChoiceType;
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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserType extends AbstractType
{
    public function __construct(
        private PartnerRepository $partnerRepository,
        private TerritoryRepository $territoryRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        $territory = $user->getPartners()->count() ? $user->getFirstTerritory() : null;

        $builder
            ->add('email', EmailType::class, [
                'disabled' => !$options['can_edit_email'],
                'row_attr' => [
                    'class' => 'fr-input-group fr-col-6',
                ], 'attr' => [
                    'class' => 'fr-input',
                ], 'label' => 'Adresse e-mail',
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

        $builder->add('territory', TerritoryChoiceType::class, [
            'mapped' => false,
            'data' => $territory,
            'attr' => [
                'class' => 'fr-select',
            ],
            'row_attr' => [
                'class' => 'fr-input-group',
            ],
        ]);
        $formModifier = function (FormInterface $form, ?Territory $territory = null) use ($user) {
            $partners = null === $territory ?
            $this->partnerRepository->findAllWithoutTerritory()
            : $this->partnerRepository->findAllList($territory);
            $form->add('tempPartner', EntityType::class, [
                'class' => Partner::class,
                'choices' => $partners,
                'data' => $user->getPartners()->count() ? $user->getPartners()->first() : null,
                'mapped' => false,
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier) {
            $data = $event->getData();
            $formModifier($event->getForm(), $data->getPartners()->count() ? $data->getFirstTerritory() : null);
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
            $formModifier($event->getForm(), $this->territoryRepository->find($event->getData()['territory']));
        });
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
            'constraints' => [
                new Assert\Callback([$this, 'validateTerritory']),
                new Assert\Callback([$this, 'validatePartner']),
            ],
        ]);
    }

    public function validateTerritory(mixed $value, ExecutionContextInterface $context): void
    {
        if ($value instanceof User) {
            $user = $value;
            $form = $context->getRoot();
            $territory = $form->get('territory')->getData();

            if ((null === $territory)
            && (\in_array('ROLE_USER_PARTNER', $user->getRoles())
            || \in_array('ROLE_ADMIN_PARTNER', $user->getRoles())
            || \in_array('ROLE_ADMIN_TERRITORY', $user->getRoles()))) {
                $context->addViolation('Le territoire doit être renseigné');
            }
        }
    }

    public function validatePartner(mixed $value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $tempPartner = $form->get('tempPartner')->getData();

        if (null === $tempPartner) {
            $context->addViolation('Le partenaire doit être renseigné');
        }
    }
}
