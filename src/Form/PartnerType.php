<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ]
            ])
            ->add('isCommune', ChoiceType::class, [
                'row_attr' => [
                    'class' => 'fr-select-group'
                ], 'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [
                    'Commune' => 1,
                    'Partner' => 0
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Type de partner'
            ])
            ->add('insee', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'required' => false,
            ])
            ->add('esaboraUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'required' => false,
            ])
            ->add('esaboraToken', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'required' => false,
            ])
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'query_builder' => function (TerritoryRepository $tr) use ($options) {
                    if ($options['route'] === 'back_partner_new')
                        return $tr->createQueryBuilder('t')->orderBy('t.id', 'ASC');
                    else if ($options['territory'])
                        $territory = $options['territory'];
                    else
                        $territory = $options['data']->getTerritory();
                    return $tr->createQueryBuilder('t')
                        ->where('t.id = :id')
                        ->setParameter('id', $territory->getId());

                },
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'fr-select'
                ],
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'label' => 'Territoire',
                'required' => true,
            ]);
        $builder->get('insee')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return implode(',', $tagsAsArray);
            },
            function ($tagsAsString) {
                // transform the string back to an array
                return explode(',', $tagsAsString);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'allow_extra_fields' => true,
            'territory' => null,
            'route' => null,
        ]);
    }

}
