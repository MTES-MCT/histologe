<?php

namespace App\Form;

use App\Entity\Model\DemandeLienSignalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeLienSignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'Adresse email',
                'help' => 'Renseignez l\'adresse email utilisée pour déposer le signalement',
            ])
            ->add('adresseHelper', null, [
                'label' => 'Adresse du logement',
                'help' => 'Saisissez l\'adresse du logement concerné et sélectionnez-la dans la liste',
            ])
            ->add('adresse', HiddenType::class)
            ->add('codePostal', HiddenType::class)
            ->add('ville', HiddenType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Recevoir mon lien de suivi par mail',
                'attr' => ['class' => 'fr-btn--icon-left fr-icon-check-line fr-btn--sm'],
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeLienSignalement::class,
        ]);
    }
}
