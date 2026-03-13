<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep3;
use App\Entity\Enum\ProfileOccupant;
use App\Form\Type\PhoneType;
use App\Validator\TelephoneFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep3Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('profilOccupant', ChoiceType::class, [
            'choices' => [
                'Locataire' => ProfileOccupant::LOCATAIRE->value,
                'Propriétaire occupant' => ProfileOccupant::BAILLEUR_OCCUPANT->value,
                'Logement vacant' => 'logement_vacant',
                'Indeterminé' => 'indetermine',
            ],
            'expanded' => true,
            'required' => false,
            'placeholder' => false,
            'label' => 'Profil de l\'occupant <span class="text-required">*</span>',
            'label_html' => true,
        ]);
        $builder->add('nomOccupant', null, ['label' => 'Nom']);
        $builder->add('prenomOccupant', null, ['label' => 'Prénom']);
        $builder->add('mailOccupant', TextType::class, [
            'label' => 'Adresse e-mail',
            'help' => 'Format attendu : exemple@domaine.com',
            'required' => false,
        ]);
        // TODO : importer js associé + icone
        $builder->add('telOccupant', PhoneType::class, [
            'label' => 'Téléphone',
            'constraints' => [
                new TelephoneFormat([
                    'message' => 'Le numéro de téléphone n\'est pas valide.',
                ]),
            ],
        ]);
        $builder->add('nbAdultesDansLogement', TextType::class, [
            'label' => 'Nombre d\'adultes vivant dans le logement',
            'help' => 'Format attendu : Saisir un nombre entier',
            'required' => false,
        ]);
        $builder->add('nbEnfantsDansLogement', TextType::class, [
            'label' => 'Nombre de mineurs vivant dans le logement',
            'help' => 'Format attendu : Saisir un nombre entier',
            'required' => false,
        ]);
        $builder->add('isEnfantsMoinsSixAnsDansLogement', ChoiceType::class, [
            'label' => 'Mineurs de 6 ans ou moins',
            'required' => false,
            'choices' => [
                'Oui' => 'oui',
                'Non' => 'non',
            ],
            'expanded' => true,
            'placeholder' => false,
        ]);
        $builder->add('autreVulnerabilite', TextareaType::class, [
            'label' => 'Autre situation de vulnérabilité à mentionner',
            'required' => false,
            'attr' => ['rows' => 4],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormServiceSecoursStep3::class,
        ]);
    }
}
