<?php

namespace App\Service\Files;

use App\Entity\Signalement;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DocumentProvider
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function getModeleCourrierPourProprietaire(Signalement $signalement): ?string
    {
        if (!$signalement->getIsProprioAverti()
            && file_exists($modeleCourrier = $this->parameterBag->get('kernel.project_dir').'/public/build/files/Lettre-information-proprietaire-bailleur_A-COMPLETER.pdf')) {
            return $modeleCourrier;
        }

        return null;
    }
}
