<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('profilOccupant', ChoiceType::class, [
            'choices' => [
                'Locataire' => 'locataire',
                'Propriétaire occupant' => 'proprietaire_occupant',
                'Logement vacant' => 'logement_vacant',
                'Indeterminé' => 'indetermine',
            ],
            'label' => 'Profil de l\'occupant <span class="text-required">*</span>',
            'label_html' => true,
        ]);
        $builder->add('nomOccupant', null, ['label' => 'Nom']);
        $builder->add('prenomOccupant', null, ['label' => 'Prénom']);
        $builder->add('mailOccupant', null, ['label' => 'Adresse e-mail']);
        $builder->add('telOccupant', null, ['label' => 'Numéro de téléphone']);
        $builder->add('nbAdultesDansLogement', null, ['label' => 'Nombre d\'adultes vivant dans le logement', 'help' => 'Format attendu : Saisir un nombre entier']);
        $builder->add('nbEnfantsDansLogement', null, ['label' => 'Nombre de mineurs vivant dans le logement', 'help' => 'Format attendu : Saisir un nombre entier']);
        $builder->add('isEnfantsMoinsSixAnsDansLogement', ChoiceType::class, [
            'label' => 'Présence d\'enfants de moins de 6 ans dans le logement',
            'required' => false,
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
        ]);
        $builder->add('autreVulnerabilite', TextareaType::class, ['label' => 'Autre situation de vulnérabilité à mentionner', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep3::class,
        ]);
    }
}
