<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\ServiceSecoursStep5;
use App\Entity\Enum\DesordreSecours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep5Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('desordres', ChoiceType::class, [
            'choices' => DesordreSecours::cases(),
            'choice_value' => fn (?DesordreSecours $desordre) => $desordre?->value,
            'choice_label' => fn (DesordreSecours $desordre) => $desordre->label(),
            'multiple' => true,
            'expanded' => true,
            'label' => 'Désordres <span class="text-required">*</span>',
            'label_html' => true,
        ]);
        $builder->add('desordresAutre', TextareaType::class, ['label' => 'Autres éléments à signaler', 'required' => false]);
        $builder->add('autresOccupantsDesordre', ChoiceType::class, [
            'label' => 'D\'autres occupants de l\'immeuble ont-ils rencontré des désordres ? <span class="text-required">*</span>',
            'label_html' => true,
            'required' => false,
            'expanded' => true,
            'placeholder' => false,
            'choices' => [
                'Oui' => true,
                'Non' => false,
                'Indéterminé' => null,
            ],
        ]);
        // TODO : ajout de doc
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServiceSecoursStep5::class,
        ]);
    }
}
