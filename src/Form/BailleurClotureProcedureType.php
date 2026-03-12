<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;

class BailleurClotureProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reponse', ChoiceType::class, [
                'label' => 'Veuillez confirmer ou non. <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'expanded' => true,
                'choices' => [
                    'Oui, les travaux ont bien été faits. Je souhaite arrêter la démarche.' => 'oui',
                    'Non, les travaux n\'ont pas été faits. Je souhaite continuer la démarche.' => 'non',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Précisez la situation <span class="fr-text-default--error">*</span>',
                'label_html' => true,
                'help' => 'Précisez la situation <em>(10 caractères minimum)</em>',
                'help_html' => true,
                'required' => false,
                'attr' => [
                    'class' => 'editor',
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
                $form = $event->getForm();
                $data = $event->getData();

                if (($data['reponse'] ?? null) === 'non') {
                    $description = trim($data['description'] ?? '');

                    if (strlen($description) < 10) {
                        $form->get('description')->addError(
                            new FormError('Veuillez préciser la situation (10 caractères minimum).')
                        );
                    }
                }
            });
    }
}
