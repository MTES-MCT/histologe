<?php

namespace App\Service\Behaviour;

use Doctrine\Common\Collections\Collection;

trait SearchQueryTrait
{
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
                $params[$key] = $value->getId();
            } elseif (null !== $value) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
