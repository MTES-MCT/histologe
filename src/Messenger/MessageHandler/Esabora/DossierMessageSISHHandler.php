<?php

namespace App\Messenger\MessageHandler\Esabora;

use App\Manager\AffectationManager;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Esabora\Handler\DossierSISHHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DossierMessageSISHHandler
{
    private iterable $dossierSISHHandlers;

    public function __construct(
        #[TaggedIterator(
            'app.dossier_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $dossierSISHHandlers,
        private AffectationManager $affectationManager
    ) {
        $this->dossierSISHHandlers = $dossierSISHHandlers;
    }

    public function __invoke(DossierMessageSISH $dossierMessageSISH): void
    {
        /** @var DossierSISHHandlerInterface $dossierSISHHandler */
        foreach ($this->dossierSISHHandlers as $dossierSISHHandler) {
            $dossierSISHHandler->handle($dossierMessageSISH);
            if ($dossierSISHHandler->canFlagAsSynchronized()) {
                $this->affectationManager->flagAsSynchronized($dossierMessageSISH);
            }
        }
    }
}
