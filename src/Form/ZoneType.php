<?php

namespace App\Form;

use App\Entity\Enum\ZoneType as EnumZoneType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\Zone;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ZoneType extends AbstractType
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $zone = $builder->getData();
        $builder
            ->add('name', null, [
                'label' => 'Nom',
                'required' => false,
                'empty_data' => '',
            ]);
        if ($this->security->isGranted('ROLE_ADMIN') && !$zone->getId()) {
            $builder
                ->add('territory', null, [
                    'label' => 'Territoire',
                    'placeholder' => 'Sélectionner une option',
                    'required' => false,
                    'query_builder' => function (TerritoryRepository $tr) {
                        return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
                    },
                    'choice_label' => function (Territory $territory) {
                        return $territory->getZip().' - '.$territory->getName();
                    },
                    'empty_data' => '',
                ]);
        } else {
            $territory = $zone->getTerritory();
            $builder->add('partners', SearchCheckboxType::class, [
                'class' => Partner::class,
                'query_builder' => function (PartnerRepository $partnerRepository) use ($territory) {
                    return $partnerRepository->createQueryBuilder('p')
                        ->where('p.territory = :territory')
                        ->setParameter('territory', $territory)
                        ->orderBy('p.nom', 'ASC');
                },
                'choice_label' => 'nom',
                'label' => 'Partenaires',
                'noselectionlabel' => 'Sélectionnez les partenaires',
                'nochoiceslabel' => 'Aucun partenaire disponible',
                'by_reference' => false,
            ]);
        }

        $builder->add('type', EnumType::class, [
            'class' => EnumZoneType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'row_attr' => [
                'class' => 'fr-select-group',
            ],
            'placeholder' => 'Sélectionner le type de zone',
            'attr' => [
                'class' => 'fr-select',
            ],
            'label' => 'Type de zone',
        ]);

        // $docUrl = 'https://data.sigea.educagri.fr/download/sigea/supports/QGIS/distance/initiation/M08_Import_Export/co/10_N1_Export_CSV_geo.html';
        $fileLabel = 'Fichier (en cas de modification des coordonnées de la zone uniquement)';
        $fileConstraints = [
            new Assert\File([
                'mimeTypes' => ['text/csv', 'text/plain'],
                'mimeTypesMessage' => 'Le fichier doit être au format CSV',
            ]),
        ];
        if (!$zone->getId()) {
            $fileLabel = 'Fichier';
            $fileConstraints[] = new Assert\NotBlank([
                'message' => 'Merci de sélectionner un fichier',
            ]);
        }
        $builder->add('file', FileType::class, [
            'label' => $fileLabel,
            'required' => false,
            'mapped' => false,
            'help' => 'Le fichier doit être au format CSV et contenir une colonnne "WKT"',
            // 'help' => 'Le fichier doit être au format CSV et contenir une colonnne "WKT" <a href="'.$docUrl.'">doc</a>',
            // 'help_html' => true,
            'constraints' => $fileConstraints,
        ]);
        if ($zone->getId()) {
            $builder->add('save', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zone::class,
            'csrf_token_id' => 'zone_type',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
