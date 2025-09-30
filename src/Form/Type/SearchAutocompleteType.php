<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'search-autocomplete'],
            'autocomplete_choices' => [],
        ]);

        $resolver->setAllowedTypes('autocomplete_choices', 'array');
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['autocomplete_choices'] = $options['autocomplete_choices'];

        // Ajouter les attributs data pour JavaScript
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-autocomplete-choices' => json_encode(array_values($options['autocomplete_choices'])),
            'autocomplete' => 'off',
        ]);
    }
}
