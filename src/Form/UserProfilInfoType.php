<?php

namespace App\Form;

use App\Entity\User;
use App\Service\UploadHandlerService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfilInfoType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('avatar', FileType::class, [
                'label' => 'Choisir un nouvel avatar (facultatif)',
                'help' => 'Taille Maximale : 5 Mo, Formats supportés : '.UploadHandlerService::getAcceptedExtensions('resizable'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou GIF)',
                        'maxSizeMessage' => 'La taille du fichier ne doit pas dépasser 5 Mo.',
                    ]),
                ],
            ])
            ->add('prenom', null, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('nom', null, [
                'label' => 'Nom',
                'required' => false,
            ])
            ->add('fonction', null, [
                'label' => 'Fonction (facultatif)',
                'help' => 'Renseignez ici votre fonction au sein de votre organisation.',
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'Téléphone professionnel (facultatif)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
