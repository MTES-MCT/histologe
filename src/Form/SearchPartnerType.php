<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\ListFilters\SearchPartner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchPartnerType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryPartner', SearchType::class, [
            'required' => false,
            'label' => 'Partenaire',
            'attr' => ['placeholder' => 'Saisir le nom ou l\'e-mail d\'un partenaire'],
        ]);
        if ($this->isAdmin) {
            $builder->add('territoire', EntityType::class, [
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
                'Ordre alphabétique (A -> Z)' => 'p.nom-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'p.nom-DESC',
                'Ordre croissant' => 'p.id-ASC',
                'Ordre décroissant' => 'p.id-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'p.nom-ASC',
        ]);

        $builder->add('isNotNotifiable', CheckboxType::class, [
            'row_attr' => [
                'class' => 'fr-toggle',
            ],
            'label_attr' => [
                'class' => 'fr-toggle__label',
            ],
            'attr' => [
                'class' => 'fr-toggle__input',
                'onchange' => 'this.form.submit()',
            ],
            'required' => false,
            'label' => 'N\'afficher que les partenaires non-notifiables',
        ]);

        $builder->add('partnerType', EnumType::class, [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'placeholder' => 'Tous les types de partenaire',
            'required' => false,
            'label' => 'Type de partenaire',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchPartner::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-partner-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
