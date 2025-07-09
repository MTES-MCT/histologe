<?php

namespace App\Service\Behaviour;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

trait SearchQueryTrait
{
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;

    public function getPage(): int
    {
        if ($this->page < 1 || empty($this->page)) {
            return 1;
        }

        return $this->page;
    }

    public function setPage(?string $page): void
    {
        if (empty($page) || !is_numeric($page) || (int) $page < 1) {
            $this->page = 1;

            return;
        }
        $this->page = (int) $page;
    }

    /**
     * @return array<mixed>
     */
    public function getUrlParams(): array
    {
        $params = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (in_array($key, ['user', 'page'])) {
                continue;
            }
            if ($value instanceof Collection) {
                if ($value->isEmpty()) {
                    continue;
                }
                $params[$key] = $value->map(fn ($partner) => $partner->getId())->toArray();
            } elseif (is_object($value)) {
                $params[$key] = isset($value->value) ? $value->value : $value->getId();
            } elseif (null !== $value) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
