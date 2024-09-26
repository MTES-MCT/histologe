<?php

namespace App\Messenger\MessageHandler\Esabora;

use App\Manager\AffectationManager;
use App\Messenger\Message\Esabora\DossierMessageSCHS;
use App\Service\Interconnection\Esabora\EsaboraSCHSService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final readonly class DossierMessageSCHSHandler
{
    public function __construct(
        private EsaboraSCHSService $esaboraService,
        private AffectationManager $affectationManager,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __invoke(DossierMessageSCHS $schsDossierMessage): void
    {
        $response = $this->esaboraService->pushDossier($schsDossierMessage);
        if (200 === $response->getStatusCode()) {
            $this->affectationManager->flagAsSynchronized($schsDossierMessage);
        }
    }
}
