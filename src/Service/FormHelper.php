<?php

namespace App\Service;

use App\Dto\Request\Signalement\RequestInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function getErrorsFromForm(FormInterface $form, bool $withPrefix = false, bool $recursive = false): array
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
                if ($childErrors = self::getErrorsFromForm(form: $childForm, withPrefix: $withPrefix, recursive: true)) {
                    foreach ($childErrors as $childError) {
                        if ($withPrefix) {
                            $errors[self::getFieldNameWithPrefix($childForm)]['errors'][] = $childError;
                        } else {
                            $errors[$childForm->getName()]['errors'][] = $childError;
                        }
                    }
                }
            }
        }

        return $errors;
    }

    private static function getFieldNameWithPrefix(FormInterface $form): string
    {
        $fieldName = $form->getName();
        while ($form->getParent()) {
            $form = $form->getParent();
        }
        $blockPrefix = $form->getConfig()->getType()->getBlockPrefix();

        return $blockPrefix.'['.$fieldName.']';
    }

    /**
     * @param array<string> $validationGroups
     *
     * @return array<string, mixed>
     */
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
