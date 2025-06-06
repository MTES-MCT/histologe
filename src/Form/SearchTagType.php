<?php

namespace App\Form;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\ListFilters\SearchTag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchTagType extends AbstractType
{
    private bool $isAdmin = false;
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
        $builder->add('queryTag', SearchType::class, [
            'required' => false,
            'label' => 'Etiquette',
            'attr' => ['placeholder' => 'Taper le nom de l\'étiquette'],
        ]);
        if ($this->isAdmin) {
            $builder->add('territory', EntityType::class, [
                'class' => Territory::class,
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'required' => false,
                'placeholder' => 'Tous les territoires',
                'label' => 'Territoire',
            ]);
        }

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 't.label-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 't.label-DESC',
                'Ordre croissant' => 't.id-ASC',
                'Ordre décroissant' => 't.id-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 't.label-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchTag::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-tag-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
