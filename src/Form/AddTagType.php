<?php

namespace App\Form;

use App\Entity\Tag;
use App\Form\Type\TerritoryChoiceType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddTagType extends AbstractType
{
    private bool $isAdmin = false;

    public function __construct(
        private readonly Security $security,
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', null, [
                'label' => 'Etiquette',
                'required' => false,
                'empty_data' => '',
            ]);
        if ($this->isAdmin) {
            $builder->add('territory', TerritoryChoiceType::class);
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
