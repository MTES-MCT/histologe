<?php

namespace App\Service\Esabora\Handler;

use App\Manager\JobEventManager;
use App\Messenger\Message\DossierMessageSISH;
use App\Repository\PartnerRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraSISHService;
use Symfony\Component\Serializer\SerializerInterface;

class DossierAdresseServiceHandler extends AbstractDossierSISHHandler
{
    protected ?string $action = AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE;

    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly SerializerInterface $serializer,
        private readonly JobEventManager $jobEventManager,
        private readonly PartnerRepository $partnerRepository
    ) {
        parent::__construct($this->serializer, $this->jobEventManager, $this->partnerRepository);
    }

    public function handle(DossierMessageSISH $dossierMessageSISH): void
    {
        $this->response = $this->esaboraSISHService->pushAdresse($dossierMessageSISH);
        $dossierMessageSISH->setSasAdresse($this->response->getSasId());
        parent::handle($dossierMessageSISH);
    }

    public static function getPriority(): int
    {
        return 3;
    }
}
