<?php

namespace App\Form;

use App\Dto\DemandeLienSignalement;
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
                'label' => 'Adresse e-mail',
                'help' => 'Renseignez l\'adresse e-mail utilisée pour déposer le signalement',
            ])
            ->add('adresseHelper', null, [
                'label' => 'Adresse du logement',
                'help' => 'Saisissez l\'adresse du logement concerné et sélectionnez-la dans la liste',
                'attr' => [
                    'autocomplete' => 'off',
                    'data-fr-adresse-autocomplete' => 'true',
                    'data-autocomplete-query-selector' => '#form-demande-lien-signalement .fr-address-group',
                ],
            ])
            ->add('adresse', HiddenType::class, [
                'attr' => ['data-autocomplete-addresse' => 'true'],
            ])
            ->add('codePostal', HiddenType::class, [
                'attr' => ['data-autocomplete-codepostal' => 'true'],
            ])
            ->add(
                'ville', HiddenType::class, [
                'attr' => ['data-autocomplete-ville' => 'true'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Recevoir mon lien de suivi',
                'attr' => ['class' => 'fr-btn--icon-left fr-icon-check-line'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeLienSignalement::class,
            'attr' => ['id' => 'form-demande-lien-signalement'],
        ]);
    }
}
