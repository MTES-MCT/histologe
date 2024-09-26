<?php

namespace App\Messenger\MessageHandler\Oilhi;

use App\Manager\AffectationManager;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\Interconnection\Oilhi\HookZapierService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
readonly class DossierMessageHandler
{
    public function __construct(
        private HookZapierService $hookZapierService,
        private AffectationManager $affectationManager,
    ) {
    }

    /**
     * @throws ExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __invoke(DossierMessage $dossierMessage): void
    {
        $response = $this->hookZapierService->pushDossier($dossierMessage);
        if (Response::HTTP_OK === $response->getStatusCode()) {
            $this->affectationManager->flagAsSynchronized($dossierMessage);
        }
    }
}
