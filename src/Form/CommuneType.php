<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Epci;
use App\Entity\Territory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommuneType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'Nom',
            ])
            ->add('epci', EntityType::class, [
                'class' => Epci::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')->orderBy('e.nom', 'ASC');
                },
                'choice_label' => 'nom',
                'label' => 'EPCI',
                'required' => false,
            ])
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'label' => 'Territoire',
                'choice_label' => 'zipAndName',
                'disabled' => true,
            ])
            ->add('codePostal', null, [
                'label' => 'Code postal',
                'disabled' => true,
            ])
            ->add('codeInsee', null, [
                'label' => 'Code INSEE',
                'disabled' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commune::class,
            'csrf_token_id' => 'commune_type',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
