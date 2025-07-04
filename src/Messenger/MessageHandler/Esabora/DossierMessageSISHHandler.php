<?php

namespace App\Messenger\MessageHandler\Esabora;

use App\Manager\AffectationManager;
use App\Messenger\Message\Esabora\DossierMessageSISH;
use App\Service\Interconnection\Esabora\Handler\DossierSISHHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DossierMessageSISHHandler
{
    /**
     * @var iterable<string, DossierSISHHandlerInterface>
     */
    private iterable $dossierSISHHandlers;

    /**
     * @param iterable<string, DossierSISHHandlerInterface> $dossierSISHHandlers
     */
    public function __construct(
        #[AutowireIterator(
            'app.dossier_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $dossierSISHHandlers,
        private readonly AffectationManager $affectationManager,
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
