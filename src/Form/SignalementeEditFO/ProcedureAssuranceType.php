<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Model\InformationProcedure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcedureAssuranceType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('infoProcedureAssuranceContactee', ChoiceType::class, [
            'label' => "L'assurance a-t-elle été contactée ?",
            'choices' => [
                'Oui' => 'oui',
                'Non' => 'non',
                'Pas d\'assurance logement' => 'pas_assurance_logement',
                'Ne sait pas' => 'nsp',
            ],
            'expanded' => true,
            'required' => false,
            'placeholder' => false,
        ]);
        $builder->add('infoProcedureReponseAssurance', TextareaType::class, [
            'label' => "Quelle a été la réponse de l'assurance ?",
            'required' => false,
            'attr' => [
                'rows' => 4,
            ],
        ]);
        $builder->add('save', SubmitType::class, [
            'label' => 'Envoyer',
            'attr' => [
                'class' => 'fr-btn--primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InformationProcedure::class,
        ]);
    }
}
