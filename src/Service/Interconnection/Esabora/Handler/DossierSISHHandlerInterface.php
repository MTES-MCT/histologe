<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Messenger\Message\Esabora\DossierMessageSISH;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.dossier_sish_handler')]
interface DossierSISHHandlerInterface
{
    public function handle(DossierMessageSISH $dossierMessageSISH): void;

    public function canFlagAsSynchronized(): bool;

    public static function getPriority(): int;
}
