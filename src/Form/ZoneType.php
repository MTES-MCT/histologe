<?php

namespace App\Form;

use App\Entity\Territory;
use App\Entity\Zone;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ZoneType extends AbstractType
{
    private $isAdmin = false;

    public function __construct(
        private readonly Security $security
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
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
        if ($this->isAdmin && !$zone->getId()) {
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
        }else{
            //TODO partenaires ?
        }
        $docUrl = 'https://data.sigea.educagri.fr/download/sigea/supports/QGIS/distance/initiation/M08_Import_Export/co/10_N1_Export_CSV_geo.html';
        $builder->add('file', FileType::class, [
            'label' => 'Fichier',
            'required' => false,
            'mapped' => false,
            'help' => 'Le fichier doit être au format CSV et contenir une colonnne "WKT" <a href="'.$docUrl.'">doc</a>',
            'help_html' => true,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Merci de sélectionner un fichier',
                ]),
                new Assert\File([
                    'mimeTypes' => ['text/csv', 'text/plain'],
                    'mimeTypesMessage' => 'Le fichier doit être au format CSV',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zone::class,
            'csrf_token_id' => 'add_zone',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
