<?php

namespace App\Form;

use App\Entity\Enum\DocumentType;
use App\Form\Type\TerritoryChoiceType;
use App\Service\ListFilters\SearchTerritoryFiles;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchTerritoryFilesType extends AbstractType
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
        $builder->add('queryName', SearchType::class, [
            'required' => false,
            'label' => 'Document',
            'attr' => ['placeholder' => 'Taper le nom du document'],
        ]);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('territory', TerritoryChoiceType::class);
        }

        $builder->add('type', EnumType::class, [
            'class' => DocumentType::class,
            'choice_filter' => ChoiceList::filter(
                $this,
                function ($choice) {
                    if (!empty($choice)) {
                        return \array_key_exists($choice->name, DocumentType::getTerritoryFilesList()) ? $choice : false;
                    }
                },
                'doctype',
            ),
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'required' => false,
            'placeholder' => 'Tous les types de document',
            'label' => 'Type de document',
        ]);

        $choices = [
            'Ordre alphabétique (A -> Z)' => 'f.title-ASC',
            'Ordre alphabétique inversé (Z -> A)' => 'f.title-DESC',
        ];
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $choices['Date d\'ajout (du plus ancien au plus récent)'] = 'f.id-ASC';
            $choices['Date d\'ajout (du plus récent au plus ancien)'] = 'f.id-DESC';
        }
        $builder->add('orderType', ChoiceType::class, [
            'choices' => $choices,
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'f.title-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchTerritoryFiles::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-territory-files-type-form', 'class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
