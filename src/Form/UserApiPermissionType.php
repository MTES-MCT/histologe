<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\UserApiPermission;
use App\Form\Type\TerritoryChoiceType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserApiPermissionType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territoryFilter', TerritoryChoiceType::class, [
                'label' => 'Préfiltrer les partenaires par territoire',
                'mapped' => false,
            ])
            ->add('partner', null, [
                'label' => 'Partenaire',
                'choice_label' => function (Partner $partner): string {
                    return $partner->getTerritory()->getZip().' - '.$partner->getNom();
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->select('p, t')
                        ->innerJoin('p.territory', 't')
                        ->where('p.isArchive = false')
                        ->orderBy('t.zip', 'ASC')
                        ->addOrderBy('p.nom', 'ASC');
                },
                'choice_attr' => function (Partner $partner) {
                    return ['data-territoryid' => $partner->getTerritory()->getId()];
                },
            ])
            ->add('territory', TerritoryChoiceType::class)
            ->add('partnerType', EnumType::class, [
                'class' => PartnerType::class,
                'label' => 'Type de partenaire',
                'required' => false,
                'placeholder' => 'Tous les types',
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserApiPermission::class,
        ]);
    }
}
