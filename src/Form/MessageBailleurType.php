<?php

namespace App\Form;

use App\Entity\File;
use App\Service\UploadHandlerService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class MessageBailleurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Votre message',
                'help' => 'Dix (10) caractères minimum.',
                'attr' => [
                    'rows' => 5,
                ],
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
                    ),
                ],
            ])
            ->add('files', FileType::class, [
                'label' => 'Ajouter des documents (facultatif)',
                'help' => 'Taille maximale par document : 10 Mo, Formats supportés : '.UploadHandlerService::getAcceptedExtensions(),
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new Assert\All([
                        new Assert\File(
                            maxSize: '10M',
                            mimeTypes: File::DOCUMENT_MIME_TYPES,
                            mimeTypesMessage: 'Veuillez télécharger un fichier au format '.UploadHandlerService::getAcceptedExtensions().', et ne dépassant pas 10 Mo.'
                        ),
                    ]),
                ],
            ])
        ;
    }
}
