<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class InAppCommunicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', null, [
            'label' => 'Titre',
            'help' => 'Titre ou description obligatoire (l\'un des deux). 255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('description', null, [
            'label' => 'Description',
            'help' => 'Titre ou description obligatoire (l\'un des deux). 255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('url', null, [
            'label' => 'URL (facultatif)',
            'help' => '255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('urlTitle', null, [
            'label' => 'Libellé du lien (facultatif)',
            'help' => '255 caractères maximum.',
            'required' => false,
        ]);
        $builder->add('communicationType', ChoiceType::class, [
            'choices' => [
                'Information' => 'info',
                'Warning' => 'warning',
                'Alerte' => 'alert',
            ],
            'label' => 'Type de communication <span class="text-required">*</span>',
            'label_html' => true,
            'required' => false,
        ]);
        $builder->add('userRoles', ChoiceType::class, [
            'label' => 'Utilisateurs concernés (facultatif)',
            'help' => 'Si aucun rôle n\'est sélectionné, le bandeau sera affiché à tous les utilisateurs.',
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'choices' => [
                'Resp. Territoire' => 'ROLE_ADMIN_TERRITORY',
                'Admin. partenaire' => 'ROLE_ADMIN_PARTNER',
                'Agent' => 'ROLE_USER_PARTNER',
            ],
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Valider',
            'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
            'row_attr' => ['class' => 'fr-text--right'],
        ]);
    }
}
