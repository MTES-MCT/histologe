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
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        // $territory = false;
        // if ($options['territory']) {
        //     $territory = $options['territory'];
        // } else {
        $territory = $user->getTerritory();
        // }

        // $partner = false;
        // if ($options['partner']) {
        //     $partner = $options['partner'];
        // } else {
        $partner = $user->getPartner();
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

        if (\in_array('ROLE_USER_PARTNER', $user->getRoles()) || \in_array('ROLE_ADMIN_PARTNER', $user->getRoles()) || \in_array('ROLE_ADMIN_TERRITORY', $user->getRoles()) || \in_array('ROLE_ADMIN', $user->getRoles())) {
            $builder
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'query_builder' => function (TerritoryRepository $tr) {
                    return $tr->createQueryBuilder('t')->orderBy('t.id', 'ASC');
                },
                'data' => !empty($territory) ? $territory : null,
                'disabled' => !$options['can_edit_territory'],
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Territoire',
                'required' => false,
            ])
            ->add('partner', EntityType::class, [
                'class' => Partner::class,
                'query_builder' => function (PartnerRepository $pr) {
                    return $pr->createQueryBuilder('p')->orderBy('p.id', 'ASC');
                },
                'data' => !empty($partner) ? $partner : null,
                'disabled' => !$options['can_edit_partner'],
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Partenaire',
                'required' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'can_edit_territory' => true,
            'can_edit_partner' => true,
            'can_edit_email' => false,
            'attr' => [
                'id' => 'front_contact',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ],
        ]);
    }
}
