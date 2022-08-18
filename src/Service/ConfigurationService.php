<?php

namespace App\Service;

use App\Repository\ConfigRepository;

class ConfigurationService
{
    private array $config;

    public function __construct(private ConfigRepository $configRepository)
    {
    }

    public function get()
    {
        if (empty($this->config)) {
            $this->config = $this->configRepository->findLast();
        }

        if (isset($this->config[0])) {
            return $this->config[0];
        }

        return false;
    }
}
