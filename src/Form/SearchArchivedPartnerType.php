<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\ListFilters\SearchArchivedPartner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchArchivedPartnerType extends AbstractType
{
    private bool $isAdmin = false;
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security,
        private readonly TerritoryRepository $territoryRepository,
    ) {
        $this->roleChoices = User::ROLES;
        unset($this->roleChoices['Usager']);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        } else {
            unset($this->roleChoices['Super Admin']);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryArchivedPartner', SearchType::class, [
            'required' => false,
            'label' => 'Partenaire',
            'attr' => ['placeholder' => 'Taper le nom du partenaire'],
        ]);
        if ($this->isAdmin) {
            $territories = $this->territoryRepository->findAllList();
            $choicesTerritories = [
                'Aucun' => 'none',
            ];
            foreach ($territories as $territory) {
                $choicesTerritories[$territory->getZip().' - '.$territory->getName()] = $territory->getId();
            }
            $builder->add('territory', ChoiceType::class, [
                'choices' => $choicesTerritories,
                'required' => false,
                'placeholder' => 'Tous les territoires',
                'label' => 'Territoire',
            ]);
        }
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchArchivedPartner::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-archived-partner-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
