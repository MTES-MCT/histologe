<?php

namespace App\Messenger;

use App\Entity\Affectation;
use App\Factory\Esabora\DossierMessageFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\MessageBusInterface;

class EsaboraBus
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
                $dossierMessage = $dossierMessageFactory->createInstance($affectation);
                $this->messageBus->dispatch($dossierMessage);
            }
        }
    }
}
