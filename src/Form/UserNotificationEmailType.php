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
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $builder->getData();
        $builder->add('isMailingSummary', ChoiceType::class, [
            'choices' => [
                'Un e-mail récapitulatif par jour' => true,
                'Tous les e-mails' => false,
            ],
            'expanded' => true,
            'label' => 'Notifications e-mails',
            'required' => false,
            'placeholder' => false,
            'mapped' => false,
            'data' => $user->getIsMailingActive() ? $user->getIsMailingSummary() : null,
        ]);
        $builder->add('isMailingClubEvent', ChoiceType::class, [
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
            'expanded' => true,
            'label' => 'Recevoir les notifications des événements du club',
            'required' => false,
            'placeholder' => false,
        ]);
        $builder->add('isSubmitted', HiddenType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['notification_email'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
