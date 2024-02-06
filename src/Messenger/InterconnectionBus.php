<?php

namespace App\Messenger;

use App\Entity\Affectation;
use App\Factory\Interconnection\DossierMessageFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\MessageBusInterface;

class InterconnectionBus
{
    private iterable $dossierMessageFactories;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[TaggedIterator('app.dossier_message_factory')] iterable $dossierMessageFactories
    ) {
        $this->dossierMessageFactories = $dossierMessageFactories;
    }

    public function dispatch(Affectation $affectation): void
    {
        /** @var DossierMessageFactoryInterface $dossierMessageFactory */
        foreach ($this->dossierMessageFactories as $dossierMessageFactory) {
            if ($dossierMessageFactory->supports($affectation)) {
                $this->messageBus->dispatch($dossierMessageFactory->createInstance($affectation));
            }
        }
    }
}
