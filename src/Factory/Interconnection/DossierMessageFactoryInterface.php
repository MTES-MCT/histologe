<?php

namespace App\Factory\Interconnection;

use App\Entity\Affectation;
use App\Messenger\Message\DossierMessageInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.dossier_message_factory')]
interface DossierMessageFactoryInterface
{
    public function supports(Affectation $affectation): bool;

    public function createInstance(Affectation $affectation): DossierMessageInterface;
}
