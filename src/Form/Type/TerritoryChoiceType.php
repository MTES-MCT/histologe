<?php

namespace App\Form\Type;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoryChoiceType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Territory::class,
            'choice_label' => fn (Territory $territory) => $territory->getZipAndName(),
            'placeholder' => 'Tous les territoires',
            'required' => false,
            'label' => 'Territoire',
            'query_builder' => function (TerritoryRepository $territoryRepository) {
                /** @var User $user */
                $user = $this->security->getUser();
                $qb = $territoryRepository->createQueryBuilder('t')
                    ->andWhere('t.isActive = 1')
                    ->orderBy('t.id', 'ASC');

                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    $territoryIds = array_keys($user->getPartnersTerritories());
                    if (!empty($territoryIds)) {
                        $qb->andWhere('t.id IN (:territoryIds)')
                           ->setParameter('territoryIds', $territoryIds);
                    }
                }

                return $qb;
            },
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
