<?php

namespace App\Form;

use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Form\Type\SearchCheckboxEnumType;
use App\Repository\InterventionRepository;
use App\Repository\PartnerRepository;
use App\Service\TimezoneProvider;
use App\Service\UploadHandlerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<mixed>
 */
class AddAndRescheduleVisiteType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly PartnerRepository $partnerRepository,
        private readonly InterventionRepository $interventionRepository,
        private readonly TimezoneProvider $timezoneProvider,
        #[Autowire(env: 'S3_ENABLE')]
        private bool $s3Enable,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $intervention = $builder->getData();

        $builder->add('scheduledAt', DateType::class, [
            'label' => 'Date de la visite <span class="fr-text-default--error">*</span>',
            'label_html' => true,
            'required' => false,
            'input' => 'datetime_immutable',
            'constraints' => [new Assert\NotBlank(message: 'Veuillez indiquer la date de la visite.')],
        ]);
        $builder->add('scheduledAtTime', TimeType::class, [
            'label' => 'Heure de la visite',
            'label_html' => true,
            'required' => false,
            'mapped' => false,
            'input' => 'datetime_immutable',
            'data' => $intervention->getScheduledAt(),
        ]);
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $partners = $this->partnerRepository->findPartnersWithQualificationAffectedOnSignalement(Qualification::VISITES, $intervention->getSignalement());
            $pendingVisites = $this->interventionRepository->getPendingVisitesForSignalement($intervention->getSignalement());
            $partnersWithPendingVisites = [];
            $externalOperatorsWithPendingVisites = [];
            foreach ($pendingVisites as $pendingVisite) {
                if ($pendingVisite->getId() === $intervention->getId()) {
                    continue;
                }
                if ($pendingVisite->getPartner()?->getId()) {
                    $partnersWithPendingVisites[$pendingVisite->getPartner()->getId()] = $pendingVisite->getPartner();
                } elseif ($pendingVisite->getExternalOperator()) {
                    $externalOperatorsWithPendingVisites[] = $pendingVisite->getExternalOperator();
                }
            }
            $dataPartner = null;
            if ($intervention->getPartner() && $intervention->getPartner()->getId()) {
                $dataPartner = $intervention->getPartner();
            } elseif ($intervention->getExternalOperator()) {
                $dataPartner = 'extern';
            }
            $builder->add('partnerChoice', ChoiceType::class, [
                'label' => 'Opérateur de visite <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'mapped' => false,
                'choices' => [...$partners, 'extern'],
                'choice_label' => static fn ($choice) => $choice instanceof Partner ? mb_strtoupper($choice->getNom()) : 'Opérateur Externe',
                'choice_value' => static fn ($choice) => $choice instanceof Partner ? (string) $choice->getId() : 'extern',
                'choice_attr' => static fn ($choice) => $choice instanceof Partner && isset($partnersWithPendingVisites[$choice->getId()]) ? ['class' => 'alert-partner'] : [],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez sélectionner un opérateur de visite.'),
                    new Assert\Callback(static function ($value, ExecutionContextInterface $context) use ($partnersWithPendingVisites) {
                        if (is_numeric($value) && isset($partnersWithPendingVisites[(int) $value])) {
                            $context
                                ->buildViolation('Ce partenaire a déjà une visite en cours. Veuillez terminer ou annuler la visite ou sélectionner un autre partenaire.')
                                ->addViolation();
                        }
                    }),
                ],
                'data' => $dataPartner,
            ]);
            $builder->add('externalOperator', TextType::class, [
                'label' => 'Nom de l\'opérateur externe <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'required' => false,
                'row_attr' => [
                    'class' => 'fr-hidden',
                ],
                'attr' => [
                    'data-existing-pending-external-operators' => json_encode($externalOperatorsWithPendingVisites),
                ],
                'constraints' => [
                    new Assert\Callback(static function ($value, ExecutionContextInterface $context) use ($externalOperatorsWithPendingVisites) {
                        if ($value && \in_array(mb_strtolower(trim($value)), array_map('mb_strtolower', $externalOperatorsWithPendingVisites), true)) {
                            $context
                                ->buildViolation('Cet opérateur externe a déjà une visite en cours. Veuillez terminer ou annuler la visite ou sélectionner un autre partenaire.')
                                ->addViolation();
                        }
                    }),
                ],
            ]);
            $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) {
                $form = $event->getForm();
                if ('extern' === $form->get('partnerChoice')->getData() && empty($form->get('externalOperator')->getData())) {
                    $form->get('externalOperator')->addError(new FormError('Veuillez saisir le nom de l\'opérateur externe.'));
                }
            });
        }
        $builder->add('commentBeforeVisite', TextareaType::class, [
            'label' => 'Commentaire (facultatif)',
            'help' => 'Le commentaire sera visible de l\'usager',
            'required' => false,
            'attr' => [
                'class' => 'editor',
            ],
            'constraints' => [
                new Assert\Length(min: 16, minMessage: 'Le commentaire de visite doit contenir au moins 10 caractères.'),
            ],
        ]);
        // les champs ci dessous sont obligatoire si visite passé (voir le POST_SUBMIT plus bas)
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
        // Ajout des contraintes de validation en fonction de la date/heure soumise
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $scheduledAt = $form->get('scheduledAt')->getData();
            $todayInTerritory = (new \DateTimeImmutable('today', $this->timezoneProvider->getDateTimezone()))->format('Y-m-d');
            $isPastDate = $scheduledAt instanceof \DateTimeInterface && $scheduledAt->format('Y-m-d') <= $todayInTerritory;

            if ($isPastDate) {
                $visiteDone = $form->get('visiteDone')->getData();
                if (null === $visiteDone) {
                    $form->get('visiteDone')->addError(new FormError('Veuillez préciser si la visite a eu lieu.'));
                }
                if (null === $form->get('occupantPresent')->getData()) {
                    $form->get('occupantPresent')->addError(new FormError('Veuillez préciser si l\'occupant était présent.'));
                }
                if (null === $form->get('proprietairePresent')->getData()) {
                    $form->get('proprietairePresent')->addError(new FormError('Veuillez préciser si le propriétaire était présent.'));
                }
                // Conclusion obligatoire uniquement si la visite est indiquée comme ayant eu lieu
                if (true === $visiteDone && empty($form->get('concludeProcedure')->getData())) {
                    $form->get('concludeProcedure')->addError(new FormError('Veuillez préciser la conclusion de la visite.'));
                }
                if (!$form->get('details')->getData()) {
                    $form->get('details')->addError(new FormError('Veuillez saisir un commentaire pour la visite.'));
                }
            }
        });

        // Vide les champs de conclusion si la visite n'est pas encore passée
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (!is_array($data) || !isset($data['scheduledAt']) || !is_string($data['scheduledAt'])) {
                return;
            }

            $scheduledAt = \DateTimeImmutable::createFromFormat('Y-m-d', $data['scheduledAt']);
            if (false === $scheduledAt) {
                return;
            }
            $todayInTerritory = (new \DateTimeImmutable('today', $this->timezoneProvider->getDateTimezone()))->format('Y-m-d');
            $isPastDate = $scheduledAt->format('Y-m-d') <= $todayInTerritory;

            if (!$isPastDate) {
                $data['visiteDone'] = null;
                $data['occupantPresent'] = null;
                $data['proprietairePresent'] = null;
                $data['concludeProcedure'] = [];
                $data['details'] = null;
                $data['notifyUsager'] = false;
                $data['rapportDeVisite'] = null;

                $event->setData($data);
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
