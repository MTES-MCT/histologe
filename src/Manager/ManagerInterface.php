<?php

namespace App\Manager;

interface ManagerInterface
{
    public function save(object $entity): void;

    public function remove(object $entity): void;
}
