<?php

namespace App\Form;

use App\Entity\Enum\ZoneType as EnumZoneType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class ZoneType extends AbstractType
{
    private Package $package;

    public function __construct(
        private readonly Security $security,
    ) {
        $this->package = new Package(new EmptyVersionStrategy());
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
                'label' => 'Partenaires de la zone',
                'help' => 'Sélectionnez dans la liste les partenaires qui pourront intervenir spécifiquement sur les dossiers situés dans la zone.',
                'noselectionlabel' => 'Sélectionnez les partenaires',
                'nochoiceslabel' => 'Aucun partenaire disponible',
                'by_reference' => false,
            ]);
            $builder->add('excludedPartners', SearchCheckboxType::class, [
                'class' => Partner::class,
                'query_builder' => function (PartnerRepository $partnerRepository) use ($territory) {
                    return $partnerRepository->createQueryBuilder('p')
                        ->where('p.territory = :territory')
                        ->setParameter('territory', $territory)
                        ->orderBy('p.nom', 'ASC');
                },
                'choice_label' => 'nom',
                'help' => 'Sélectionnez dans la liste les partenaires à exclure de la zone. Ces partenaires ne pourront pas être affectés aux dossiers situés dans la zone.',
                'label' => 'Partenaires exclus de la zone',
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
            'placeholder' => 'Sélectionner le type de zone',
            'label' => 'Type de zone',
        ]);
        $fileConstraints = [
            new Assert\File([
                'mimeTypes' => ['text/csv', 'text/plain'],
                'mimeTypesMessage' => 'Le fichier doit être au format CSV',
            ]),
        ];
        $modeleUrl = $this->package->getUrl('/build/files/zone_modele.csv');
        $builder->add('file', FileType::class, [
            'label' => $zone->getId() ? 'Modifier depuis un fichier (en cas de modification des coordonnées de la zone uniquement)' : 'Importer depuis un fichier',
            'required' => false,
            'mapped' => false,
            'help' => 'Le fichier doit être au format CSV séparé par des virgules et contenir une colonnne "WKT", <a href="'.$modeleUrl.'">voir le template</a>',
            'help_html' => true,
            'constraints' => $fileConstraints,
        ]);
        $builder->add('area', null, [
            'label' => $zone->getId() ? 'Copier / coller le texte au format WKT' : 'Ou copier / coller le texte au format WKT',
            'required' => false,
            'help' => 'Vous pouvez générer une zone au bon format avec l\'outil <a href="https://wktmap.com/">wktmap.com</a>',
            'help_html' => true,
            'attr' => ['rows' => 5],
        ]);
        if ($zone->getId()) {
            $builder->add('save', SubmitType::class, [
                'label' => 'Modifier',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);
        }
        // Ajouter un écouteur d'événements pour valider les champs file et area
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $file = $form->get('file')->getData();
            $area = $form->get('area')->getData();

            if (empty($file) && empty($area)) {
                $error = new FormError('Vous devez renseigner une zone via le champ fichier ou texte.');
                $form->get('file')->addError($error);
                $form->get('area')->addError($error);
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
