<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Service\ListFilters\SearchInterconnexion;
use App\Service\ListFilters\SearchPartner;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchInterconnexionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $partnerRepository = $options['partner_repository'];
        $territoryRepository = $options['territory_repository'];
        $user = $options['user'];
        $territory = null;
        if (isset($options['request'])) {
            $territoryId = $options['request']->get('territory') ?? $options['request']->query->get('territory') ?? $options['request']->request->get('territory');
            if ($territoryId) {
                $territory = $territoryRepository->find($territoryId);
            }
        }
        if (!$territory && isset($options['data']) && $options['data'] instanceof SearchInterconnexion) {
            $territory = $options['data']->getTerritory();
            if ($territory instanceof Territory) {
                // ok
            } elseif (is_int($territory) && $territory > 0) {
                $territory = $territoryRepository->find($territory);
            } else {
                $territory = null;
            }
        }
        $searchPartner = new SearchPartner($user);
        $searchPartner->setIsOnlyInterconnected(true);
        if ($territory) {
            $searchPartner->setTerritoire($territory);
        }
        $partners = $partnerRepository->getPartners(1000, $searchPartner);
        $partnerChoices = [];
        foreach ($partners as $partner) {
            $partnerChoices[] = $partner;
        }



        $builder->add('territory', EntityType::class, [
            'class' => Territory::class,
            'query_builder' => function (TerritoryRepository $tr) {
                return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'choice_label' => function (Territory $territory) {
                return $territory->getZip().' - '.$territory->getName();
            },
            'required' => false,
            'placeholder' => 'Tous les territoires',
            'label' => 'Territoire',
        ]);

        $builder->add('partner', EntityType::class, [
            'class' => Partner::class,
            'choices' => $partnerChoices,
            'choice_label' => 'nom',
            'required' => false,
            'label' => 'Partenaire',
            'placeholder' => 'Tous les partenaires',
        ]);
        $builder->add('status', ChoiceType::class, [
            'required' => false,
            'label' => 'Statut',
            'placeholder' => 'Tous les statuts',
            'choices' => [
                'Success' => 'success',
                'Fail' => 'failed',
            ],
        ]);
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Synchro la plus récente' => 'createdAt-DESC',
                'Synchro la plus ancienne' => 'createdAt-ASC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'createdAt-DESC',
        ]);
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchInterconnexion::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-interconnexion-form', 'class' => 'fr-p-4v bo-filter-form'],
            'partner_repository' => null,
            'territory_repository' => null,
            'user' => null,
            'request' => null,
        ]);
        $resolver->setRequired(['partner_repository', 'territory_repository', 'user', 'request']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
