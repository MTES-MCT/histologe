<?php

namespace App\Service;

use Symfony\Component\Form\FormInterface;

class FormHelper
{
    public static function getErrorsFromForm(FormInterface $form)
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = self::getErrorsFromForm($childForm)) {
                    foreach ($childErrors as $childError) {
                        $errors[$childForm->getName().'_'.uniqid()] = $childError;
                    }
                }
            }
        }

        return $errors;
    }
}
