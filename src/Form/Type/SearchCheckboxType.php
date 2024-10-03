<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchCheckboxType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'expanded' => true,
            'multiple' => true,
            'attr' => ['class' => 'search-checkbox'],
            'noselectionlabel' => '',
            'nochoiceslabel' => '',
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['noselectionlabel'] = $options['noselectionlabel'];
        $view->vars['nochoiceslabel'] = $options['nochoiceslabel'];
    }
}
