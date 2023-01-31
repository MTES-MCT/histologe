<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Psr\Log\LoggerInterface;
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
    private $partnerRepository;
    private $logger;

    public function __construct(PartnerRepository $partnerRepository, LoggerInterface $logger)
    {
        $this->partnerRepository = $partnerRepository;
        $this->logger = $logger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        // $territory = false;
        // if ($options['territory']) {
        //     $territory = $options['territory'];
        // } else {
        // $territory = null;
        $territory = $user->getTerritory();
        // }

        // $partner = false;
        // if ($options['partner']) {
        //     $partner = $options['partner'];
        // } else {
        // $partner = $user->getPartner();
        // }

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
                return $tr->createQueryBuilder('t')->select('PARTIAL t.{id,name,zip}')->where('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'data' => !empty($territory) ? $territory : null,
            'disabled' => !$options['can_edit_territory'],
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
            $this->logger->info(sprintf('dans le formmodifier  territory =  %s ', $territory));
            $partners = null === $territory ? $this->partnerRepository->findAllWithoutTerritory() : $this->partnerRepository->findAllList($territory);

            $form->add('partner', EntityType::class, [
                'class' => Partner::class,
                'choices' => $partners,
                // 'disabled' => $territory === null,
            //     'disabled' => !$options['can_edit_partner'],
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
                $this->logger->info(sprintf('dans le PRE_SET_DATA  territory =  %s ', $data->getTerritory()));

                $formModifier($event->getForm(), $data->getTerritory());
            }
        );

        $builder->get('territory')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $territory = $event->getForm()->getData();

                $this->logger->info(sprintf('dans le POST_SUBMIT  territory =  %s ', $territory));
                $formModifier($event->getForm()->getParent(), $territory);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'can_edit_territory' => true,
            'can_edit_partner' => true,
            'can_edit_email' => false,
            'attr' => [
                'id' => 'account_user',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ],
        ]);
    }
}
