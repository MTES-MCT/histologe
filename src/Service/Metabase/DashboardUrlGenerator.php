<?php

namespace App\Service\Metabase;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DashboardUrlGenerator
{
    private Configuration $jwtConfig;

    public function __construct(
        #[Autowire(env: 'METABASE_SITE_URL')]
        private readonly string $siteUrl,
        #[Autowire(env: 'METABASE_SECRET_KEY')]
        private readonly string $secretKey,
        #[Autowire(env: 'METABASE_IFRAME_TTL')]
        private readonly string $ttlInMinutes,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
        $key = empty($this->secretKey) ? 'default_empty_key' : $this->secretKey;
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($key)
        );
    }

    public function generate(
        DashboardKey $dashboard,
        array $params = [],
        array $queryParams = [],
    ): ?string {
        $dashboardId = $dashboard->value;
        try {
            $builder = $this->jwtConfig->builder()
                ->withClaim('resource', ['dashboard' => $dashboardId])
                ->withClaim('params', $params)
                ->expiresAt($this->clock->now()->modify(sprintf('+%d minutes', $this->ttlInMinutes)));

            $token = $builder->getToken(
                $this->jwtConfig->signer(),
                $this->jwtConfig->signingKey(),
            );

            return $this->buildUrl($queryParams, $token->toString());
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    public function getTtlInSeconds(): int
    {
        $ttlInMinutes = (int) $this->ttlInMinutes;
        if ($ttlInMinutes > 1) {
            return ($ttlInMinutes - 1) * 60;
        }

        return 60;
    }

    private function buildUrl(array $queryParams, string $token): string
    {
        $baseUrl = sprintf('%s/embed/dashboard/%s', $this->siteUrl, $token);

        if (!empty($queryParams)) {
            $baseUrl .= '?'.http_build_query($queryParams);
        }

        $fragment = 'bordered=false&titled=false&theme=transparent';

        return $baseUrl.'#'.$fragment;
    }
}
