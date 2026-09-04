<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Enum\TravauxMiseEnConformite;
use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class TravauxMiseEnConformiteType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('travauxMiseEnConformiteUsager', EnumType::class, [
                'class' => TravauxMiseEnConformite::class,
                'choice_label' => static fn (TravauxMiseEnConformite $choice) => $choice->labelForUsager(),
                'expanded' => true,
                'label' => 'Où en sont les travaux dans votre logement ? <span class="text-required">*</span>',
                'label_html' => true,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner une réponse.'),
                ],
            ])
            ->add('travauxMiseEnConformiteUsagerCommentaire', TextareaType::class, [
                'label' => 'Pouvez-vous préciser ?',
                'required' => false,
                'mapped' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Valider ma réponse',
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
