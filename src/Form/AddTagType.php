<?php

namespace App\Form;

use App\Entity\Tag;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddTagType extends AbstractType
{
    private $isAdmin = false;

    public function __construct(
        private readonly Security $security
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', null, [
                'label' => 'Etiquette',
                'required' => false,
                'empty_data' => '',
            ]);
        if ($this->isAdmin) {
            $builder
                ->add('territory', null, [
                    'label' => 'Territoire',
                    'placeholder' => 'Sélectionner une option',
                    'required' => false,
                    'query_builder' => function (TerritoryRepository $tr) {
                        return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
                    },
                    'choice_label' => function (Territory $territory) {
                        return $territory->getZip().' - '.$territory->getName();
                    },
                    'empty_data' => '',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
            'csrf_token_id' => 'add_tag',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
