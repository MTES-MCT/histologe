<?php

namespace App\Repository\Behaviour;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.entity_cleaner')]
interface EntityCleanerRepositoryInterface
{
    public function cleanOlderThan(string $period = '- 30 days'): int;
}
