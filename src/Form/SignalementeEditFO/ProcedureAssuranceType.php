<?php

namespace App\Form\SignalementeEditFO;

use App\Entity\Model\InformationProcedure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
class ProcedureAssuranceType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('infoProcedureAssuranceContactee', ChoiceType::class, [
                'label' => 'L\'assurance a-t-elle été contactée ? <span class="text-required">*</span>',
                'label_html' => true,
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Pas d\'assurance logement' => 'pas_assurance_logement',
                    'Ne sait pas' => 'nsp',
                ],
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(
                        groups: ['Default'],
                        message: 'Veuillez préciser si l\'assurance a été contactée.',
                    ),
                ],
            ])
            ->add('infoProcedureReponseAssurance', TextareaType::class, [
                'label' => "Quelle a été la réponse de l'assurance ? (Obligatoire si assurance contactée)",
                'help' => 'Format attendu : 255 caractères maximum',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 255,
                        maxMessage: 'La réponse du bailleur doit comporter au maximum {{ limit }} caractères.',
                    ),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'fr-btn--primary',
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) {
            $form = $event->getForm();
            $infoProcedureAssuranceContactee = $form->get('infoProcedureAssuranceContactee')->getData();
            $infoProcedureReponseAssurance = $form->get('infoProcedureReponseAssurance')->getData();
            // Ca ne devrait être obligatoire que si le déclarant est locataire, bailleur occupant, bailleur
            // mais plus strict ici que dans le formulaire usager pour éviter de complexifier la logique de validation
            if ('oui' === $infoProcedureAssuranceContactee && empty($infoProcedureReponseAssurance)) {
                $form->get('infoProcedureReponseAssurance')->addError(new FormError('Veuillez préciser la réponse de l\'assurance.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InformationProcedure::class,
        ]);
    }
}
