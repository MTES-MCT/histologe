<?php

namespace App\Form;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CloseAffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motifCloture', null, [
                'choices' => MotifCloture::getListForAffectation(),
                'label' => 'Motif de fermeture',
                'choice_label' => static function ($choice) {
                    return $choice->label();
                },
                'choice_attr' => static function ($choice) {
                    if (in_array($choice, MotifCloture::getListNeedTravauxPrecisions(), true)) {
                        return ['data-need-travaux-precisions' => 'true'];
                    }

                    return [];
                },
                'required' => false,
                'placeholder' => 'Sélectionner un motif',
            ]);
        $builder->add('travauxMiseEnConformite', null, [
            'label' => 'Les travaux de mise en conformité du logement ont-ils été réalisés ?',
            'choice_label' => static function ($choice) {
                return $choice->label();
            },
            'expanded' => true,
            'placeholder' => false,
        ]);
        $builder->add('precisionsCloture', null, [
            'label' => 'Précisions',
            'help' => 'Précisez le contexte de la fermeture et détaillez les éventuelles procédures engagées (10 caractères minimum).',
            'required' => false,
            'attr' => [
                'class' => 'editor',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Affectation::class,
            'validation_groups' => ['close_affectation'],
        ]);
    }
}
