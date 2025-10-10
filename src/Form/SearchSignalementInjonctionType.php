<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchSignalementInjonction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchSignalementInjonctionType extends AbstractType
{
    private bool $isAdmin = false;
    /**
     * @var array<string, string>
     */
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security,
    ) {
        $this->roleChoices = User::ROLES;
        unset($this->roleChoices['Usager']);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        } else {
            unset($this->roleChoices['Super Admin']);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->isAdmin) {
            $builder->add('territoire', TerritoryChoiceType::class);
        }

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre croissant' => 's.id-ASC',
                'Ordre décroissant' => 's.id-DESC',
                'Ordre ville alphabétique (A -> Z)' => 's.villeOccupant-ASC',
                'Ordre ville alphabétique inversé (Z -> A)' => 's.villeOccupant-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 's.id-DESC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchSignalementInjonction::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-signalement-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
