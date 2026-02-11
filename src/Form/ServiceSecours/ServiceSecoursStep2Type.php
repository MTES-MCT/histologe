<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\ServiceSecoursStep2;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('adresseComplete', null, [
            'label' => 'Adresse du logement <span class="text-required">*</span>',
            'label_html' => true,
            'help' => 'Format attendu : Tapez l\'adresse puis sélectionnez-la dans la liste. Si elle n\'apparait pas, cliquez sur Saisir une adresse manuellement.',
        ]); // obligatoire
        // TODO : gerer les champs adresse indépendant (adresse, code postal, ville)
        $builder->add('adresseAutreOccupant', null, ['label' => 'Complément d\'adresse (bâtiment/étage/porte)', 'help' => 'Format attendu : 255 caractères maximum']);
        $builder->add('isLogementSocial', ChoiceType::class, [
            'label' => 'Logement social <span class="text-required">*</span>',
            'label_html' => true,
            'expanded' => true,
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'Oui' => true,
                'Non' => false,
                'Indéterminé' => null,
            ],
        ]); // obligatoire
        $builder->add('natureLogement', ChoiceType::class, [
            'expanded' => true,
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'Appartement' => 'appartement',
                'Maison' => 'maison',
                'Autre' => 'autre',
            ],
            'label' => 'Nature du logement <span class="text-required">*</span>',
            'label_html' => true,
        ]); // obligatoire
        $builder->add('typeEtageLogement', ChoiceType::class, [
            'expanded' => true,
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'Rez de chaussée' => 'rez_de_chaussee',
                'Dernier étage' => 'dernier_etage',
                'Sous-sol' => 'sous_sol',
                'Autre étage' => 'autre',
            ],
            'label' => 'Localisation de l\'appartement',
        ]);
        $builder->add('etageOccupant', null, ['label' => 'Précicez l\'étage', 'help' => 'Format attendu : 5 caractères maximum']);
        $builder->add('nbPiecesLogement', null, ['label' => 'Nombre de pièces à vivre (salon, chambre) du logement', 'help' => 'Format attendu : Saisir un nombre entier']);
        $builder->add('superficie', null, ['label' => 'Superficie approximative du logement (en m²)', 'help' => 'Format attendu : Saisir un nombre entier']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceSecoursStep2::class,
        ]);
    }
}
