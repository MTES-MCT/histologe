<?php

namespace App\Form\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep5;
use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Enum\AppContext;
use App\Repository\DesordreCritereRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceSecoursStep5Type extends AbstractType
{
    public function __construct(private readonly DesordreCritereRepository $desordreCritereRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $desordres = $this->desordreCritereRepository->findAllWithPrecisions(AppContext::SERVICE_SECOURS);

        $builder->add('desordres', ChoiceType::class, [
            'choices' => $desordres,
            'choice_value' => fn (?DesordreCritere $desordre) => $desordre?->getSlugCritere(),
            'choice_label' => fn (DesordreCritere $desordre) => $desordre->getLabelCritere(),
            'choice_attr' => function (DesordreCritere $desordre): array {
                $precision = $desordre->getDesordrePrecisions()->first();
                /** @var DesordrePrecision $precision */
                if ($precision) {
                    $description = $precision->getLabel();
                }

                return [
                    'data-dsfr-hint' => $description ?? '',
                ];
            },
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
            'data_class' => FormServiceSecoursStep5::class,
        ]);
    }
}
