<?php

namespace App\Form;

use App\Entity\Enum\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AddTerritoryFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'Fichier <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'help' => 'Sélectionnez un fichier à télécharger.',
            'multiple' => false,
            'required' => false,
            'mapped' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Veuillez sélectionner un fichier à télécharger.',
                ]),
                new Assert\Valid(),
            ],
        ]);

        $builder->add('title', null, [
            'label' => 'Nom du document <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'help' => 'Ce nom sera visible par les partenaires. 100 caractères maximum.',
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length([
                    'min' => 3,
                    'minMessage' => 'Le nom du document doit contenir au moins {{ limit }} caractères.',
                    'max' => 100,
                    'maxMessage' => 'Le nom du document doit contenir au maximum {{ limit }} caractères.',
                ]),
            ],
        ]);

        $builder->add('documentType', EnumType::class, [
            'label' => 'Type de document <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'help' => 'Sélectionnez un type dans la liste.',
            'placeholder' => 'Sélectionnez un type',
            'required' => false,
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
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Veuillez sélectionner un type de document.',
                ]),
            ],
        ]);

        $builder->add('description', TextareaType::class, [
            'label' => 'Description (facultatif)',
            'help' => 'Expliquez à vos partenaires, en quelques mots, l\'objet et l\'utilisation du document.',
            'required' => false,
            'constraints' => [
                new Assert\Length([
                    'min' => 10,
                    'minMessage' => 'La description du document doit contenir au moins {{ limit }} caractères.',
                ]),
            ],
        ]);
    }
}
