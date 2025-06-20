<?php

namespace App\Manager;

interface ManagerInterface
{
    public function save(object $entity, bool $flush = true): void;

    public function remove(object $entity, bool $flush = true): void;

    public function flush(): void;
}
