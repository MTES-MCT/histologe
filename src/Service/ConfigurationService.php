<?php

namespace App\Service;

use App\Repository\ConfigRepository;

class ConfigurationService
{
    private array $config;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->config = $configRepository->findLast();
    }

    public function get()
    {
        if (isset($this->config[0]))
            return $this->config[0];
        return false;
    }
}