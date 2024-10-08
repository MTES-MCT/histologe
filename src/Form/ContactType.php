<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ->add('email', TextType::class, [
                'label' => 'Votre adresse e-mail',
                'help' => 'Format attendu : nom@domaine.fr',
                'attr' => ['autocomplete' => 'email', 'aria-required' => 'true'],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre e-mail.'),
                    new Email(mode: Email::VALIDATION_MODE_STRICT),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Prénom et nom',
                'help' => 'Exemple : Claude Petit',
                'attr' => ['autocomplete' => 'name', 'aria-required' => 'true'],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre nom complet.'),
                ],
            ])
            ->add('organisme', TextType::class, [
                'label' => 'Organisme (facultatif)',
                'help' => 'Exemple : Mairie de Paris',
                'attr' => ['autocomplete' => 'organization'],
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
                'attr' => ['aria-required' => 'true'],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner le type de demande.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'attr' => ['rows' => 10, 'aria-required' => 'true'],
                'required' => false,
                'label' => 'Votre message',
                'help' => 'Ne partagez pas d\'informations sensibles (par ex. mot de passe, numéro de carte bleue, etc).<br>Format : 10 caractères minimum.',
                'help_html' => true,
                'constraints' => [
                    new Assert\NotBlank(message: 'Merci de renseigner votre message.'),
                    new Assert\Length(min: 10, minMessage: 'Votre message doit comporter au moins 10 caractères.'),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer le message',
                'attr' => ['class' => 'fr-btn fr-fi-mail-fill fr-btn--icon-left'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'front_contact',
            ],
        ]);
    }
}
