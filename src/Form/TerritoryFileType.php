<?php

namespace App\Form;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\PartnerType;
use App\Entity\File;
use App\Form\Type\SearchCheckboxEnumType;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\FileRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TerritoryFileType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly FileRepository $fileRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On retire le champ file si on est en édition (l'entité a un id)
        $isEdit = false;
        if (isset($options['data']) && $options['data'] instanceof File && $options['data']->getId()) {
            $isEdit = true;
        }
        if (!$isEdit) {
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
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => File::DOCUMENT_MIME_TYPES,
                        'maxSizeMessage' => 'Le fichier ne doit pas dépasser 10 Mo.',
                        'mimeTypesMessage' => 'Seuls les fichiers {{ types }} sont autorisés.',
                    ]),
                ],
            ]);
        }
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('territory', TerritoryChoiceType::class);
        }

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
        $builder->add('partnerType', SearchCheckboxEnumType::class, [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'label' => 'Type de partenaire (facultatif)',
            'noselectionlabel' => 'Choisissez le ou les types de partenaires dans la liste',
            'nochoiceslabel' => 'Aucun type de partenaire disponible',
            'help' => 'Choisissez un ou plusieurs types de partenaire parmi la liste ci-dessous.',
            'required' => false,
        ]);
        $builder->add('partnerCompetence', SearchCheckboxEnumType::class, [
            'class' => Qualification::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'label' => 'Compétences (facultatif)',
            'noselectionlabel' => 'Choisissez la ou les compétences dans la liste',
            'nochoiceslabel' => 'Aucune compétence disponible',
            'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Assert\Callback([$this, 'validateDocumentType']),
            ],
        ]);
    }

    public function validateDocumentType(mixed $value, ExecutionContextInterface $context): void
    {
        if ($value instanceof File) {
            $file = $value;

            if (DocumentType::GRILLE_DE_VISITE !== $file->getDocumentType()) {
                return;
            }

            $existingVisitGrid = $this->fileRepository->findOneBy([
                'territory' => $file->getTerritory(),
                'documentType' => DocumentType::GRILLE_DE_VISITE,
            ]);

            if (
                // Si c'est une création, on vérifie simplement l'existence d'une auttre grille de visite
                (empty($file->getId()) && null !== $existingVisitGrid)
                // Si c'est une édition, on vérifie que la grille de visite trouvée n'est pas celle en cours d'édition
                || ($existingVisitGrid && $existingVisitGrid->getId() !== $file->getId())
            ) {
                $context->buildViolation('Il existe déjà une grille de visite pour ce territoire.')
                    ->atPath('documentType')
                    ->addViolation();
            }
        }
    }
}
