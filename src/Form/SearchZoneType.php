<?php

namespace App\Form;

use App\Entity\Territory;
use App\Service\SearchZone;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchZoneType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['placeholder' => 'Taper le nom d\'une zone'],
        ]);
        if ($this->security->isGranted('ROLE_ADMIN')) {
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
        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchZone::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-zone-form', 'class' => 'fr-p-4v'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
