<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchCheckboxEnumType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => false,
            'multiple' => true,
            'attr' => ['class' => 'search-checkbox'],
            'noselectionlabel' => '',
            'nochoiceslabel' => '',
            'showSelectionAsBadges' => false,
        ]);
    }

    public function getParent(): string
    {
        return EnumType::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['noselectionlabel'] = $options['noselectionlabel'];
        $view->vars['nochoiceslabel'] = $options['nochoiceslabel'];
        $view->vars['showSelectionAsBadges'] = $options['showSelectionAsBadges'];
    }
}
