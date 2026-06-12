<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class MessageUsagerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Votre message <span class="text-required">*</span>',
                'help' => 'Dix (10) caractères minimum.',
                'label_html' => true,
                'attr' => [
                    'rows' => 5,
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ]);
    }
}
