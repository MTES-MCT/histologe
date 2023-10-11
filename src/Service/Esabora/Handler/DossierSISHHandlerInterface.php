<?php

namespace App\Service\Esabora\Handler;

use App\Messenger\Message\DossierMessageSISH;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.dossier_sish_handler')]
interface DossierSISHHandlerInterface
{
    public function handle(DossierMessageSISH $dossierMessageSISH): void;

    public static function getPriority(): int;
}
