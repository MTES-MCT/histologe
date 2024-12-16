<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\SearchPartner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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
            'label' => false,
            'attr' => ['placeholder' => 'Rechercher un partenaire'],
        ]);
        if ($this->isAdmin) {
            $builder->add('territory', EntityType::class, [
                'class' => Territory::class,
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'required' => false,
                'placeholder' => 'Tous les territoires',
                'label' => false,
            ]);
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->desactivePartnerType($event->getForm());
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $this->desactivePartnerType($event->getForm());
        });
        $builder->add('page', HiddenType::class);
    }

    private function desactivePartnerType(FormInterface $builder): void
    {
        $options = [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'placeholder' => 'Type de partenaire',
            'required' => false,
            'label' => false,
        ];

        $builder->add('partnerType', EnumType::class, $options);
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
