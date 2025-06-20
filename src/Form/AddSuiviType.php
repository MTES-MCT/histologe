<?php

namespace App\Form;

use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Entity\Suivi;
use App\Form\Type\SearchCheckboxType;
use App\Repository\FileRepository;
use App\Repository\SignalementQualificationRepository;
use App\Service\DataValidationHelper;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AddSuiviType extends AbstractType
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly SignalementQualificationRepository $signalementQualificationRepository,
        private readonly Security $security,
        private readonly QualificationStatusService $qualificationStatusService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $suivi = $builder->getData();
        $signalement = $suivi->getSignalement();
        $isNotNotifiable = DataValidationHelper::isInvalidEmail($signalement->getMailDeclarant()) && DataValidationHelper::isInvalidEmail($signalement->getMailOccupant());

        $builder->add('isPublic', null, [
            'label' => 'En cochant cette case, le suivi sera envoyé à l\'usager',
            'row_attr' => [
                'class' => $isNotNotifiable ? 'fr-hidden' : 'fr-toggle',
            ],
            'label_attr' => [
                'class' => 'fr-toggle__label',
            ],
            'attr' => [
                'class' => 'fr-toggle__input',
            ],
            'required' => false,
            'disabled' => $isNotNotifiable,
        ]);
        $builder->add('description', null, [
            'label' => false,
            'help' => 'Décrivez la ou les action(s) menée(s). 10 caractères minimum <span class="fr-text-default--error">*</span>',
            'help_html' => true,
            'attr' => [
                'class' => 'editor',
            ],
            'required' => false,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length([
                    'min' => 10,
                    'minMessage' => 'Le contenu du suivi doit contenir au moins {{ limit }} caractères.',
                ]),
            ],
        ]);
        $signalementFiles = $signalement->getFiles()->filter(function (File $file) {
            return $file->isTypeDocument() && !$file->getIsSuspicious();
        });
        $signalementQualificationNDE = $this->signalementQualificationRepository->findOneBy([
            'signalement' => $signalement,
            'qualification' => Qualification::NON_DECENCE_ENERGETIQUE,
        ]);
        if ($this->security->isGranted('SIGN_SEE_NDE', $signalement) && $this->qualificationStatusService->canSeenNDEEditZone($signalementQualificationNDE)) {
            $standaloneFiles = $this->fileRepository->findBy(['isStandalone' => true], ['title' => 'ASC']);
            $choices = [
                'Documents du dossier' => $signalementFiles->toArray(),
                'Documents types' => $standaloneFiles,
            ];
        } else {
            $choices = $signalementFiles->toArray();
        }
        $builder->add('files', SearchCheckboxType::class, [
            'class' => File::class,
            'choice_label' => 'title',
            'label' => 'Fichiers',
            'noselectionlabel' => 'Sélectionner une ou plusieurs fichiers à joindre au suivi',
            'nochoiceslabel' => 'Aucun fichiers disponible',
            'mapped' => false,
            'choices' => $choices,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Suivi::class,
        ]);
    }
}
