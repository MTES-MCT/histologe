<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordonneesSyndicType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('denominationSyndic', null, [
                'label' => 'Dénomination',
                'required' => false,
            ])
            ->add('nomSyndic', null, [
                'label' => 'Nom',
                'help' => 'Saisissez le nom du contact du syndic',
                'required' => false,
            ])
            ->add('mailSyndic', TextType::class, [
                'label' => 'Adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'required' => false,
            ])
            ->add('telSyndic', null, [
                'label' => 'Numéro de téléphone du syndic',
                'help' => 'Format attendu : 0639987654',
                'required' => false,
            ])
            ->add('telSyndicSecondaire', null, [
                'label' => 'Numéro de téléphone secondaire du syndic',
                'help' => 'Format attendu : 0639987654',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
        ]);
    }
}
