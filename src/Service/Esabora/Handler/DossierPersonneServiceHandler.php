<?php

namespace App\Service\Esabora\Handler;

use App\Manager\JobEventManager;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Repository\PartnerRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Esabora\Model\DossierMessageSISHPersonne;
use Symfony\Component\Serializer\SerializerInterface;

class DossierPersonneServiceHandler extends AbstractDossierSISHHandler
{
    protected ?string $action = AbstractEsaboraService::ACTION_PUSH_DOSSIER_PERSONNE;

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
        /** @var DossierMessageSISHPersonne $dossierPersonne */
        foreach ($dossierMessageSISH->getPersonnes() as $dossierPersonne) {
            $this->response = $this->esaboraSISHService->pushPersonne($dossierMessageSISH, $dossierPersonne);
            parent::handle($dossierMessageSISH);
        }
    }

    public static function getPriority(): int
    {
        return 1;
    }
}
