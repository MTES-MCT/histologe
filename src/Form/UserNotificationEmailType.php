<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserNotificationEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $builder->getData();
        $builder->add('isMailingSummary', ChoiceType::class, [
            'choices' => [
                'Un e-mail récapitulatif par jour' => true,
                'Tous les e-mails' => false,
            ],
            'expanded' => true,
            'label' => false,
            'required' => false,
            'placeholder' => false,
            'data' => $user->getIsMailingActive() ? $user->getIsMailingSummary() : null,
        ]);
        $builder->add('isSubmitted', HiddenType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
