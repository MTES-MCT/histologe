<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class DocumentIAType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('workflowId', null, [
                'label' => 'ID du workflow à exécuter',
                'required' => true,
                'help' => 'Vous pouvez récupérer l\'ID d\'un workflow en utilisant l\'API Document IA (GET /api/v2/workflows)',
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier à faire analyser par Document IA',
                'required' => true,
                'help' => 'Extensions acceptées : PDF, JPG ou PNG (max 25MB)',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Analyser',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_token_id' => 'document_ia_form',
        ]);
    }
}
