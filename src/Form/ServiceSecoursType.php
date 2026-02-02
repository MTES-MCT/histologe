<?php

namespace App\Form;

use App\Dto\Api\Request\SignalementRequest;
use App\Entity\Enum\DesordreSecours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // informations déclarant
        $builder
            ->add('structureDeclarant', null, ['label' => 'Service ou organisme'])
            ->add('mailDeclarant', null, ['label' => 'Adresse e-mail'])
            ->add('telDeclarant', null, ['label' => 'Numéro de téléphone'])
            ->add('nomDeclarant', null, ['label' => 'Nom'])
            ->add('prenomDeclarant', null, ['label' => 'Prénom'])
            ->add('dateVisite', DateType::class, ['label' => 'Date de la visite du logement', 'mapped' => false, 'required' => false]);
        // informations bailleur
        $builder->add('nomBailleur', null, ['label' => 'Nom'])
            ->add('prenomBailleur', null, ['label' => 'Prénom'])
            ->add('mailBailleur', null, ['label' => 'Adresse e-mail'])
            ->add('telBailleur', null, ['label' => 'Numéro de téléphone'])
            ->add('isLogementSocial', CheckboxType::class, ['label' => 'Bailleur social ?', 'required' => false]);
        // localisation du logement
        $builder->add('adresseComplete', null, ['label' => 'Adresse complète du logement', 'mapped' => false])
            ->add('adresseAutreOccupant', null, ['label' => 'Bâtiment/étage/porte'])
            ->add('profilOccupant', ChoiceType::class, [
                'choices' => [
                    'Locataire' => 'locataire',
                    'Propriétaire occupant' => 'proprietaire_occupant',
                    'Indeterminé' => 'indetermine',
                ],
                'label' => 'Profil de l\'occupant',
                'mapped' => false,
            ]);
        // catégorie logement
        $builder->add('natureLogement', ChoiceType::class, [
            'choices' => [
                'Appartement' => 'appartement',
                'Maison individuelle' => 'maison',
                'Hôtel meublé' => 'hotel_meuble',
                'Combles' => 'combles',
                'Caves, garage, sous-sol' => 'caves_garage_soussol',
                'Autre' => 'autre',
            ],
            'label' => 'Nature du logement',
            'mapped' => false,
        ]);
        $builder->add('natureLogementAutre', null, ['label' => 'Si autre, précisez']);
        $builder->add('nombrePieces', null, ['label' => 'Nombre de pièces']);
        // infos occupant
        $builder->add('nomOccupant', null, ['label' => 'Nom']);
        $builder->add('prenomOccupant', null, ['label' => 'Prénom']);
        $builder->add('mailOccupant', null, ['label' => 'Adresse e-mail']);
        $builder->add('telOccupant', null, ['label' => 'Numéro de téléphone']);
        $builder->add('nbOccupantsLogement', null, ['label' => 'Nombre de personnes vivant dans le logement']);
        $builder->add('nbEnfantsDansLogement', null, ['label' => 'Nombre d\'enfant']);
        $builder->add('commentaireOccupant', TextareaType::class, ['label' => 'Précisions eventuelles', 'mapped' => false]);
        // desordres
        $builder->add('desordres', ChoiceType::class, [
            'choices' => DesordreSecours::cases(),
            'choice_value' => fn (?DesordreSecours $desordre) => $desordre?->value,
            'choice_label' => fn (DesordreSecours $desordre) => $desordre->label(),
            'multiple' => true,
            'expanded' => true,
            'mapped' => false,
            'label' => 'Cocher les principaux éléments motivant le signalement (Il est possible de cocher plusieurs cases)',
        ]);
        $builder->add('desordresAutre', TextareaType::class, ['label' => 'Autres éléments à signaler', 'mapped' => false]);
        // divers
        $builder->add('coordonneesSyndic', TextareaType::class, ['label' => 'Coordonnées du syndic', 'mapped' => false]);
        $builder->add('desagrementsImmeuble', null, ['label' => 'Les autres occupants de l\'immeuble ont-ils connus des désagréments dans leurs logements ?', 'mapped' => false]);
        $builder->add('isTransmisMairie', CheckboxType::class, ['label' => 'Signalement déja transmis en mairie ?', 'mapped' => false, 'required' => false]);
        $builder->add('isBailleurAverti', CheckboxType::class, ['label' => 'Propriétaire/bailleur averti ?', 'required' => false, 'help' => 'Indiquer si le propriétaire ou bailleur a déjà été informé des problèmes rencontrés dans le logement.']);
        // submit button
        $builder->add('submit', SubmitType::class, ['label' => 'Envoyer le signalement']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SignalementRequest::class,
        ]);
    }
}
