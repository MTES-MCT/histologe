<?php

namespace App\Form;

use App\Dto\Api\Request\SignalementRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            // TODO : créer le champ dateVisite non mappé
            ->add('dateVisite', DateType::class, ['label' => 'Date de la visite du logement', 'mapped' => false, 'required' => false]);
        // informations bailleur
        $builder->add('nomBailleur', null, ['label' => 'Nom'])
            ->add('prenomBailleur', null, ['label' => 'Prénom'])
            ->add('mailBailleur', null, ['label' => 'Adresse e-mail'])
            ->add('telBailleur', null, ['label' => 'Numéro de téléphone'])
            ->add('isLogementSocial', CheckboxType::class, ['label' => 'Bailleur social ?', 'required' => false]);
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
