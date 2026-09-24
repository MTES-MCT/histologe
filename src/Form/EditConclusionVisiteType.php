<?php

namespace App\Form;

use App\Entity\Enum\ProcedureType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Form\Type\SearchCheckboxEnumType;
use App\Service\UploadHandlerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class EditConclusionVisiteType extends AbstractType
{
    public function __construct(
        #[Autowire(env: 'S3_ENABLE')]
        private bool $s3Enable,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intervention = $builder->getData();

        if (!$intervention->getSignalement()->getIsLogementVacant()) {
            $builder->add('notifyUsager', CheckboxType::class, [
                'row_attr' => [
                    'class' => 'fr-toggle fr-mb-6v',
                ],
                'label_attr' => [
                    'class' => 'fr-toggle__label',
                ],
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
                'label' => 'En cochant cette case, l\'usager sera notifié des informations de cette visite ',
            ]);
        }
        $builder->add('concludeProcedure', SearchCheckboxEnumType::class, [
            'class' => ProcedureType::class,
            'label' => 'Quelle est la conclusion de la visite ? <span class="fr-text-default--error">*</span>',
            'noselectionlabel' => 'Sélectionner une ou plusieurs conclusions',
            'label_html' => true,
            'required' => false,
            'choice_label' => 'label',
            'row_attr' => [
                'class' => 'fr-mb-6v',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Veuillez préciser la conclusion de la visite.'),
            ],
        ]);
        $builder->add('details', TextareaType::class, [
            'label' => 'Commentaire de visite <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'required' => false,
            'attr' => [
                'class' => 'editor',
            ],
            'constraints' => [
                new Assert\Length(min: 16, minMessage: 'Le commentaire de visite doit contenir au moins 10 caractères.'),
            ],
        ]);
        if (!$intervention->getRapportDeVisite()) {
            $builder->add('rapportDeVisite', FileType::class, [
                'label' => 'Rapport de visite (facultatif)',
                'help' => 'Formats supportés : '.UploadHandlerService::getAcceptedExtensions(),
                'required' => false,
                'attr' => [
                    'accept' => implode(',', File::DOCUMENT_MIME_TYPES),
                    'disabled' => !$this->s3Enable,
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '25M',
                        mimeTypes: File::DOCUMENT_MIME_TYPES,
                        mimeTypesMessage: 'Veuillez télécharger un fichier au format '.UploadHandlerService::getAcceptedExtensions().', et ne dépassant pas 25 Mo.'
                    ),
                ],
                'mapped' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
        ]);
    }
}
