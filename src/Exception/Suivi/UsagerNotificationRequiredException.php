<?php

namespace App\Exception\Suivi;

class UsagerNotificationRequiredException extends \Exception
{
    private mixed $value;

    /**
     * @var array<array<string, string>>
     */
    private array $errors;

    public function __construct(
        mixed $value = null,
        string $message = 'Vous voulez réouvrir pour le partenaire, un suivi de réouverture va être crée. Vous devez indiquer si vous souhaitez notifier l\'usager.',
        int $code = 0, ?\Throwable $previous = null,
    ) {
        $this->value = $value;
        $this->errors = [
            [
                'property' => 'notifyUsager',
                'message' => 'Veuillez renseigner la valeur true ou false.',
            ],
        ];
        parent::__construct($message, $code, $previous);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
