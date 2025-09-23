<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Form\Type\SearchAutocompleteType;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use App\Service\ListFilters\SearchDashboardAverifier;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchDashboardAverifierType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryCommune', SearchAutocompleteType::class, [
            'required' => false,
            'label' => 'Commune ou code postal',
            'attr' => ['placeholder' => 'Taper le nom ou le code postal de la commune'],
            'autocomplete_choices' => $options['communesAndCp'] ?? [],
        ]);

        $builder->add('territoireId', HiddenType::class, [
            'mapped' => false,
            'required' => false,
            'data' => $options['territory']?->getId() ?? '',
            'attr' => [
                'disabled' => $options['territory'] ? false : true,
            ],
        ]);

        $builder->add('mesDossiersAverifier', HiddenType::class, [
            'mapped' => false,
            'required' => false,
            'data' => $options['mesDossiersAverifier'] ?? '',
        ]);

        $builder->add('mesDossiersMessagesUsagers', HiddenType::class, [
            'mapped' => false,
            'required' => false,
        ]);

        if ($this->security->isGranted('ROLE_SUPER_ADMIN') || $this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $this->addPartnersField($builder, $options['territory']);
        }
    }

    private function addPartnersField(FormInterface|FormBuilderInterface $builder, string|Territory|null $territory): void
    {
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) use ($territory) {
                $query = $partnerRepository->createQueryBuilder('p');

                if ($territory) {
                    $query->where('p.territory = :territory')
                       ->setParameter('territory', $territory)
                       ->orderBy('p.nom', 'ASC');
                } else {
                    $query->where('1 = 0');
                }

                return $query;
            },
            'choice_label' => 'nom',
            'label' => 'Partenaires',
            'noselectionlabel' => 'Tous les partenaires',
            'required' => false,
            'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les partenaires disponibles' : 'Aucun partenaire disponible',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchDashboardAverifier::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-dashboard-averifier-form', 'class' => 'bo-filter-form'],
            'territory' => null,
            'mesDossiersAverifier' => null,
            'communesAndCp' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
