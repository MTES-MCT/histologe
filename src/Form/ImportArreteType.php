<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<mixed>
 */
class ImportArreteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'Ajouter un fichier',
            'help' => 'Format supporté : .csv ; 50 lignes maximum.',
            'attr' => [
                'accept' => '.csv',
                'class' => 'fr-upload',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Veuillez sélectionner un fichier.'),
                new Assert\File(
                    maxSize: '1M',
                    mimeTypes: ['text/csv', 'application/csv'],
                    mimeTypesMessage: 'Veuillez uploader un fichier CSV valide.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'id' => 'import-arrete-form',
            ],
        ]);
    }
}
