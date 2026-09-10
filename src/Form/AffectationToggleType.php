<?php

namespace App\Form;

use App\Entity\Partner;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class AffectationToggleType extends AbstractType
{
    public function __construct(private readonly PartnerRepository $partnerRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $partners = $builder->getData();
        $partnersTogglables = array_merge($partners['affected'], $partners['not_affected']);
        $partnerIds = array_column($partnersTogglables, 'id');
        $partnerEntities = $this->partnerRepository->findByIds($partnerIds);

        $disabledPartnerIds = [];
        foreach ($partnersTogglables as $partnerData) {
            if ($partnerData['is_disabled'] ?? false) {
                $disabledPartnerIds[$partnerData['id']] = true;
            }
        }

        $affectedPartnerIds = array_flip(array_column($partners['affected'], 'id'));
        $affectedPartners = array_values(array_filter(
            $partnerEntities,
            static fn (Partner $partner) => isset($affectedPartnerIds[$partner->getId()])
        ));

        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'choices' => array_values($partnerEntities),
            'choice_label' => 'nom',
            'choice_attr' => static fn (Partner $partner) => isset($disabledPartnerIds[$partner->getId()]) ? ['disabled' => 'disabled'] : [],
            'data' => $affectedPartners,
            'label' => '<strong>Sélectionner le(s) partenaire(s) à affecter</strong>',
            'label_html' => true,
            'help' => 'Tapez le nom d\'un partenaire et sélectionnez-le dans la liste',
            'noselectionlabel' => 'Aucun partenaire sélectionné',
            'nochoiceslabel' => 'Aucun partenaire disponible',
            'showSelectionAsTags' => true,
            'showSelectionAsTagsLabel' => 'Partenaire(s) sélectionné(s)',
            'showSelectionAsTagsHelp' => 'Cliquez sur un partenaire pour le retirer du dossier. Il ne pourra alors plus intervenir sur le dossier.',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'signalement-affectation-form',
                'data-submit-type' => 'formData',
            ],
            'validation_groups' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'signalement-affectation';
    }
}
