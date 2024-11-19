<?php

namespace App\Service;

use App\Dto\Request\Signalement\RequestInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormHelper
{
    public static function getErrorsFromForm(FormInterface $form, $recursive = false): array
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            if ($recursive) {
                $errors[] = $error->getMessage();
            } else {
                $errors['__nopath__']['errors'][] = $error->getMessage();
            }
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = self::getErrorsFromForm($childForm, true)) {
                    foreach ($childErrors as $childError) {
                        $errors[$childForm->getName()]['errors'][] = $childError;
                    }
                }
            }
        }

        return $errors;
    }

    public static function getErrorsFromRequest(
        ValidatorInterface $validator,
        RequestInterface $request,
        ?array $validationGroups = [],
    ): array {
        $errors = [];
        $violations = $validator->validate($request, null, $validationGroups);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors['errors'][$violation->getPropertyPath()]['errors'][] = $violation->getMessage();
            }
        }

        return $errors;
    }
}
