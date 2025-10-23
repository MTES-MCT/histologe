<?php

namespace App\Tests;

trait KernelServiceHelperTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $service
     *
     * @return T
     */
    protected function getService(string $service): object
    {
        /** @var T $obj */
        $obj = static::getContainer()->get($service);
        if (null === $obj) {
            throw new \LogicException(sprintf('Service %s not found.', $service));
        }

        return $obj;
    }
}
