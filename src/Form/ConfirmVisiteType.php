<?php

namespace App\Form;

use App\Entity\Enum\ProcedureType;
use App\Entity\File;
use App\Entity\Intervention;
use App\Form\Type\SearchCheckboxEnumType;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class ConfirmVisiteType extends AbstractType
{
    public function __construct(
        private readonly TimezoneProvider $timezoneProvider,
        #[Autowire(env: 'S3_ENABLE')]
        private bool $s3Enable,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intervention = $builder->getData();

        $builder->add('visiteDone', ChoiceType::class, [
            'label' => 'La visite a-t-elle eu lieu ? <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'choices' => [
                'Visite effectuée' => true,
                'Visite non effectuée' => false,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'placeholder' => false,
            'mapped' => false,
            'constraints' => [new Assert\NotNull(message: 'Veuillez préciser si la visite a eu lieu.')],
        ]);
        $builder->add('occupantPresent', ChoiceType::class, [
            'label' => 'L\'occupant était-il présent ? <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'constraints' => [new Assert\NotNull(message: 'Veuillez préciser si l\'occupant était présent.')],
            'placeholder' => false,
        ]);
        $builder->add('proprietairePresent', ChoiceType::class, [
            'label' => 'Le propriétaire était-il présent ? <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'constraints' => [new Assert\NotNull(message: 'Veuillez préciser si le propriétaire était présent.')],
            'placeholder' => false,
        ]);
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
        ]);
        $builder->add('details', TextareaType::class, [
            'label' => 'Commentaire de visite <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'required' => false,
            'attr' => [
                'class' => 'editor',
            ],
            'constraints' => [
                new Assert\NotNull(message: 'Veuillez saisir un commentaire pour la visite.'),
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
                        maxSize: '10M',
                        mimeTypes: File::DOCUMENT_MIME_TYPES,
                        mimeTypesMessage: 'Veuillez télécharger un fichier au format '.UploadHandlerService::getAcceptedExtensions().', et ne dépassant pas 10 Mo.'
                    ),
                ],
                'mapped' => false,
            ]);
        }
        // Ajout des contraintes de validation en fonction de la date/heure soumise
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $scheduledAt = $form->get('scheduledAt')->getData();
            $todayInTerritory = (new \DateTimeImmutable('today', $this->timezoneProvider->getDateTimezone()))->format('Y-m-d');
            $isPastDate = $scheduledAt instanceof \DateTimeInterface && $scheduledAt->format('Y-m-d') <= $todayInTerritory;

            if ($isPastDate) {
                $visiteDone = $form->get('visiteDone')->getData();
                // Conclusion obligatoire uniquement si la visite est indiquée comme ayant eu lieu
                if (true === $visiteDone && empty($form->get('concludeProcedure')->getData())) {
                    $form->get('concludeProcedure')->addError(new FormError('Veuillez préciser la conclusion de la visite.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intervention::class,
        ]);
    }
}
