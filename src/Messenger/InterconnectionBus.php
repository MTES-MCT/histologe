<?php

namespace App\Messenger;

use App\Entity\Affectation;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class InterconnectionBus
{
    /**
     * @var iterable<string, MessageBusInterface>
     */
    private iterable $dossierMessageFactories;

    /**
     * @param iterable<string, MessageBusInterface> $dossierMessageFactories
     */
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[AutowireIterator('app.dossier_message_factory')] iterable $dossierMessageFactories,
    ) {
        $this->dossierMessageFactories = $dossierMessageFactories;
    }

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(Affectation $affectation): void
    {
        if (!$affectation->getPartner()->canSyncWithEsabora()
            && !$affectation->getPartner()->canSyncWithOilhi($affectation->getSignalement())
            && !$affectation->getPartner()->canSyncWithIdoss()
        ) {
            return;
        }
        /** @var DossierMessageFactoryInterface $dossierMessageFactory */
        foreach ($this->dossierMessageFactories as $dossierMessageFactory) {
            if ($dossierMessageFactory->supports($affectation)) {
                $this->messageBus->dispatch($dossierMessageFactory->createInstance($affectation));
            }
        }
    }
}
