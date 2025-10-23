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
        $obj = static::getContainer()->get($service);
        assert(null !== $obj);

        /* @var T $obj */
        return $obj;
    }
}
