<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

trait KernelServiceHelperTrait
{
    /**
     * @template T of object
     * @param class-string<T> $service
     * @return T
     */
    protected function getService(string $service): object
    {
        $obj = static::getContainer()->get($service);
        assert($obj !== null);
        return $obj;
    }
}