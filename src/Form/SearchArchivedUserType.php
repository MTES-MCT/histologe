<?php

namespace App\Form;

use App\Entity\Territory;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\ListFilters\SearchArchivedUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchArchivedUserType extends AbstractType
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryUser', SearchType::class, [
            'required' => false,
            'label' => 'Utilisateur',
            'attr' => ['placeholder' => 'Taper le nom ou l\'e-mail d\'un utilisateur'],
        ]);
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
            $territory = $builder->getData()->getTerritory() ? $this->territoryRepository->find($builder->getData()->getTerritory()) : null;
            $this->addPartnersField(
                $event->getForm(),
                $territory
            );
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if (isset($event->getData()['territory'])) {
                $territory = $event->getData()['territory'] ? $this->territoryRepository->find($event->getData()['territory']) : null;
                $this->addPartnersField(
                    $event->getForm(),
                    $territory
                );
            }
        });

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Nom (A -> Z)' => 'u.nom-ASC',
                'Nom inversé (Z -> A)' => 'u.nom-DESC',
                'Partenaire (A -> Z)' => 'p.nom-ASC',
                'Partenaire inversé (Z -> A)' => 'p.nom-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'u.nom-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    private function addPartnersField(FormInterface $builder, ?Territory $territory): void
    {
        $partners = $territory ? $this->partnerRepository->findBy(['territory' => $territory]) : $this->partnerRepository->findAll();
        $choicesPartners = [
            'Aucun' => 'none',
        ];
        foreach ($partners as $partner) {
            $choicesPartners[$partner->getNom()] = $partner->getId();
        }
        $builder->add('partner', ChoiceType::class, [
            'choices' => $choicesPartners,
            'required' => false,
            'placeholder' => 'Tous les partenaires',
            'label' => 'Partenaire',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchArchivedUser::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-archived-users-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
