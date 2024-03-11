<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email',
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre email.'),
                    new Assert\Email(mode: Email::VALIDATION_MODE_STRICT),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Prénom et nom',
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre nom complet.'),
                ],
            ])
            ->add('organisme', TextType::class, [
                'label' => 'Organisme (facultatif)',
                'required' => false,
            ])
            ->add('objet', ChoiceType::class, [
                'label' => 'Objet de votre demande',
                'choices' => [
                    'Sélectionnez un type de demande' => '',
                    'Demande de renseignement / de démo' => 'Demande de renseignement / de démo',
                    'Je souhaite activer mon compte' => 'Je souhaite activer mon compte',
                    'J\'ai besoin d\'aide pour utiliser l\'interface d\'administration' => 'J\'ai besoin d\'aide pour utiliser l\'interface d\'administration',
                    'J\'aimerais signaler un bug' => 'J\'aimerais signaler un bug',
                    'Autre' => 'Autre',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner le type de demande.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'minlength' => 10,
                ],
                'label' => 'Votre message',
                'help' => 'Ne partagez pas d\'informations sensibles (par ex. mot de passe, numéro de carte bleue, etc).<br>Format : 10 caractères minimum.',
                'help_html' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre message.'),
                    new Assert\Length(min: 10, minMessage: 'Votre message doit comporter au moins 10 caractères.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'front_contact',
                'class' => 'needs-validation',
                'novalidate' => 'true',
            ],
        ]);
    }
}
