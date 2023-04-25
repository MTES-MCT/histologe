<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\DossierMessageSISH;
use App\Service\Esabora\Handler\DossierSISHHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DossierMessageSISHHandler
{
    private iterable $dossierSISHHandlers;

    public function __construct(
        #[TaggedIterator('app.dossier_sish_handler', defaultPriorityMethod: 'getPriority')] iterable $dossierSISHHandlers
    ) {
        $this->dossierSISHHandlers = $dossierSISHHandlers;
    }

    public function __invoke(DossierMessageSISH $dossierMessageSISH): void
    {
        dump($this->dossierSISHHandlers);
        /** @var DossierSISHHandlerInterface $dossierSISHHandler */
        foreach ($this->dossierSISHHandlers as $dossierSISHHandler) {
            $dossierSISHHandler->handle($dossierMessageSISH);
        }
    }
}
